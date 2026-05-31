<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$req = Illuminate\Http\Request::create('/v1/auth/login', 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json',
    'HTTP_ACCEPT' => 'application/json',
    'HTTP_ORIGIN' => 'http://localhost:5173',
], json_encode(['email' => 'admin@beza.test', 'password' => 'admin123', 'device_id' => 'test-device-001']));

$resp = $kernel->handle($req);

echo 'STATUS: ' . $resp->getStatusCode() . PHP_EOL;
echo 'LOCATION: ' . ($resp->headers->get('Location') ?? 'none') . PHP_EOL;

// Check if any session/CSRF cookie is set
foreach ($resp->headers->getCookies() as $cookie) {
    echo 'COOKIE: ' . $cookie->getName() . ' = ' . substr($cookie->getValue(), 0, 20) . '...' . PHP_EOL;
}

$data = json_decode($resp->getContent(), true);
if (is_array($data)) {
    echo 'JSON: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} else {
    echo 'CONTENT: ' . substr($resp->getContent(), 0, 500) . PHP_EOL;
}
