Set shell = CreateObject("WScript.Shell")
shell.CurrentDirectory = "C:\laragon\www\Beza-Platform\backend"
shell.Run "php artisan serve --port=8000", 0, False
shell.Run "cmd /c start http://localhost:8000/v1/core/health", 1, False
