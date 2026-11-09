<?php
$name = $_GET['name'] ?? '';
if (!$name || !preg_match('/^[a-z0-9._-]+$/i', $name)) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$filepath = __DIR__ . '/../data/uploads/' . $name;
if (file_exists($filepath)) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filepath);
    finfo_close($finfo);

    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    $videoMimes = [
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'ogv' => 'video/ogg',
        'ogg' => 'video/ogg',
        'mov' => 'video/quicktime'
    ];
    if (isset($videoMimes[$ext])) {
        $mime = $videoMimes[$ext];
    }

    $size = filesize($filepath);

    // Support HTTP Range requests for smooth video scrubbing & seeking
    if (isset($_SERVER['HTTP_RANGE'])) {
        $range = $_SERVER['HTTP_RANGE'];
        list($param, $range) = explode('=', $range);
        if (strtolower(trim($param)) === 'bytes') {
            list($start, $end) = explode('-', $range);
            $start = intval($start);
            $end = ($end === '') ? ($size - 1) : intval($end);
            if ($start > $end || $start > $size - 1) {
                header('HTTP/1.1 416 Range Not Satisfiable');
                header("Content-Range: bytes */$size");
                exit;
            }
            $length = $end - $start + 1;
            header('HTTP/1.1 206 Partial Content');
            header("Content-Range: bytes $start-$end/$size");
            header("Content-Length: $length");
            header('Content-Type: ' . $mime);
            header('Accept-Ranges: bytes');

            $fp = fopen($filepath, 'rb');
            fseek($fp, $start);
            $buffer = 1024 * 64;
            while (!feof($fp) && ($pos = ftell($fp)) <= $end) {
                if ($pos + $buffer > $end) {
                    $buffer = $end - $pos + 1;
                }
                echo fread($fp, $buffer);
                flush();
            }
            fclose($fp);
            exit;
        }
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $size);
    header('Accept-Ranges: bytes');
    header('Cache-Control: public, max-age=86400');
    readfile($filepath);
} else {
    header('HTTP/1.1 404 Not Found');
}
