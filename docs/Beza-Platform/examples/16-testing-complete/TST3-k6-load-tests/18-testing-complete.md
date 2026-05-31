# 18 - تشغيل جميع الاختبارات

## Script Runner

```javascript
// scripts/run-all-tests.js
import { execSync } from 'k6/execution';

const tests = [
  { name: 'Ping Test', file: 'scripts/ping-test.js' },
  { name: 'Auth Test', file: 'scripts/auth-test.js', env: true },
  { name: 'Transfer Test', file: 'scripts/transfer-test.js', env: true },
  { name: 'Mixed Workload', file: 'scripts/mixed-workload.js', env: true },
  { name: 'Stress Test', file: 'scripts/stress-test.js', env: true },
  { name: 'Spike Test', file: 'scripts/spike-test.js', env: true },
  { name: 'Soak Test', file: 'scripts/soak-test.js', env: true },
];

for (const test of tests) {
  console.log(`\n========== تشغيل: ${test.name} ==========`);
  const cmd = test.env
    ? `k6 run ${test.file} -e TOKEN=${__ENV.TOKEN} --out json=reports/${test.file.replace('scripts/', '').replace('.js', '')}.json`
    : `k6 run ${test.file} --out json=reports/${test.file.replace('scripts/', '').replace('.js', '')}.json`;
  execSync(cmd);
  console.log(`✅ ${test.name} مكتمل`);
}
```

## Batch Script (Windows)

```batch
:: scripts/run-all.bat
@echo off
set TOKEN=%1

echo ========== تشغيل جميع اختبارات K6 ==========
echo.

echo [1/7] Ping Test...
k6 run scripts/ping-test.js --out json=reports\ping.json
if %errorlevel% neq 0 (
  echo ❌ Ping Test فشل
  exit /b 1
)

echo [2/7] Auth Test...
k6 run scripts/auth-test.js -e TOKEN=%TOKEN% --out json=reports\auth.json
if %errorlevel% neq 0 exit /b 1

echo [3/7] Transfer Test...
k6 run scripts/transfer-test.js -e TOKEN=%TOKEN% --out json=reports\transfer.json
if %errorlevel% neq 0 exit /b 1

echo [4/7] Mixed Workload...
k6 run scripts/mixed-workload.js -e TOKEN=%TOKEN% --out json=reports\mixed.json
if %errorlevel% neq 0 exit /b 1

echo [5/7] Stress Test...
k6 run scripts/stress-test.js -e TOKEN=%TOKEN% --out json=reports\stress.json
if %errorlevel% neq 0 exit /b 1

echo [6/7] Spike Test...
k6 run scripts/spike-test.js -e TOKEN=%TOKEN% --out json=reports\spike.json
if %errorlevel% neq 0 exit /b 1

echo [7/7] Soak Test...
k6 run scripts/soak-test.js -e TOKEN=%TOKEN% --out json=reports\soak.json
if %errorlevel% neq 0 exit /b 1

echo.
echo ========== ✅ جميع الاختبارات مكتملة ==========
```

## Shell Script (Linux)

```bash
# scripts/run-all.sh
#!/bin/bash
TOKEN=${1:-"test-token-default"}
mkdir -p reports

echo "========== تشغيل جميع اختبارات K6 =========="
echo

for test in ping auth transfer mixed stress spike soak; do
  echo "[*] تشغيل $test ..."
  CMD="k6 run scripts/${test}-test.js"
  [ "$test" != "ping" ] && CMD="$CMD -e TOKEN=$TOKEN"
  CMD="$CMD --out json=reports/${test}.json"
  
  if $CMD; then
    echo "✅ $test مكتمل"
  else
    echo "❌ $test فشل"
    exit 1
  fi
done

echo
echo "========== ✅ جميع الاختبارات مكتملة =========="
```
