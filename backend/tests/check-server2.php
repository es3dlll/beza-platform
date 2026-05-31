<?php
$errno = 0; $errstr = '';
$fp = @fsockopen('localhost', 8000, $errno, $errstr, 2);
if ($fp) {
    echo "PORT 8000: OPEN\n";
    $out = "GET /v1/core/health HTTP/1.0\r\nHost: localhost\r\nAccept: application/json\r\n\r\n";
    fwrite($fp, $out);
    $resp = '';
    while (!feof($fp)) $resp .= fgets($fp, 4096);
    echo $resp;
    fclose($fp);
} else {
    echo "PORT 8000: CLOSED ($errno: $errstr)\n";
}
