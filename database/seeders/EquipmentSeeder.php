<?php

namespace Database\Seeders;

use App\Models\CustodianUnit;
use App\Models\Equipment;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $registrarId   = CustodianUnit::where('code', 'registrar')->value('id');
        $libraryId     = CustodianUnit::where('code', 'library')->value('id');
        $maintenanceId = CustodianUnit::where('code', 'maintenance')->value('id');
        $comlabId      = CustodianUnit::where('code', 'comlab')->value('id');

        $items = [
            // Registrar items (documents)
            [
                'custodian_unit_id'  => $registrarId,
                'name'               => 'Form 137 (Official Transcript)',
                'category'           => 'Documents',
                'quantity'           => 50,
                'available_quantity' => 50,
                'description'        => 'Official permanent student record / Form 137 copy request.',
            ],
            [
                'custodian_unit_id'  => $registrarId,
                'name'               => 'Certificate of Good Moral Character',
                'category'           => 'Documents',
                'quantity'           => 100,
                'available_quantity' => 100,
                'description'        => 'Official registrar certification of good standing.',
            ],

            // Library items (tables, chairs, extension cords, speakers, mics, projectors)
            [
                'custodian_unit_id'  => $libraryId,
                'name'               => 'LCD Projector',
                'category'           => 'Audio-Visual',
                'quantity'           => 5,
                'available_quantity' => 5,
                'description'        => 'Epson EB-X51 XGA projector with HDMI and VGA input.',
            ],
            [
                'custodian_unit_id'  => $libraryId,
                'name'               => 'Portable Speaker System',
                'category'           => 'Audio-Visual',
                'quantity'           => 3,
                'available_quantity' => 3,
                'description'        => 'JBL PartyBox 310 portable speaker with wireless microphone.',
            ],
            [
                'custodian_unit_id'  => $libraryId,
                'name'               => 'Portable PA System',
                'category'           => 'Audio-Visual',
                'quantity'           => 2,
                'available_quantity' => 2,
                'description'        => 'Portable public address system with 2 wireless microphones.',
            ],
            [
                'custodian_unit_id'  => $libraryId,
                'name'               => 'Extension Cord (10m)',
                'category'           => 'Electrical',
                'quantity'           => 8,
                'available_quantity' => 8,
                'description'        => 'Heavy-duty 10-meter extension cord with surge protection.',
            ],
            [
                'custodian_unit_id'  => $libraryId,
                'name'               => 'Whiteboard (Portable)',
                'category'           => 'Office',
                'quantity'           => 4,
                'available_quantity' => 4,
                'description'        => 'Magnetic whiteboard on wheels (90cm x 120cm).',
            ],
            [
                'custodian_unit_id'  => $libraryId,
                'name'               => 'Conference Table',
                'category'           => 'Furniture',
                'quantity'           => 6,
                'available_quantity' => 6,
                'description'        => 'Foldable 6-foot conference table for events and meetings.',
            ],
            [
                'custodian_unit_id'  => $libraryId,
                'name'               => 'Stackable Monobloc Chairs (Set of 10)',
                'category'           => 'Furniture',
                'quantity'           => 15,
                'available_quantity' => 15,
                'description'        => 'White heavy-duty monobloc chairs in bundles of 10.',
            ],

            // Maintenance items (carpentry and repair tools)
            [
                'custodian_unit_id'  => $maintenanceId,
                'name'               => 'Carpentry Tool Set',
                'category'           => 'Tools',
                'quantity'           => 4,
                'available_quantity' => 4,
                'description'        => 'Comprehensive wood working and carpentry kit with hand tools.',
            ],
            [
                'custodian_unit_id'  => $maintenanceId,
                'name'               => 'Heavy-Duty Hammer & Screwdriver Kit',
                'category'           => 'Tools',
                'quantity'           => 6,
                'available_quantity' => 6,
                'description'        => 'Maintenance tool kit with claw hammers and precision screwdrivers.',
            ],
            [
                'custodian_unit_id'  => $maintenanceId,
                'name'               => 'Electric Drill & Bit Set',
                'category'           => 'Tools',
                'quantity'           => 3,
                'available_quantity' => 3,
                'description'        => 'Cordless impact drill with masonry, wood, and metal drill bits.',
            ],

            // ComLab items (laptops, cameras, tripods, studio lights, LAN cables, HDMI cables)
            [
                'custodian_unit_id'  => $comlabId,
                'name'               => 'Laptop (Windows)',
                'category'           => 'Computing',
                'quantity'           => 10,
                'available_quantity' => 10,
                'description'        => 'Lenovo ThinkPad E14 Gen 5 with i5 processor and 8GB RAM.',
            ],
            [
                'custodian_unit_id'  => $comlabId,
                'name'               => 'DSLR Camera',
                'category'           => 'Photography',
                'quantity'           => 2,
                'available_quantity' => 2,
                'description'        => 'Canon EOS 250D with 18-55mm lens kit for documentation.',
            ],
            [
                'custodian_unit_id'  => $comlabId,
                'name'               => 'Tripod Stand',
                'category'           => 'Photography',
                'quantity'           => 3,
                'available_quantity' => 3,
                'description'        => 'Professional camera tripod, adjustable height up to 170cm.',
            ],
            [
                'custodian_unit_id'  => $comlabId,
                'name'               => 'Studio Light Kit',
                'category'           => 'Photography',
                'quantity'           => 2,
                'available_quantity' => 2,
                'description'        => 'LED softbox studio continuous lighting kit with light stands.',
            ],
            [
                'custodian_unit_id'  => $comlabId,
                'name'               => 'LAN Cable (10m)',
                'category'           => 'Computing',
                'quantity'           => 12,
                'available_quantity' => 12,
                'description'        => 'Cat6 high-speed Ethernet patch cable.',
            ],
            [
                'custodian_unit_id'  => $comlabId,
                'name'               => 'HDMI Cable (5m)',
                'category'           => 'Accessories',
                'quantity'           => 10,
                'available_quantity' => 10,
                'description'        => '5-meter HDMI 2.0 cable for projector/display connections.',
            ],
            [
                'custodian_unit_id'  => $comlabId,
                'name'               => 'Wireless Presenter',
                'category'           => 'Presentation',
                'quantity'           => 5,
                'available_quantity' => 5,
                'description'        => 'Logitech R500s laser pointer with USB receiver.',
            ],
        ];

        foreach ($items as $item) {
            Equipment::firstOrCreate(
                ['name' => $item['name']],
                array_merge($item, ['status' => 'available'])
            );
        }
    }
}
