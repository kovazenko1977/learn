<?php

namespace App\Services;

class SerialCommService {
    private $port;
    private $baudRate;
    private $handle = null;
    private $isSimulation = false;

    public function __construct(string $port = 'COM3', int $baudRate = 9600) {
        $this->port = $port;
        $this->baudRate = $baudRate;

        // Comprehensive hardware detection
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // On Windows, we can't easily check file_exists for COM ports
            // but we can try to see if 'mode' command succeeds for that port
            exec("mode {$this->port} 2>NUL", $output, $return_var);
            if ($return_var !== 0) $this->isSimulation = true;
        } else {
            // Common Linux/Android RS-485 converter paths
            $paths = ['/dev/ttyUSB0', '/dev/ttyUSB1', '/dev/ttyAMA0', '/dev/ttyS0'];
            $found = false;
            foreach ($paths as $p) {
                if (file_exists($p)) {
                    $this->port = $p;
                    $found = true;
                    break;
                }
            }
            if (!$found && !file_exists($this->port)) {
                $this->isSimulation = true;
            }
        }
    }

    public function connect(): bool {
        if ($this->isSimulation) return false;

        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                exec("mode {$this->port}: baud={$this->baudRate} parity=n data=8 stop=1 2>NUL");
            } else {
                exec("stty -F {$this->port} {$this->baudRate} cs8 -cstopb -parenb 2>/dev/null");
            }

            $this->handle = @fopen($this->port, "r+b");
            return $this->handle !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function disconnect() {
        if ($this->handle) {
            fclose($this->handle);
            $this->handle = null;
        }
    }

    public function sendPacket(int $cmd, array $data = []): array {
        if ($this->isSimulation || (!$this->handle && !$this->connect())) {
            return ['status' => 'error', 'message' => 'Hardware not found or access denied'];
        }

        // Real Orion protocol framing
        $packet = [0xFE, count($data) + 2, $cmd];
        foreach ($data as $byte) $packet[] = $byte;
        $packet[] = $this->calculateCRC8($packet);

        $binary = pack('C*', ...$packet);
        if (@fwrite($this->handle, $binary) === false) {
            return ['status' => 'error', 'message' => 'Write failed'];
        }
        fflush($this->handle);

        // Real hardware read wait
        usleep(200000);
        $response = @fread($this->handle, 1024);

        if (!$response) {
            return ['status' => 'error', 'message' => 'No response from C2000M'];
        }

        return [
            'status' => 'ok',
            'raw' => bin2hex($response),
            'bytes' => array_values(unpack('C*', $response))
        ];
    }

    private function calculateCRC8(array $bytes): int {
        $crc = 0;
        foreach ($bytes as $byte) {
            $crc ^= $byte;
        }
        return $crc;
    }

    public function isHardwareConnected(): bool {
        if ($this->isSimulation) return false;
        $conn = $this->connect();
        if ($conn) $this->disconnect(); // Test connection only
        return $conn;
    }
}
