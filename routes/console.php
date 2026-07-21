<?php

use App\Jobs\SendEventReminders;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('family-tree:about', function (): void {
    $this->info('Family Tree Platform Indonesia');
})->purpose('Display project information');

Schedule::job(new SendEventReminders)->hourly()->withoutOverlapping();
