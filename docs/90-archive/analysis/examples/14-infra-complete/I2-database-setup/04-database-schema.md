# 04 - هيكل جميع الجداول (Database Schema)

## قائمة الجداول والوصف

| # | الجدول | الحقول | الغرض |
|---|--------|--------|-------|
| 1 | users | id, uuid, name, email, phone, password, pin_code, status, kyc_status, is_admin, is_merchant, is_agent, device_id, fcm_token, last_login_ip, last_login_at, preferences, timestamps | حسابات المستخدمين |
| 2 | wallets | id, user_id, currency, wallet_number, balance, frozen_balance, is_active, timestamps | محافظ العملات |
| 3 | transactions | id, from_wallet_id, to_wallet_id, amount, amount_in_usd, type, status, reference_number, description, fee, metadata, completed_at, timestamps | جميع المعاملات |
| 4 | merchants | id, user_id, business_name, business_type, commercial_registration, tax_number, status, timestamps | حسابات التجار |
| 5 | merchant_products | id, merchant_id, name, description, price, currency, image, is_active, timestamps | منتجات التجار |
| 6 | merchant_orders | id, user_id, merchant_id, product_id, quantity, total, status, timestamps | طلبات المتجر |
| 7 | deals | id, title, description, target_amount, current_amount, roi_percentage, duration_days, status, start_date, end_date, timestamps | الصفقات الاستثمارية |
| 8 | deal_investments | id, deal_id, user_id, amount, expected_profit, status, timestamps | استثمارات الصفقات |
| 9 | cards | id, user_id, card_number, card_type, expiry_date, cvv, status, balance, card_holder, timestamps | البطاقات |
| 10 | card_transactions | id, card_id, amount, type, merchant, status, reference, timestamps | معاملات البطاقات |
| 11 | agents | id, user_id, agent_code, commission_rate, balance, status, timestamps | الوكلاء |
| 12 | agent_transactions | id, agent_id, user_id, type, amount, code, status, expires_at, timestamps | معاملات الوكلاء |
| 13 | referrals | id, referrer_id, referred_id, code, reward_amount, status, timestamps | نظام الإحالة |
| 14 | kyc_documents | id, user_id, document_type, document_number, front_image, back_image, status, notes, timestamps | وثائق KYC |
| 15 | disputes | id, user_id, transaction_id, subject, description, status, resolution, timestamps | النزاعات |
| 16 | settings | id, key, value, group, description, timestamps | إعدادات النظام |
| 17 | notifications | id, type, notifiable_type, notifiable_id, data, read_at, timestamps | الإشعارات |
| 18 | audit_logs | id, event_type, loggable_type, loggable_id, user_id, data, ip, user_agent, timestamps | سجل التدقيق |
| 19 | jobs | id, queue, payload, attempts, reserved_at, available_at, created_at | وظائف قائمة الانتظار |
| 20 | failed_jobs | id, uuid, connection, queue, payload, exception, failed_at | الوظائف الفاشلة |
