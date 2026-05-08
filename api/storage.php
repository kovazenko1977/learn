<?php
function loadData($filename) {
    $content = file_get_contents($filename);
    $content = str_replace(['<?php /*', '*/ ?>'], '', $content);
    return json_decode(trim($content), true);
}

function saveData($filename, $data) {
    $content = "<?php /*\n" . json_encode($data, JSON_PRETTY_PRINT) . "\n*/ ?>";
    file_put_contents($filename, $content, LOCK_EX);
}
