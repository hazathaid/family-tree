<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('family-tree:about', function (): void {
    $this->info('Family Tree Platform Indonesia');
})->purpose('Display project information');
