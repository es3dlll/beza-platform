# 12 - النسخ الاحتياطي والاستعادة (Backup & Restore)

## النسخ الاحتياطي (Backup)

```bash
# نسخ قاعدة البيانات كاملة
mysqldump -u root -p beza > beza_backup.sql

# نسخ مع البيانات فقط (بدون هيكل)
mysqldump -u root -p --no-create-info beza > beza_data.sql

# نسخ جداول محددة
mysqldump -u root -p beza users wallets transactions > beza_core.sql

# نسخ مع ضغط
mysqldump -u root -p beza | gzip > beza_backup.sql.gz

# نسخ مع الوقت الحالي
mysqldump -u root -p beza > "beza_backup_$(date +%Y%m%d_%H%M%S).sql"
```

## الاستعادة (Restore)

```bash
# استعادة كاملة
mysql -u root -p beza < beza_backup.sql

# استعادة من ملف مضغوط
gunzip < beza_backup.sql.gz | mysql -u root -p beza

# استعادة جداول محددة
mysql -u root -p beza < beza_core.sql
```

## النسخ الاحتياطي التلقائي (Windows Script)

```batch
@echo off
set TIMESTAMP=%date:~10,4%%date:~4,2%%date:~7,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set BACKUP_DIR=C:\backups\beza
set DB_USER=root
set DB_PASS=password
set DB_NAME=beza

if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

mysqldump -u %DB_USER% -p%DB_PASS% %DB_NAME% > "%BACKUP_DIR%\beza_%TIMESTAMP%.sql"
echo Backup created: %BACKUP_DIR%\beza_%TIMESTAMP%.sql

:: Delete backups older than 7 days
forfiles -p "%BACKUP_DIR%" -s -m *.sql -d -7 -c "cmd /c del @path"
```

## استراتيجية الاحتفاظ

| الفترة | تكرار النسخ | الاحتفاظ |
|--------|------------|----------|
| يومي | كل يوم | 7 أيام |
| أسبوعي | كل جمعة | 4 أسابيع |
| شهري | أول الشهر | 12 شهراً |
| سنوي | نهاية السنة | 7 سنوات (قانوني) |
