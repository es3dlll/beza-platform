# 04 - تثبيت K6 (Installation)

## Windows

```bash
# عبر winget
winget install k6

# أو تحميل مباشر
# https://k6.io/docs/getting-started/installation/

# التحقق
k6 version
```

## macOS

```bash
brew install k6
```

## Linux (Ubuntu/Debian)

```bash
sudo apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
echo "deb https://dl.k6.io/deb stable main" | sudo tee /etc/apt/sources.list.d/k6.list
sudo apt-get update
sudo apt-get install k6
```

## تحقق من التثبيت

```bash
k6 version
# k6 v0.49.0 (go1.21.5, windows/amd64)
```
