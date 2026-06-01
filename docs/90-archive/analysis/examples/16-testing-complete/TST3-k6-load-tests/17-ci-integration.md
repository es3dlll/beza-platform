# 17 - التكامل مع CI (CI Integration)

## GitHub Actions

```yaml
# .github/workflows/k6-load-tests.yml
name: K6 Load Tests

on:
  schedule:
    - cron: '0 6 * * 1'  # كل يوم إثنين 6 صباحاً
  workflow_dispatch:       # تشغيل يدوي
  push:
    branches: [main, staging]

jobs:
  load-test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Install K6
        run: |
          sudo apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
          echo "deb https://dl.k6.io/deb stable main" | sudo tee /etc/apt/sources.list.d/k6.list
          sudo apt-get update
          sudo apt-get install k6

      - name: Start test server
        run: |
          php artisan serve --port=8000 &>/dev/null &
          sleep 10

      - name: Run Ping Test
        run: k6 run scripts/ping-test.js --out json=reports/ping.json

      - name: Run Auth Test
        run: k6 run scripts/auth-test.js -e TOKEN=${{ secrets.TEST_TOKEN }} --out json=reports/auth.json

      - name: Run Transfer Test
        run: k6 run scripts/transfer-test.js -e TOKEN=${{ secrets.TEST_TOKEN }} --out json=reports/transfer.json

      - name: Run Stress Test
        run: k6 run scripts/stress-test.js -e TOKEN=${{ secrets.TEST_TOKEN }} --out json=reports/stress.json

      - name: Upload Reports
        uses: actions/upload-artifact@v4
        with:
          name: k6-reports
          path: reports/

      - name: Check Thresholds
        run: |
          for f in reports/*.json; do
            if grep -q '"failed":true' "$f"; then
              echo "❌ $f تجاوز الحدود"
              exit 1
            fi
          done
          echo "✅ جميع الحدود ضمن المعدل"
```

## GitLab CI

```yaml
# .gitlab-ci.yml
k6-load-tests:
  stage: test
  image: grafana/k6:latest
  script:
    - k6 run scripts/ping-test.js --out json=reports/ping.json
    - k6 run scripts/auth-test.js -e TOKEN=$TEST_TOKEN --out json=reports/auth.json
    - k6 run scripts/transfer-test.js -e TOKEN=$TEST_TOKEN --out json=reports/transfer.json
  artifacts:
    paths:
      - reports/
    when: always
  only:
    - schedules
    - main
```

## Slack Notification

```bash
# scripts/notify-slack.sh
#!/bin/bash
WEBHOOK_URL="$SLACK_WEBHOOK"
REPORT_FILE="$1"

SLACK_PAYLOAD=$(cat <<EOF
{
  "text": "📊 *تقرير اختبارات الحمل K6*\\nالنتيجة: ${{ job.status }}\\nالتاريخ: $(date)\\nللتقرير الكامل: ${{ github.server_url }}/${{ github.repository }}/actions/runs/${{ github.run_id }}"
}
EOF
)

curl -X POST -H 'Content-type: application/json' --data "$SLACK_PAYLOAD" "$WEBHOOK_URL"
```
