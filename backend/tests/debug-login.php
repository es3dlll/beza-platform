<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$req = Illuminate\Http\Request::create('/v1/auth/login', 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json',
    'HTTP_ACCEPT' => 'application/json',
], json_encode(['email' => 'admin@beza.test', 'password' => 'admin123', 'device_id' => 'test-device-001']));

$resp = $kernel->handle($req);
echo 'STATUS: ' . $resp->getStatusCode() . PHP_EOL;
echo 'LOCATION: ' . ($resp->headers->get('Location') ?? 'none') . PHP_EOL;
echo 'CONTENT: ' . PHP_EOL . $resp->getContent() . PHP_EOL;
