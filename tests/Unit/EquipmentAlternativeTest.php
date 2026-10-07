<?php

namespace Tests\Unit;

use App\Models\Equipment;
use App\Models\EquipmentRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AlternativeSuggester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EquipmentAlternativeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed standard roles if not present
        Role::firstOrCreate(['name' => 'Student'], ['description' => 'Student user']);
    }

    /**
     * Helper to create an equipment item with test defaults.
     */
    protected function createEquip(array $overrides = []): Equipment
    {
        return Equipment::create(array_merge([
            'name'               => 'Test Equipment',
            'category'           => 'Electrical',
            'description'        => 'Test description',
            'quantity'           => 3,
            'available_quantity' => 3,
            'status'             => 'available',
        ], $overrides));
    }

    /**
     * 1. Functional equivalence detector accurately matches items with the same function
     * and rejects items with different functions.
     */
    public function test_has_same_function_identifies_equivalent_equipment(): void
    {
        $ext5m = $this->createEquip(['name' => 'Extension Cord (5m)', 'category' => 'Electrical']);
        $ext10m = $this->createEquip(['name' => 'Extension Cord (10m)', 'category' => 'Electrical']);
        $ext5Meters = $this->createEquip(['name' => 'Extension 5 meters', 'category' => 'Electrical']);
        $ext10Meters = $this->createEquip(['name' => 'Extension 10 meters', 'category' => 'Electrical']);

        $projector = $this->createEquip(['name' => 'LCD Projector', 'category' => 'Audio-Visual']);
        $dlpProjector = $this->createEquip(['name' => 'DLP Projector', 'category' => 'Audio-Visual']);
        $speaker = $this->createEquip(['name' => 'Portable Speaker System', 'category' => 'Audio-Visual']);
        $paSystem = $this->createEquip(['name' => 'Portable PA System', 'category' => 'Audio-Visual']);

        $camera = $this->createEquip(['name' => 'DSLR Camera', 'category' => 'Photography']);
        $tripod = $this->createEquip(['name' => 'Tripod Stand', 'category' => 'Photography']);
        $laptop = $this->createEquip(['name' => 'Laptop (Windows)', 'category' => 'Computing']);
        $whiteboard = $this->createEquip(['name' => 'Whiteboard (Portable)', 'category' => 'Office']);

        // Same function matches:
        $this->assertTrue(AlternativeSuggester::hasSameFunction($ext5m, $ext10m));
        $this->assertTrue(AlternativeSuggester::hasSameFunction($ext5m, $ext10Meters));
        $this->assertTrue(AlternativeSuggester::hasSameFunction($ext5Meters, $ext10Meters));
        $this->assertTrue(AlternativeSuggester::hasSameFunction($projector, $dlpProjector));
        $this->assertTrue(AlternativeSuggester::hasSameFunction($speaker, $paSystem));

        // Different function non-matches:
        $this->assertFalse(AlternativeSuggester::hasSameFunction($ext5m, $projector));
        $this->assertFalse(AlternativeSuggester::hasSameFunction($projector, $speaker));
        $this->assertFalse(AlternativeSuggester::hasSameFunction($projector, $paSystem));
        $this->assertFalse(AlternativeSuggester::hasSameFunction($camera, $tripod));
        $this->assertFalse(AlternativeSuggester::hasSameFunction($laptop, $whiteboard));
        $this->assertFalse(AlternativeSuggester::hasSameFunction($ext5m, $ext5m), 'An item should not match itself');
    }

    /**
     * 2. When requested equipment (e.g. Extension Cord 5m) is not available,
     * it suggests the available same-function substitute (e.g. Extension Cord 10m),
     * and NEVER suggests unrelated items from other categories.
     */
    public function test_suggests_10m_extension_for_5m_extension_and_no_unrelated_items(): void
    {
        $dateNeeded = Carbon::tomorrow()->toDateString();
        $expectedReturn = Carbon::tomorrow()->addDays(2)->toDateString();

        // 5m cord is out of stock (available_quantity = 0)
        $ext5m = $this->createEquip([
            'name'               => 'Extension Cord (5m)',
            'category'           => 'Electrical',
            'quantity'           => 2,
            'available_quantity' => 0,
        ]);

        // 10m cord is available with sufficient stock
        $ext10m = $this->createEquip([
            'name'               => 'Extension Cord (10m)',
            'category'           => 'Electrical',
            'quantity'           => 5,
            'available_quantity' => 5,
        ]);

        // Unrelated inventory items
        $projector = $this->createEquip(['name' => 'LCD Projector', 'category' => 'Audio-Visual']);
        $camera = $this->createEquip(['name' => 'DSLR Camera', 'category' => 'Photography']);
        $whiteboard = $this->createEquip(['name' => 'Whiteboard (Portable)', 'category' => 'Office']);

        $suggestions = AlternativeSuggester::suggestAlternateEquipment($ext5m, 1, $dateNeeded, $expectedReturn);

        $this->assertNotEmpty($suggestions);

        // Extension Cord (10m) must be suggested
        $altItemIds = collect($suggestions)
            ->where('type', 'alternate_equipment')
            ->pluck('equipment_id')
            ->all();

        $this->assertContains($ext10m->id, $altItemIds, 'Expected Extension Cord (10m) to be suggested as alternate equipment.');

        // Unrelated items must NOT be suggested
        $this->assertNotContains($projector->id, $altItemIds, 'Projector must NOT be suggested for an extension cord request.');
        $this->assertNotContains($camera->id, $altItemIds, 'Camera must NOT be suggested for an extension cord request.');
        $this->assertNotContains($whiteboard->id, $altItemIds, 'Whiteboard must NOT be suggested for an extension cord request.');
    }

    /**
     * 3. When NO same-function equipment is available, it suggests AVAILABLE TIME (future borrow dates)
     * for the requested equipment and NEVER suggests unrelated items.
     */
    public function test_suggests_available_time_when_no_same_function_equipment_exists(): void
    {
        $today = Carbon::today();
        $dateNeeded = $today->copy()->addDay()->toDateString();
        $expectedReturn = $today->copy()->addDays(3)->toDateString();

        $user = User::create([
            'first_name' => 'Test',
            'last_name'  => 'User',
            'email'      => 'testborrower@example.com',
            'password'   => bcrypt('password'),
            'role_id'    => Role::where('name', 'Student')->first()->id,
            'status'     => 'active',
        ]);

        // Only one extension cord model exists in the school inventory (quantity: 1)
        $ext5m = $this->createEquip([
            'name'               => 'Extension Cord (5m)',
            'category'           => 'Electrical',
            'quantity'           => 1,
            'available_quantity' => 0,
        ]);

        // Currently booked during the requested date range
        EquipmentRequest::create([
            'equipment_id'         => $ext5m->id,
            'user_id'              => $user->id,
            'quantity'             => 1,
            'purpose'              => 'Existing Event',
            'date_needed'          => $dateNeeded,
            'expected_return_date' => $expectedReturn,
            'status'               => 'borrowed',
        ]);

        // Other inventory items from other categories exist
        $this->createEquip(['name' => 'LCD Projector', 'category' => 'Audio-Visual']);
        $this->createEquip(['name' => 'DSLR Camera', 'category' => 'Photography']);

        $suggestions = AlternativeSuggester::suggestAlternateEquipment($ext5m, 1, $dateNeeded, $expectedReturn);

        $this->assertNotEmpty($suggestions);

        // All suggestions must be available borrow dates (available time) for the requested equipment
        foreach ($suggestions as $sug) {
            $this->assertEquals('alternate_borrow_date', $sug['type'], 'Expected only available borrow date suggestions when no same-function equipment exists.');
            $this->assertEquals($ext5m->id, $sug['equipment_id']);
            // The suggested date needed must be after the booked return date
            $this->assertGreaterThanOrEqual($expectedReturn, $sug['date_needed']);
        }

        // None of the suggestions are alternate equipment of different categories
        $altEquipmentSuggestions = collect($suggestions)->where('type', 'alternate_equipment');
        $this->assertCount(0, $altEquipmentSuggestions);
    }
}
