<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('struk:index')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

    
Schedule::command('sqlite:maintenance')
    ->monthlyOn(1, '02:00')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('struk:build-content-index')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('struk:cleanup-db')
    ->yearlyOn(1, 1, '00:05')
    ->withoutOverlapping()
    ->runInBackground();
