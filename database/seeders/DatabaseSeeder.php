<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ArticleCategorySeeder::class);

        if (app()->environment(['local', 'testing'])) {
            $this->call(DemoFamilySeeder::class);
        }
    }
}
