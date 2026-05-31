@echo off
cd /d "C:\laragon\www\Beza-Platform\backend"
php -S localhost:8000 -t public > storage\logs\server.log 2>&1
