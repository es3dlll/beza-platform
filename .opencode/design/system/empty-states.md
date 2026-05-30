# Empty States — Beza (بزة)

## Empty State Design Rules
- **Illustration**: Custom vector illustration per screen, theme-aware (light/dark mode), centered above text
- **Title**: Bold, 20sp Arabic / 16sp English subtitle in lighter weight
- **Message**: 14sp, secondary text color, max 2 lines
- **CTA**: Primary button, full-width with 16dp horizontal margin
- **Spacing**: 48dp above illustration, 24dp between elements

## Empty States Reference Table

### Home — No Balance Yet
| Field | Value |
|-------|-------|
| Title (AR) | مرحباً بك في بزة |
| Title (EN) | Welcome to Beza |
| Message | حول أول مبلغ للبدء باستخدام بزة |
| CTA | اشحن الآن |

### Transactions — No Transactions
| Field | Value |
|-------|-------|
| Title (AR) | لا توجد معاملات |
| Title (EN) | No transactions yet |
| Message | معاملاتك المالية ستظهر هنا بعد أول تحويل |
| CTA | حول الآن |

### Agents — No Agents Near You
| Field | Value |
|-------|-------|
| Title (AR) | لا يوجد وكلاء قريبين |
| Title (EN) | No agents nearby |
| Message | جرب البحث في منطقة أخرى أو أعد تحديث الموقع |
| CTA | تحديث الموقع |

### Bills — No Bills
| Field | Value |
|-------|-------|
| Title (AR) | لا توجد فواتير |
| Title (EN) | No bills yet |
| Message | لم تقم بأي دفع فواتير حتى الآن |
| CTA | ادفع فاتورة |

### Beneficiaries — No Beneficiaries
| Field | Value |
|-------|-------|
| Title (AR) | لا يوجد مستفيدون |
| Title (EN) | No beneficiaries |
| Message | أضف مستفيداً لتتمكن من التحويل له بسرعة |
| CTA | إضافة مستفيد |

### Notifications — No Notifications
| Field | Value |
|-------|-------|
| Title (AR) | لا توجد إشعارات |
| Title (EN) | No notifications |
| Message | الإشعارات الجديدة ستظهر هنا تلقائياً |
| CTA | رجوع |

### Savings — No Savings Goals
| Field | Value |
|-------|-------|
| Title (AR) | لا توجد أهداف ادخارية |
| Title (EN) | No savings goals |
| Message | ابدأ بالادخار لمستقبلك. حدد هدفاً وابدأ التوفير |
| CTA | إنشاء هدف |

### Cards — No Cards
| Field | Value |
|-------|-------|
| Title (AR) | لا توجد بطاقات |
| Title (EN) | No cards yet |
| Message | اطلب بطاقة بزة للدفع في المتاجر وسحب النقود |
| CTA | طلب بطاقة |

### Search — No Results
| Field | Value |
|-------|-------|
| Title (AR) | لا توجد نتائج |
| Title (EN) | No results found |
| Message | لم نجد نتائج مطابقة. حاول بكلمات بحث مختلفة |
| CTA | بحث جديد |

### Error — Network Error
| Field | Value |
|-------|-------|
| Title (AR) | تعذر الاتصال بالخادم |
| Title (EN) | Connection failed |
| Message | تحقق من اتصالك بالإنترنت وحاول مرة أخرى |
| CTA | إعادة المحاولة |

### Error — Server Error
| Field | Value |
|-------|-------|
| Title (AR) | حدث خطأ |
| Title (EN) | Something went wrong |
| Message | الرجاء المحاولة لاحقاً. إذا استمرت المشكلة اتصل بالدعم الفني |
| CTA | إعادة المحاولة |

### Error — Session Expired
| Field | Value |
|-------|-------|
| Title (AR) | انتهت الجلسة |
| Title (EN) | Session expired |
| Message | تم تسجيل الخروج تلقائياً. الرجاء تسجيل الدخول مجدداً |
| CTA | تسجيل الدخول |

### Error — Maintenance
| Field | Value |
|-------|-------|
| Title (AR) | الخدمة قيد الصيانة |
| Title (EN) | Under maintenance |
| Message | سنعود قريباً. حاول بعد قليل أو استخدم الكود *123# |
| CTA | استخدام USSD |

## Empty State Component Props

| Prop | Type | Default | Notes |
|------|------|---------|-------|
| illustration | String | — | Asset name from illustrations library |
| titleAr | String | — | Arabic title text |
| titleEn | String | — | English title text |
| messageAr | String | — | Arabic message body |
| messageEn | String | — | English message body |
| ctaText | String | — | Button label |
| ctaAction | Function | — | Button onPress handler |
| secondaryCta | String | Optional | Secondary link text (e.g., "معرفة المزيد") |
| errorType | Enum | null | Used for error-specific styling |
