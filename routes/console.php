<?php

use Illuminate\Support\Facades\Schedule;

// Server cron runs `php artisan schedule:run` every minute (deploy/aws/setup.sh).
Schedule::command('data:prune')->dailyAt('02:30')->withoutOverlapping()->onOneServer();
