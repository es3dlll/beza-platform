<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Boot and login
$req = Illuminate\Http\Request::create('/v1/auth/login', 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json',
    'HTTP_ACCEPT' => 'application/json',
], json_encode(['email' => 'admin@beza.test', 'password' => 'admin123', 'device_id' => 'test-device-001']));
$resp = $kernel->handle($req);
$data = json_decode($resp->getContent(), true);
$token = $data['data']['token'] ?? null;
echo 'Login: ' . $resp->getStatusCode() . ', Token: ' . ($token ? substr($token, 0, 10) . '...' : 'NONE') . PHP_EOL;

if (!$token) exit(1);

// Now test transfer
$req2 = Illuminate\Http\Request::create('/v1/wallet/transfer', 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json',
    'HTTP_ACCEPT' => 'application/json',
    'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
], json_encode([
    'to_wallet_id' => 'non-existent-test',
    'amount_fils' => 1000,
    'currency' => 'SYP',
]));
$resp2 = $kernel->handle($req2);
echo 'Transfer: ' . $resp2->getStatusCode() . PHP_EOL;
echo 'Location: ' . ($resp2->headers->get('Location') ?? 'none') . PHP_EOL;
$content2 = json_decode($resp2->getContent(), true);
if ($content2) {
    echo json_encode($content2, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} else {
    echo 'Raw: ' . substr($resp2->getContent(), 0, 300) . PHP_EOL;
}
