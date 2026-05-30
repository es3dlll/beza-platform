# Offline-First Design — Beza (بزة)

## Syrian Connectivity Reality

### Connectivity Tiers

| Tier | Description | Latency | Uptime | Locations |
|------|-------------|---------|--------|-----------|
| Tier 1 | Damascus/Aleppo 4G | 30-80ms | 85% | City centers: Damascus (دمشق), Aleppo (حلب), Latakia (اللاذقية), Tartus (طرطوس) |
| Tier 2 | Town 3G | 100-300ms | 60% | Suburbs, medium towns: Homs (حمص), Hama (حماة), Deir ez-Zor (دير الزور), Hasakeh (الحسكة) |
| Tier 3 | Rural EDGE/2G | 500-2000ms | 40% | Villages, IDP camps, rural agricultural areas |
| Tier 4 | No signal | N/A | 10% | Remote areas, some rural villages, areas with damaged infrastructure |

### Key Constraints
- **Frequent dropouts**: Connection can drop mid-transaction
- **Data cost**: 1GB costs ~15,000 SYP (significant for average user)
- **Power**: Users may be on battery saving mode, background sync limited
- **Device range**: Android 8+ on low-end devices (2-3GB RAM, MediaTek Helio)
- **Storage**: 16-32GB devices, app must use < 100MB for offline data

## Offline Strategy by Screen

### Home Screen
- **Balance**: Last known balance displayed from SQLite cache
  - "رصيدك: ١٬٢٠٠٬٠٠٠ ل.س (آخر تحديث: 10:30)"
  - Timestamp in red if > 1 hour stale
- **Quick actions**: Send, Pay, Top-up — all navigate to cached versions
- **Announcement banner**: Cached from last successful fetch

### Transactions History
- Last 50 transactions cached locally (SQLite)
- Available offline: full transaction list with details
- Transaction details include all cached data (amount, date, recipient, reference)
- "Pending" transactions shown with orange indicator
- Offline banner: "قد لا تكون المعاملات حديثة. آخر تحديث: 10:30"

### Money Transfer
- **Recipient list**: Cached contacts and recent recipients
- **Send flow**: Full flow available offline up to confirmation
  - Select recipient (from cache)
  - Enter amount
  - Review
  - Authenticate (PIN/biometric — local)
  - Transaction queued with status "قيد الإرسال" (Pending Send)
  - Queue position shown: "سيتم الإرسال تلقائياً عند توفر الإنترنت"
- **Queue limits**: Max 10 pending outgoing transactions

### Agent List
- Full agent list cached (up to 500 agents)
- Cache refreshes every 24 hours
- Fields cached: name_ar, name_en, lat, lng, phone, distance, rating, is_open, address
- Map tiles cached for last viewed area (500×500m tiles, 3 zoom levels)
- Warning when stale: "بيانات الوكلاء من: 28 مايو 2026"
- "Update" button that fetches fresh data if connectivity available

### Bill Payment
- Biller list cached (7 days max age)
- Fetch bill: requires connectivity (amount varies by usage)
  - Offline: "يلزم الاتصال بالإنترنت لجلب قيمة الفاتورة"
  - Queued: "سنقوم بجلب الفاتورة عند توفر الإنترنت"
- Previously fetched bills cached for 24 hours
- Pay bill: requires connectivity for final execution

### QR Code Generation
- Static QR codes (user ID, merchant ID) generated offline
- Dynamic QR codes (transaction-specific) cached when generated
- QR scanner: works fully offline (pattern matching is local)
- All QR code assets bundled with app

### USSD
- Always available even when data is down
- USSD works on Tier 3 and Tier 4 (GSM signal only)
- App can trigger USSD codes via Android `ACTION_CALL` with `tel:*123*1%23`
- USSD responses parsed locally; results cached in app history
- Fallback message: "استخدم الكود *123#  لباقي الخدمات"

## Sync Strategy

### Background Sync
- Triggered when connectivity is restored (via `ConnectivityManager` / `NWPathMonitor`)
- Check interval: every 5 minutes when app is foreground, every 15 minutes background
- Battery-aware: skips sync if battery < 20% unless pending financial transactions
- Data-aware: skips sync if on metered connection and data < 100MB remaining

### Sync Queue Priority
| Priority | Type | Max Queue | Behavior |
|----------|------|-----------|----------|
| P1 | Financial transactions | 10 | Sent first, retry 5 times (exponential backoff: 30s, 1m, 2m, 4m, 8m) |
| P2 | Non-financial (bill fetch, balance refresh) | 20 | Sent after P1, retry 3 times |
| P3 | Analytics events | 100 | Batch-sent, no retry |
| P4 | Cache refresh (agent list, biller list) | 5 | Sent last, when on WiFi |

### Optimistic UI
- Outgoing transaction appears immediately in transaction list
- Status: "قيد الإرسال" (Pending) with orange badge
- On sync success: status changes to "تمت" (Completed), badge turns green
- On sync failure: status changes to "فشل" (Failed), badge turns red, error message shown
- User can retry failed transactions from transaction detail screen

### Conflict Resolution
- **Server wins** for all financial conflicts
- Conflict scenarios:
  1. **Stale balance**: Server balance differs from cached → server balance replaces cached, user notified
  2. **Duplicate transaction**: Server detects duplicate (same amount + recipient + timestamp) → marks one as duplicate, notifies user
  3. **Recipient changed**: Server recipient data differs → syncs latest, notifies user
- User notification for conflicts: center modal with details

### Sync Progress Indicator
- During sync: subtle icon in app bar (animated up-down arrows)
- On sync complete: brief toast "تمت المزامنة" (Sync complete) — 3s
- On sync error: amber banner "تعذرت المزامنة" with retry
- Pull-to-refresh always triggers manual sync attempt

## Offline Resource Budget

| Resource | Cache Size | Max Age | Storage Location |
|----------|-----------|---------|-----------------|
| Last 50 transactions | ~50KB | 30 days | SQLite (transactions table) |
| Agent list (500 agents) | ~200KB | 24 hours | SQLite (agents table) |
| Biller list (50 billers) | ~50KB | 7 days | SQLite (billers table) |
| FX rates (30 pairs) | ~5KB | 30 minutes | SQLite (fx_rates table) |
| User profile | ~2KB | 7 days | SQLite (profile table) |
| Map tiles (last area) | ~2MB | 24 hours | File cache (tiles/) |
| QR code assets | ~100KB | App version | Bundled or file cache |
| Static assets (logos, flags) | ~500KB | App version | Bundled assets |
| USSD history | ~20KB | 7 days | SQLite (ussd_log table) |
| Pending transaction queue | ~2KB | Until synced | SQLite (pending_queue table) |

### SQLite Schema (Offline DB)
- Tables: `transactions`, `agents`, `billers`, `fx_rates`, `profile`, `beneficiaries`, `pending_queue`, `ussd_log`, `analytics_events`
- All tables have `last_synced_at` timestamp
- `pending_queue` has `retry_count` and `next_retry_at` for exponential backoff
- WAL mode enabled for concurrent read/write

### Cache Eviction
- Least Recently Used (LRU) for file cache (map tiles)
- Oldest-first for transaction history (keep 50 most recent)
- 7-day TTL for non-critical cached data
- On storage warning (< 500MB free): reduce agent cache to 200, transaction cache to 25
- App uninstall clears all cached data

## Offline Detection

### Connectivity Status States
1. **Online**: Connected to internet, latency < 500ms, ready for real-time operations
2. **Limited**: Connected but high latency (Tier 2/3). Real-time operations may timeout. Use cached data with background sync
3. **Offline**: No connectivity. Full offline mode with queuing

### Detection Method
- Primary: `ConnectivityManager.getActiveNetwork()` + `NetworkCapabilities` check
- Secondary: Actual HTTP HEAD request to `https://api.beza.app/ping` (timeout 5s)
- Fallback: If primary indicates online but HEAD fails → show "Limited" state
- Poll interval: 5 seconds when state is "Limited" or "Offline"

### State Transitions
- **Online → Limited**: Latency exceeds threshold or ping fails → show amber banner
- **Limited → Offline**: No response for 3 consecutive pings → show red offline banner (offline mode)
- **Offline → Online**: Successful ping → clear banner, sync queue, show success toast
- **Limited → Online**: Latency back to normal → clear banner silently

## Offline UI Components

### Connectivity Banner
```
┌─────────────────────────────────────┐
│ ⚠ أنت غير متصل. آخر تحديث: 10:30   │
│                        [إعادة المحاولة] │
└─────────────────────────────────────┘
```
- Top sticky banner below app bar
- Amber for "Limited", red for "Offline"
- Dismissible only when back to "Online"
- Retry button triggers immediate connectivity check

### Pending Transaction Indicator
```
┌─ Pending Transactions ───────────────────┐
│ → ٢٥٬٠٠٠ ل.س إلى محمد                     │
│   قيد الإرسال...      [إلغاء]      │
│ → ١٠٬٠٠٠ ل.س إلى أحمد                     │
│   بالإنتظار          [إلغاء]      │
└────────────────────────────────────────┘
```
- Shows in transaction list with orange "قيد الإرسال" badge
- User can cancel pending transaction before it syncs
- When transaction syncs: badge turns green, moves to history
