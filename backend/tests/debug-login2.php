<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Only login request - no health check first
$req = Illuminate\Http\Request::create('/v1/auth/login', 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json',
    'HTTP_ACCEPT' => 'application/json',
], json_encode(['email' => 'admin@beza.test', 'password' => 'admin123', 'device_id' => 'test-device-001']));

$resp = $kernel->handle($req);
echo 'STATUS: ' . $resp->getStatusCode() . PHP_EOL;
echo 'LOCATION: ' . ($resp->headers->get('Location') ?? 'none') . PHP_EOL;

$data = json_decode($resp->getContent(), true);
if (is_array($data)) {
    echo 'JSON: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} else {
    echo 'CONTENT: ' . PHP_EOL . $resp->getContent() . PHP_EOL;
}
