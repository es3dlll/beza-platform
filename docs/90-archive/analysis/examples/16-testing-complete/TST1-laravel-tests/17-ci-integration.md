# 17 - التكامل مع CI (CI Integration)

## GitHub Actions

```yaml
# .github/workflows/tests.yml
name: Laravel Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: beza_testing
          MYSQL_ROOT_PASSWORD: root
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s

      redis:
        image: redis:7
        ports:
          - 6379:6379

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
          extensions: mbstring, pdo_mysql, xml, curl, gd, bcmath, redis

      - name: Install Composer Dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Copy .env
        run: cp .env.example .env

      - name: Generate Key
        run: php artisan key:generate

      - name: Run Migrations
        run: php artisan migrate --env=testing
        env:
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: beza_testing
          DB_USERNAME: root
          DB_PASSWORD: root

      - name: Run Tests with Coverage
        run: php artisan test --coverage --min=80
        env:
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: beza_testing
          DB_USERNAME: root
          DB_PASSWORD: root

      - name: Upload Coverage
        uses: codecov/codecov-action@v3
        with:
          file: ./coverage.xml
```

## GitLab CI

```yaml
# .gitlab-ci.yml
stages:
  - test

phpunit:
  stage: test
  image: php:8.2-cli
  services:
    - mysql:8.0
    - redis:7
  variables:
    MYSQL_DATABASE: beza_testing
    MYSQL_ROOT_PASSWORD: root
    DB_HOST: mysql
    DB_DATABASE: beza_testing
    DB_USERNAME: root
    DB_PASSWORD: root
  script:
    - composer install --no-interaction --prefer-dist
    - cp .env.example .env
    - php artisan key:generate
    - php artisan migrate --env=testing
    - php artisan test --coverage --min=80
```

## فشل CI إذا كانت التغطية أقل من 80%

```bash
# يخرج مع exit code 1 إذا التغطية < 80%
php artisan test --coverage --min=80
```
