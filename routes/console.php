<?php

use App\Jobs\SendBirthdayGreetings;
use App\Jobs\SendEventReminders;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('family-tree:about', function (): void {
    $this->info('Family Tree Platform Indonesia');
})->purpose('Display project information');

Schedule::job(new SendEventReminders)->hourly()->withoutOverlapping();
Schedule::job(new SendBirthdayGreetings)->dailyAt('07:00')->withoutOverlapping();
Schedule::command('refresh-tokens:prune')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('tree-exports:prune')->dailyAt('03:30')->withoutOverlapping();
Schedule::command('backup:run --only-db')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('backup:clean')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('backup:monitor')->hourly()->withoutOverlapping();
Schedule::command('horizon:snapshot')->everyFiveMinutes();
Schedule::command('telescope:prune --hours=48')->daily()->withoutOverlapping();
