# Brand Guide

> Single source of truth for Beza Pay visual identity and brand voice.

## Logo

### Primary Logo (Arabic)
- **Type**: Wordmark in Arabic calligraphy style
- **Font**: Custom-designed "Beza" logotype (based on modified Arabic Kufic)
- **Color**: Deep Green `#1B5E20` on light backgrounds
- **Clear space**: Minimum 1x logo height on all sides
- **Minimum size**: 32px height (digital), 15mm (print)

### Secondary Logo (English)
- **Type**: "Beza Pay" in Inter Bold
- **Color**: Deep Green `#1B5E20`
- **Use**: International communications, app store listings

### Icon / App Icon
- **Shape**: Squircle (rounded square, 30% corner radius)
- **Icon**: Simplified "ب" letterform in white on deep green gradient
- **Gradient**: `#1B5E20` → `#2E7D32`
- **Variants**: Standard, monochrome (notification), greyscale (disabled state)

### Logo Usage Rules
- Never stretch, rotate, or distort the logo
- Never change the logo colors
- Never add effects (drop shadows, glows)
- Never place on low-contrast backgrounds
- Always maintain minimum clear space

### Logo Files
```
shared/design-system/assets/logo/
├── beza-logo-primary.svg
├── beza-logo-primary.png (512x512)
├── beza-logo-icon.svg
├── beza-logo-icon.png (1024x1024)
├── beza-logo-icon-monochrome.svg
└── beza-logo-icon-greyscale.svg
```

## Color Palette

### Primary Colors
| Color | Hex | Usage |
|-------|-----|-------|
| Deep Green | `#1B5E20` | Primary brand, buttons, headers |
| Medium Green | `#2E7D32` | Hover states, active tabs |
| Light Green | `#4CAF50` | Success states, positive amounts |
| Green Accent | `#81C784` | Secondary accents |

### Neutral Colors
| Color | Hex | Usage |
|-------|-----|-------|
| Near Black | `#1C1C1E` | Primary text |
| Dark Grey | `#3A3A3C` | Secondary text |
| Medium Grey | `#8E8E93` | Placeholder text, disabled |
| Light Grey | `#C7C7CC` | Borders, dividers |
| Background | `#F2F2F7` | Page backgrounds |
| Surface | `#FFFFFF` | Cards, modals, inputs |

### Semantic Colors
| Color | Hex | Usage |
|-------|-----|-------|
| Success Green | `#34C759` | Confirmation, success states |
| Warning Amber | `#FF9500` | Pending, warning states |
| Error Red | `#FF3B30` | Errors, insufficient balance |
| Info Blue | `#007AFF` | Information, links |

### Color Psychology (Arabic Context)
- **Green** (`#1B5E20`): Trust, growth, prosperity. Deep cultural significance in Islamic finance — associated with paradise, life, and ethical finance.
- **Gold accents**: Used sparingly for premium tiers, loyalty rewards.
- **Avoid red** (`#FF3B30`) for anything except errors — red is associated with loss.
- **Avoid yellow** (`#FFD60A`) in large areas — associated with caution/warning.

### Accessibility
- All color combinations meet WCAG 2.1 AA minimum contrast ratio (4.5:1 for text)
- Color is never the sole indicator of meaning (icons + labels always accompany)
- Dark mode: Inverted palette with `#1C1C1E` as surface, `#FFFFFF` as text

## Typography

### Primary Font (Arabic)
| Property | Value |
|----------|-------|
| Font Family | `Tajawal` |
| Weights | 300 (Light), 400 (Regular), 500 (Medium), 700 (Bold) |
| Fallback | `Noto Naskh Arabic`, `System Default` |
| Use | All Arabic UI text, body copy, headings |

### Secondary Font (English / Numbers)
| Property | Value |
|----------|-------|
| Font Family | `Inter` |
| Weights | 400 (Regular), 500 (Medium), 600 (SemiBold), 700 (Bold) |
| Fallback | `SF Pro`, `Roboto`, `System Default` |
| Use | English text, numbers, currency amounts, code |

### Type Scale
| Level | Size (px/rem) | Weight | Line Height | Use |
|-------|---------------|--------|-------------|-----|
| H1 | 32 / 2rem | Bold 700 | 1.2 | Page titles |
| H2 | 24 / 1.5rem | Bold 700 | 1.3 | Section headers |
| H3 | 20 / 1.25rem | SemiBold 600 | 1.3 | Card titles |
| H4 | 18 / 1.125rem | Medium 500 | 1.4 | Subsection headers |
| Body L | 17 / 1.0625rem | Regular 400 | 1.5 | Primary body text |
| Body M | 15 / 0.9375rem | Regular 400 | 1.5 | Secondary body, inputs |
| Body S | 13 / 0.8125rem | Regular 400 | 1.5 | Captions, helper text |
| Caption | 12 / 0.75rem | Regular 400 | 1.4 | Badges, timestamps |
| Button | 16 / 1rem | Medium 500 | 1.2 | Button labels |

## Iconography

### Icon Library
- **Feather Icons** (modified) for all UI elements
- Custom icons for Beza-specific concepts (wallet, transfer, cash-in/out)
- All icons rendered at 24x24dp (standard) or 20x20dp (compact)

### Icon Design Rules
- **Style**: Outlined, 2px stroke, rounded caps and joins (stroke-linejoin: round)
- **RTL awareness**: Icons that imply direction (arrow, chevron) auto-flip in RTL
- **Color**: Inherits text color by default
- **States**: Default (currentColor), Disabled (opacity 0.38), Error (#FF3B30)
- **Animated icons**: Used only for loading states (spinner, pulse)

### Custom Icons
| Icon Name | Description | Usage |
|-----------|-------------|-------|
| `wallet` | Wallet outline | Wallet screen, balance |
| `transfer-send` | Arrow up right | Send money |
| `transfer-receive` | Arrow down left | Receive money |
| `cash-in` | Money entering building | Agent cash-in |
| `cash-out` | Money leaving building | Agent cash-out |
| `bill` | Receipt/document | Bill payments |
| `kyc` | ID card with shield | KYC verification |
| `agent` | Person with POS machine | Agent locations |
| `sharia` | Crescent/star | Sharia compliance badge |

## Photography & Illustration

### Photography Style
- **Real people**: Authentic Syrian users in natural settings (homes, shops, streets)
- **No stock photos**: All photography is original, commissioned
- **Diversity**: Representation across age, gender, profession within Syrian context
- **Tone**: Warm, natural lighting, candid moments
- **Composition**: Focus on faces and interactions, not devices

### Illustration Style
- **Style**: Flat vector illustrations with organic curves
- **Color palette**: Brand green primary, warm neutrals, gold accents
- **Use cases**: Empty states, error states, onboarding screens, feature illustrations
- **Characters**: Simplified silhouettes, diverse representation, modest attire
- **Cultural elements**: Damascene patterns, arabesque motifs for decorative elements

## Voice and Tone (Arabic)

### Core Principles
| Principle | Arabic | Explanation |
|-----------|--------|-------------|
| Respectful | محترم | Formal address (أنتم not أنت for official comms) |
| Clear | واضح | Short sentences, plain Arabic (no dialect, no fus-ha extreme) |
| Supportive | داعم | Encouraging, never blaming. "حاول مرة أخرى" not "لقد أخطأت" |
| Transparent | شفاف | Honest about fees, limits, delays. No fine print traps. |

### Tone by Channel
| Channel | Tone | Example |
|---------|------|---------|
| App UI | Direct, concise | "تم التحويل بنجاح" |
| Push notification | Urgent, personal | "لقد استلمت 50,000 ل.س من أحمد" |
| SMS | Functional, clear | "رمز التحقق: 123456" |
| Email | Warm, detailed | "عزيزي المستخدم، نشكرك على ثقتك..." |
| Error message | Helpful, not technical | "عذراً، الرصيد غير كافٍ" (not "Error 402") |
| Marketing | Enthusiastic, benefit-focused | "حوّل أموالك بأمان وسرعة مع بيزا" |

### Do / Don't
| Do (Arabic) | Don't |
|-------------|-------|
| استخدام اللغة العربية الفصحى المبسطة | استخدام اللهجات العامية |
| استخدام أنتم للمخاطبة الرسمية | استخدام أنت في المراسلات الرسمية |
| ذكر المبلغ والرسوم بوضوح | إخفاء الرسوم في الحواشي |
| تقديم الشكر والاعتذار عند الخطأ | إلقاء اللوم على المستخدم |
| استخدام مصطلحات مالية إسلامية | استخدام مصطلحات ربوية |

### Number Formatting
- Arabic-Indic digits (`٠١٢٣٤٥٦٧٨٩`) for all amounts and counts
- Thousands separator: comma `,` (rendered as `٬` in Arabic context)
- Decimal separator: period `.`
- Currency: `ل.س` (SYP) after amount with space
- Example: `١٬٥٠٠٬٠٠٠ ل.س`
