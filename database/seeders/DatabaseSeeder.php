<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
       $this->call(DefaultPermissions::class);
       $this->call(DemoDataSeeder::class);
       $this->call(LanguagesSeeder::class);
       $this->call(LocationSeeder::class);
       $this->call(IatiPublishersSeeder::class);
       $this->call(IatiProjectsSeeder::class);
    }
}
