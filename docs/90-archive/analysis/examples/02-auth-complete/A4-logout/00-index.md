# ÙÙ‡Ø±Ø³ - A4: ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø®Ø±ÙˆØ¬ (Logout)

```
A4-logout/
â”œâ”€â”€ 00-index.md                     â† Ø£Ù†Øª Ù‡Ù†Ø§
â”œâ”€â”€ 01-business-idea.md             # ÙÙƒØ±Ø© Ø§Ù„Ø¹Ù…Ù„ ÙˆØ³ÙŠÙ†Ø§Ø±ÙŠÙˆ Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù…
â”œâ”€â”€ 02-architecture.md              # Ù…ÙƒØ§Ù† Ø§Ù„Ø¹Ù…Ù„ÙŠØ© ÙÙŠ Ø§Ù„Ù†Ø¸Ø§Ù…
â”œâ”€â”€ 03-data-flow-sequence.md        # ØªØ¯ÙÙ‚ Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ù„ÙƒØ§Ù…Ù„ (Sequence Diagram)
â”œâ”€â”€ 04-database-relationships.md    # Ø¹Ù„Ø§Ù‚Ø§Øª Ø§Ù„Ø¬Ø¯Ø§ÙˆÙ„ + ER
â”œâ”€â”€ 05-migrations.md                # ÙƒÙˆØ¯ Ø§Ù„Ù…ÙŠØºØ±ÙŠØ´Ù† Ø§Ù„ÙƒØ§Ù…Ù„
â”œâ”€â”€ 06-eloquent-models.md           # Ø§Ù„Ù…ÙˆØ¯ÙŠÙ„Ø² Ù…Ø¹ Ø§Ù„Ø¹Ù„Ø§Ù‚Ø§Øª ÙˆØ§Ù„ casts
â”œâ”€â”€ 07-validation-rules.md          # ÙƒÙ„ Ù‚ÙˆØ§Ø¹Ø¯ Ø§Ù„ØªØ­Ù‚Ù‚ + Ø£Ø³Ø¨Ø§Ø¨Ù‡Ø§
â”œâ”€â”€ 08-controller-full-code.md      # Ø§Ù„Ù…ØªØ­ÙƒÙ… Ø§Ù„ÙƒØ§Ù…Ù„ Ù…Ø¹ ÙƒÙ„ Ø³Ø·Ø±
â”œâ”€â”€ 09-service-layer.md             # Ø³ÙŠØ±ÙØ³ Ù„ÙŠØ± Ø§Ù„Ø¹Ù…Ù„ÙŠØ©
â”œâ”€â”€ 10-auth-guards-middleware.md    # Ø§Ù„Ù…ØµØ§Ø¯Ù‚Ø© ÙˆØ§Ù„ØµÙ„Ø§Ø­ÙŠØ§Øª
â”œâ”€â”€ 11-events-and-listeners.md      # Ø§Ù„Ø£Ø­Ø¯Ø§Ø« ÙˆØ§Ù„Ù…Ø³ØªÙ…Ø¹ÙŠÙ†
â”œâ”€â”€ 12-notification-system.md       # FCM + SMS + Email
â”œâ”€â”€ 13-exception-handling.md        # ÙƒÙ„ Ø§Ù„Ø§Ø³ØªØ«Ù†Ø§Ø¡Ø§Øª ÙˆÙ…Ø¹Ø§Ù„Ø¬ØªÙ‡Ø§
â”œâ”€â”€ 14-rate-limiting-brute-force.md # Ù…Ù†Ø¹ Ø§Ù„Ù‡Ø¬Ù…Ø§Øª
â”œâ”€â”€ 15-api-specification.md         # OpenAPI / Postman ÙƒØ§Ù…Ù„
â”œâ”€â”€ 16-flutter-implementation.md    # Flutter UI + BLoC + Repository
â”œâ”€â”€ 17-react-implementation.md      # React UI + Hooks + API
â”œâ”€â”€ 18-testing-complete.md          # ÙƒÙ„ Ø§Ù„Ø§Ø®ØªØ¨Ø§Ø±Ø§Øª
â”œâ”€â”€ 19-edge-cases.md                # Ø­Ø§Ù„Ø§Øª Ø§Ù„Ø­Ø§ÙØ©
â””â”€â”€ 20-security-audit.md            # Ø£Ù…Ø§Ù† Ø§Ù„Ø¹Ù…Ù„ÙŠØ© Ø®Ø·ÙˆØ© Ø¨Ø®Ø·ÙˆØ©
```

## Ù…Ù„Ø®Øµ Ø§Ù„Ø¹Ù…Ù„ÙŠØ©
| Ø§Ù„Ø¹Ù†ØµØ± | Ø§Ù„Ù‚ÙŠÙ…Ø© |
|--------|--------|
| Ø§Ø³Ù… Ø§Ù„Ø¹Ù…Ù„ÙŠØ© | ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø®Ø±ÙˆØ¬ |
| Ø§Ù„Ø£ÙˆÙ„ÙˆÙŠØ© | P1 (Ù…Ù‡Ù…) |
| API | `POST /api/v1/auth/logout` |
| Controller | `AuthController@logout` |
| Service | `AuthService` |
| DB Tables | token_blacklist (JWT) |
| Ø¢Ù„ÙŠØ© Ø§Ù„Ø¥Ø¨Ø·Ø§Ù„ | Ø­Ø°Ù Ø§Ù„ØªÙˆÙƒÙ† Ø§Ù„Ø­Ø§Ù„ÙŠ ÙÙ‚Ø· |
| Flutter Screen | â€” (ÙŠØªÙ… Ù…Ù† Ø§Ù„Ø¥Ø¹Ø¯Ø§Ø¯Ø§Øª) |
| React Page | â€” (ÙŠØªÙ… Ù…Ù† Ø§Ù„Ù‡ÙŠØ¯Ø±) |
