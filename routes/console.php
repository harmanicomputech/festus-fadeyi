<?php

use Illuminate\Support\Facades\Schedule;

// Requires the system cron entry:  * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('ff85:send-scheduled')->everyFiveMinutes()->withoutOverlapping();
