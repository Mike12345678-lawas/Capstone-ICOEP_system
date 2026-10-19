<?php

namespace Database\Seeders;

use App\Models\CustodianUnit;
use Illuminate\Database\Seeder;

class CustodianUnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'registrar',   'name' => 'Registrar'],
            ['code' => 'library',     'name' => 'Library'],
            ['code' => 'maintenance', 'name' => 'Maintenance'],
            ['code' => 'comlab',      'name' => 'ComLab'],
        ];

        foreach ($units as $unit) {
            CustodianUnit::firstOrCreate(['code' => $unit['code']], $unit);
        }
    }
}
