# Infrastructure & Deployment - البنية التحتية

## Docker Compose

```
services:
  app:          # Laravel + PHP-FPM + Nginx
  mysql:        # MySQL 8.4 مع إعدادات AML-friendly
  redis:        # للجلسات، Queue، Rate Limiting
  rabbitmq:     # Event Bus
  backup:       # Cron job للنسخ الاحتياطي المشفر
  monitoring:   # Prometheus + Grafana
```

## بيئات النشر

| البيئة | الخادم | الغرض |
|--------|--------|-------|
| **Development** | محلي (Laragon) | تطوير واختبار |
| **Staging** | VPS (أوروبا) | تكامل واختبار ما قبل الإنتاج |
| **Production** | VPS (سوريا/لبنان + Cloudflare) | الإطلاق الرسمي |

## متطلبات الخادم

- **VPS:** 2 vCPU, 4GB RAM, 40GB NVMe
- **OS:** Ubuntu 24.04 LTS
- **Stack:** PHP 8.3+, MySQL 8.4, Redis 7.x, Nginx
- **SSL:** Let's Encrypt (تلقائي التجديد)

## النسخ الاحتياطي

- MySQL Backup: كل 6 ساعات (مشفر AES-256)
- Storage Backup: يومياً
- Off-site: تخزين خارجي مشفر
- اختبار استعادة: شهرياً

## المراقبة (Monitoring)

- **Uptime:** Cloudflare + UptimeRobot
- **Performance:** Prometheus + Grafana
- **Errors:** Sentry أو Flare
- **Logs:** Elastic Stack أو Laravel Telescope
- **Alerts:** PagerDuty/Telegram Bot

## أمان الخادم

- UFW: فتح 22 (SSH), 80 (HTTP), 443 (HTTPS) فقط
- Fail2Ban: منع هجمات التخمين
- SSH: مفاتيح فقط (لا كلمات مرور)
- Docker: تشغيل الحاويات كمستخدم غير جذر
