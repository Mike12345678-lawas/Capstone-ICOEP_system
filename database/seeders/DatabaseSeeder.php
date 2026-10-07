<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            DepartmentSeeder::class,   // must run before UserSeeder (FK dependency)
            UserSeeder::class,
            FacilitySeeder::class,
            FacilityReservationSeeder::class,
            EquipmentSeeder::class,
            EventSeeder::class,
            AnnouncementSeeder::class,
        ]);
    }
}
