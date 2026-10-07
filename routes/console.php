<?php

use Illuminate\Support\Facades\Schedule;

// Shared hosting has no supervisor: cron runs schedule:run every minute, and this drains
// the queue (inbox LLM calls, webhook deliveries) within that minute.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();
