<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// List all routes
echo "=== جميع المسارات المسجلة ===\n";
$router = $app->make('router');
foreach ($router->getRoutes() as $route) {
    echo '  ' . implode('|', $route->methods()) . ' ' . $route->uri() . "\n";
    echo '    Middleware: ' . json_encode($route->gatherMiddleware()) . "\n";
    echo '    Action: ' . (is_string($route->getAction('uses')) ? $route->getAction('uses') : 'Closure') . "\n";
}

// Try matching the auth route
echo "\n=== محاولة مطابقة مسار /v1/auth/login ===\n";
$req = Illuminate\Http\Request::create('/v1/auth/login', 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json',
    'HTTP_ACCEPT' => 'application/json',
], json_encode(['email' => 'admin@beza.test', 'password' => 'admin123', 'device_id' => 'test-device-001']));

try {
    $route = $router->getRoutes()->match($req);
    echo 'تم المطابقة: ' . $route->uri() . "\n";
    echo 'الميدلوير: ' . json_encode($route->gatherMiddleware()) . "\n";
} catch (\Throwable $e) {
    echo 'خطأ في المطابقة: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
}
