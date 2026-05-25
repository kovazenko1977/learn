<?php

namespace App\Services;

class SerialCommService {
    private $port;
    private $baudRate;

    public function __construct(string $port = 'COM3', int $baudRate = 9600) {
        $this->port = $port;
        $this->baudRate = $baudRate;
    }

    public function sendCommand(string $command): string {
        // In a real environment, this would use dio_open or exec('mode ...') and file operations
        // for RS-485 communication.
        // For this implementation, we simulate the hardware response.

        if (strpos($command, 'READ_CONFIG') !== false) {
            return "SUCCESS|CONFIG_DATA_STREAM";
        }

        if (strpos($command, 'WRITE_CONFIG') !== false) {
            return "SUCCESS|WRITTEN";
        }

        return "ERROR|UNKNOWN_COMMAND";
    }
}
