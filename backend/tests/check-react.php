<?php
$c = @file_get_contents('http://localhost:5173/');
if ($c === false) {
    echo "REACT: DOWN\n";
    exit(1);
}
echo "REACT: UP (" . strlen($c) . " bytes)\n";
echo substr($c, 0, 200) . "\n";
