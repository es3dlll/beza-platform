<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Check who handles form POST requests (method spoofing)
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Send a GET to auth/login to see if it exists
$req = Illuminate\Http\Request::create('/v1/auth/login', 'GET', [], [], [], [
    'HTTP_ACCEPT' => 'application/json',
]);

$resp = $kernel->handle($req);
echo 'GET STATUS: ' . $resp->getStatusCode() . PHP_EOL;
$data = json_decode($resp->getContent(), true);
if (is_array($data)) {
    echo 'GET JSON: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} else {
    echo 'GET CONTENT: ' . substr($resp->getContent(), 0, 200) . PHP_EOL;
}
