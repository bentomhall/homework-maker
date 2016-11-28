<?php
$filename = $_GET["name"];
if (file_exists($filename) && preg_match('/\.zip$/', $filename)) {
    header('Content-Disposition: attachment; filename="'.basename($filename).'"');
    header('Content-Type: application/octet-stream');
    header('Content-Length: '.filesize($filename));
    header('Content-Transfer-Encoding: binary');
    readfile($filename);
}
else {
    http_response_code(404);
}