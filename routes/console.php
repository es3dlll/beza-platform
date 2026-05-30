<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('ledger:close-daily')->dailyAt('23:55');
Schedule::command('settlement:process-daily')->dailyAt('23:50');
Schedule::command('savings:distribute-profits')->lastDayOfMonth('23:00');
Schedule::command('savings:auto-sweep')->hourly();
Schedule::command('fraud:refresh-cache')->hourly();
Schedule::command('agent:refresh-liquidity')->everySixHours();
Schedule::command('cleanup:expired-otps')->everyFiveMinutes();
Schedule::command('cleanup:expired-sessions')->hourly();
Schedule::command('fx:clean-expired-quotes')->everyFiveMinutes();
Schedule::command('cleanup:expired-holds')->everyTenMinutes();
Schedule::command('loyalty:recalculate-tiers')->daily();
