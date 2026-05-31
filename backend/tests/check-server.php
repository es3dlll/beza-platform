<?php
declare(strict_types=1);

$ctx = stream_context_create(['http' => ['timeout' => 2, 'header' => "Accept: application/json\r\n"]]);
$resp = @file_get_contents('http://localhost:8000/v1/core/health', false, $ctx);

if ($resp === false) {
    echo "SERVER: DOWN\n";
    exit(1);
}

$data = json_decode($resp, true);
echo "SERVER: UP (200)\n";
echo "Status: " . ($data['data']['status'] ?? 'unknown') . "\n";
echo "Message: " . ($data['message'] ?? '') . "\n";
echo "Timestamp: " . ($data['timestamp'] ?? '') . "\n";
