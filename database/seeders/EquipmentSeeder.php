<?php

namespace Database\Seeders;

use App\Models\Equipment;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'LCD Projector',            'category' => 'Audio-Visual',     'quantity' => 5,  'available_quantity' => 5,  'description' => 'Epson EB-X51 XGA projector with HDMI and VGA input.'],
            ['name' => 'Portable Speaker System',  'category' => 'Audio-Visual',     'quantity' => 3,  'available_quantity' => 3,  'description' => 'JBL PartyBox 310 portable speaker with wireless microphone.'],
            ['name' => 'DSLR Camera',              'category' => 'Photography',      'quantity' => 2,  'available_quantity' => 2,  'description' => 'Canon EOS 250D with 18-55mm lens kit for documentation.'],
            ['name' => 'Laptop (Windows)',          'category' => 'Computing',        'quantity' => 10, 'available_quantity' => 10, 'description' => 'Lenovo ThinkPad E14 Gen 5 with i5 processor and 8GB RAM.'],
            ['name' => 'Wireless Presenter',       'category' => 'Presentation',     'quantity' => 5,  'available_quantity' => 5,  'description' => 'Logitech R500s laser pointer with USB receiver.'],
            ['name' => 'Extension Cord (10m)',      'category' => 'Electrical',       'quantity' => 8,  'available_quantity' => 8,  'description' => 'Heavy-duty 10-meter extension cord with surge protection.'],
            ['name' => 'Whiteboard (Portable)',     'category' => 'Office',           'quantity' => 4,  'available_quantity' => 4,  'description' => 'Magnetic whiteboard on wheels (90cm x 120cm).'],
            ['name' => 'Tripod Stand',             'category' => 'Photography',      'quantity' => 3,  'available_quantity' => 3,  'description' => 'Professional camera tripod, adjustable height up to 170cm.'],
            ['name' => 'HDMI Cable (5m)',           'category' => 'Accessories',      'quantity' => 10, 'available_quantity' => 10, 'description' => '5-meter HDMI 2.0 cable for projector/display connections.'],
            ['name' => 'Portable PA System',        'category' => 'Audio-Visual',     'quantity' => 2,  'available_quantity' => 2,  'description' => 'Portable public address system with 2 wireless microphones.'],
        ];

        foreach ($items as $item) {
            Equipment::create(array_merge($item, ['status' => 'available']));
        }
    }
}
