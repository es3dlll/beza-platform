<?php
declare(strict_types=1);

echo "=== بيزا — اختبار التكامل الحي ===\n\n";

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel;

$pass = 0; $fail = 0; $total = 0;

function test(string $name, Request $request, Kernel $kernel, callable $assert): array {
    global $total, $pass, $fail;
    $total++;
    try {
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        $result = $assert($response);
        if ($result === true) {
            echo "  [✓] {$name}\n";
            $pass++;
            return ['status' => 'pass', 'response' => $response];
        }
        echo "  [✗] {$name}: {$result}\n";
        $fail++;
        return ['status' => 'fail', 'response' => $response, 'reason' => $result];
    } catch (\Throwable $e) {
        echo "  [✗] {$name}: " . $e->getMessage() . "\n";
        $fail++;
        return ['status' => 'error', 'exception' => $e->getMessage()];
    }
}

function makeApp(): \Illuminate\Foundation\Application {
    return require __DIR__ . '/../bootstrap/app.php';
}

function freshKernel(\Illuminate\Foundation\Application $app): Kernel {
    return $app->make(Kernel::class);
}

// ============================================================
// 1. Health check
// ============================================================
echo "--- نقطة الفحص الصحي ---\n";

$app = makeApp();
$kernel = freshKernel($app);
$req = Request::create('/v1/core/health', 'GET');
$r = test('GET /v1/core/health → 200', $req, $kernel, function($r) {
    return $r->getStatusCode() === 200 ? true : 'توقع 200 حصل ' . $r->getStatusCode();
});

if ($r['status'] === 'pass') {
    $data = json_decode($r['response']->getContent(), true);
    echo '  message: ' . ($data['message'] ?? 'N/A') . "\n";
    echo '  status: ' . ($data['data']['status'] ?? 'N/A') . "\n";
    foreach (($data['data']['checks'] ?? []) as $c => $v) {
        $s = $v['status'] ?? $v['name'] ?? 'ok';
        echo '  ' . (($v['status'] ?? '') === 'healthy' ? '✓' : '•') . " {$c}: {$s}\n";
    }
}

// ============================================================
// 2. Auth login
// ============================================================
echo "\n--- نقطة المصادقة ---\n";

$app2 = makeApp();
$kernel2 = freshKernel($app2);
$req = Request::create('/v1/auth/login', 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json',
    'HTTP_ACCEPT' => 'application/json',
], json_encode(['email' => 'admin@beza.test', 'password' => 'admin123', 'device_id' => 'test-device-001']));
$lr = test('POST /v1/auth/login → 200', $req, $kernel2, function($r) {
    return $r->getStatusCode() === 200 ? true : 'توقع 200 حصل ' . $r->getStatusCode();
});

$token = null;
if ($lr['status'] === 'pass') {
    $data = json_decode($lr['response']->getContent(), true);
    $token = $data['data']['token'] ?? null;
    echo '  message: ' . ($data['message'] ?? '') . "\n";
    echo '  token: ' . ($token ? substr($token, 0, 20) . '...' : 'NONE') . "\n";
}

// ============================================================
// 3. Wallet transfer
// ============================================================
echo "\n--- نقطة التحويل المالي ---\n";

if (!$token) {
    echo '  [–] تخطي: لا يوجد توكن' . "\n";
    exit(1);
}

// Look up wallet IDs directly from SQLite
function getWalletIds(): ?object {
    $dbPath = __DIR__ . '/../database/database.sqlite';
    if (!file_exists($dbPath)) return null;
    $pdo = new PDO("sqlite:{$dbPath}");
    $users = $pdo->query("SELECT id, email FROM users WHERE email IN ('admin@beza.test','user1@beza.test')")->fetchAll(PDO::FETCH_ASSOC);
    $user1Id = null;
    foreach ($users as $u) { if ($u['email'] === 'user1@beza.test') $user1Id = $u['id']; }
    if (!$user1Id) return null;
    $stmt = $pdo->prepare("SELECT id FROM wallets WHERE user_id = ?");
    $stmt->execute([$user1Id]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
    return $wallet ? (object)['wallet_id' => $wallet['id']] : null;
}

$walletInfo = getWalletIds();
if (!$walletInfo) {
    echo '  [–] تخطي: لا توجد محفظة للمستخدم user1@beza.test' . "\n";
    exit(1);
}

$app3 = makeApp();
$kernel3 = freshKernel($app3);
$req = Request::create('/v1/wallet/transfer', 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json',
    'HTTP_ACCEPT' => 'application/json',
    'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
], json_encode([
    'to_wallet_id' => $walletInfo->wallet_id,
    'amount_fils' => 500,
    'currency' => 'SYP',
]));
$tr = test('POST /v1/wallet/transfer → 200', $req, $kernel3, function($r) {
    return $r->getStatusCode() === 200 ? true : 'توقع 200 حصل ' . $r->getStatusCode();
});

if ($tr['status'] === 'pass') {
    $data = json_decode($tr['response']->getContent(), true);
    echo '  message: ' . ($data['message'] ?? '') . "\n";
    echo '  entry_id: ' . ($data['data']['entry_id'] ?? 'N/A') . "\n";
    echo '  amount: ' . ($data['data']['amount_fils'] ?? '') . ' ' . ($data['data']['currency'] ?? '') . "\n";
}

// ============================================================
// Summary
// ============================================================
echo "\n=== الخلاصة ===\n";
echo "الإجمالي: {$total} | النجاح: {$pass} | الفشل: {$fail}\n\n";
exit($fail > 0 ? 1 : 0);
