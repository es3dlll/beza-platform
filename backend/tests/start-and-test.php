<?php
declare(strict_types=1);

echo "Starting PHP built-in server on port 8001...\n";

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

$process = proc_open('php artisan serve --port=8001', $descriptors, $pipes, __DIR__ . '/..');

if (!is_resource($process)) {
    echo "Failed to start server\n";
    exit(1);
}

// Close stdin
fclose($pipes[0]);

// Wait for server to start
sleep(3);

echo "Testing health endpoint...\n";
$ch = curl_init('http://localhost:8001/v1/core/health');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $http\n";
echo "Response:\n$resp\n\n";

echo "Testing login endpoint...\n";
$ch = curl_init('http://localhost:8001/v1/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'admin@beza.test',
    'password' => 'admin123',
    'device_id' => 'test-device-001',
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
]);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $http\n";
echo "Response:\n$resp\n";

proc_terminate($process);
echo "\nServer stopped.\n";
