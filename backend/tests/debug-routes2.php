<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// First make a request to boot providers
$req = Illuminate\Http\Request::create('/v1/core/health', 'GET');
$resp = $kernel->handle($req);
echo "Health check status: " . $resp->getStatusCode() . "\n\n";

// Now list all routes after boot
echo "=== جميع المسارات المسجلة ===\n";
$router = $app->make('router');
foreach ($router->getRoutes() as $route) {
    echo '  ' . implode('|', $route->methods()) . ' ' . $route->uri() . "\n";
}

// Now match the auth route
echo "\n=== مطابقة /v1/auth/login ===\n";
$req2 = Illuminate\Http\Request::create('/v1/auth/login', 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json',
    'HTTP_ACCEPT' => 'application/json',
], json_encode(['email' => 'admin@beza.test', 'password' => 'admin123', 'device_id' => 'test-device-001']));

try {
    $route = $router->getRoutes()->match($req2);
    echo 'مطابق: ' . $route->uri() . "\n";
    echo 'ميدلوير: ' . json_encode($route->gatherMiddleware()) . "\n";
} catch (\Throwable $e) {
    echo 'خطأ: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
}
