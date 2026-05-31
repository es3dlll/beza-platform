# Beza Platform — Makefile
# أوامر التطوير الموحدة لجميع المنصات

.PHONY: help install dev test format clean analyze

help: ## عرض هذه المساعدة
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# ─── Backend (Laravel 13) ───────────────────────────────

backend-install: ## تثبيت اعتماديات Laravel
	cd backend && composer install

backend-dev: ## تشغيل خادم Laravel المحلي
	cd backend && php artisan serve

backend-test: ## تشغيل اختبارات Laravel
	cd backend && php artisan test

backend-format: ## توحيد صياغة PHP
	cd backend && composer format

backend-analyze: ## تحليل سكوني PHP
	cd backend && composer analyze

backend-migrate: ## تشغيل هجرات قاعدة البيانات
	cd backend && php artisan migrate:fresh --seed

# ─── Frontend Admin (React 19) ──────────────────────────

admin-install: ## تثبيت اعتماديات React
	cd frontend/admin && npm install

admin-dev: ## تشغيل خادم React المحلي
	cd frontend/admin && npm run dev

admin-test: ## تشغيل اختبارات React
	cd frontend/admin && npm run test

admin-format: ## توحيد صياغة TypeScript/JavaScript
	cd frontend/admin && npm run format

admin-build: ## بناء React للإنتاج
	cd frontend/admin && npm run build

# ─── Mobile (Flutter 3.29) ──────────────────────────────

mobile-install: ## تثبيت اعتماديات Flutter
	cd frontend/mobile && flutter pub get

mobile-dev: ## تشغيل Flutter على الجهاز
	cd frontend/mobile && flutter run

mobile-test: ## تشغيل اختبارات Flutter
	cd frontend/mobile && flutter test

mobile-format: ## توحيد صياغة Dart
	cd frontend/mobile && dart format .

mobile-analyze: ## تحليل سكوني Dart
	cd frontend/mobile && flutter analyze

mobile-build-android: ## بناء Android
	cd frontend/mobile && flutter build apk --release

mobile-build-ios: ## بناء iOS
	cd frontend/mobile && flutter build ios --release

# ─── أوامر شاملة ────────────────────────────────────────

install: backend-install admin-install mobile-install ## تثبيت جميع الاعتماديات

dev: ## تشغيل جميع الخوادم المحلية (يتطلب نوافذ منفصلة)
	@echo "Run each in separate terminals:"
	@echo "  make backend-dev"
	@echo "  make admin-dev"
	@echo "  make mobile-dev"

test: backend-test admin-test mobile-test ## تشغيل جميع الاختبارات

format: backend-format admin-format mobile-format ## توحيد صياغة جميع المنصات

analyze: backend-analyze admin-analyze mobile-analyze ## تحليل سكوني لجميع المنصات

clean: ## تنظيف الملفات المؤقتة
	cd backend && rm -rf vendor node_modules storage/framework/cache/data/*
	cd frontend/admin && rm -rf node_modules dist
	cd frontend/mobile && rm -rf build .dart_tool
