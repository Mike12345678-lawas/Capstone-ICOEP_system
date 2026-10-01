<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentRequest;
use App\Models\Facility;
use App\Models\FacilityReservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckPermission::class,
            \App\Http\Middleware\CheckActiveUser::class,
        ]);
    }

    public function test_approve_equipment_with_insufficient_stock_is_refused()
    {
        $user = User::factory()->create();
        $equipment = Equipment::create([
            'name' => 'Projector',
            'category' => 'Electronics',
            'quantity' => 1,
            'available_quantity' => 1,
            'status' => 'available',
        ]);

        $request1 = EquipmentRequest::create([
            'equipment_id' => $equipment->id,
            'user_id' => $user->id,
            'quantity' => 1,
            'purpose' => 'Test',
            'date_needed' => now()->addDay(),
            'expected_return_date' => now()->addDays(2),
            'status' => 'pending',
            'remarks' => ''
        ]);

        $request2 = EquipmentRequest::create([
            'equipment_id' => $equipment->id,
            'user_id' => $user->id,
            'quantity' => 1,
            'purpose' => 'Test 2',
            'date_needed' => now()->addDay(),
            'expected_return_date' => now()->addDays(2),
            'status' => 'pending',
            'remarks' => ''
        ]);

        $this->actingAs($user);

        // Approve first
        $this->patch("/equipment-requests/{$request1->id}/status", [
            'status' => 'approved'
        ]);
        $this->assertEquals(0, $equipment->fresh()->available_quantity);
        $this->assertEquals('approved', $request1->fresh()->status);
        
        // Approve second (should fail due to stock)
        $this->patch("/equipment-requests/{$request2->id}/status", [
            'status' => 'approved'
        ]);
        
        $this->assertEquals(0, $equipment->fresh()->available_quantity);
        $this->assertEquals('pending', $request2->fresh()->status);
    }

    public function test_approving_same_request_twice_does_not_double_decrement_stock()
    {
        $user = User::factory()->create();
        $equipment = Equipment::create([
            'name' => 'Projector',
            'category' => 'Electronics',
            'quantity' => 5,
            'available_quantity' => 5,
            'status' => 'available'
        ]);

        $req = EquipmentRequest::create([
            'equipment_id' => $equipment->id,
            'user_id' => $user->id,
            'quantity' => 1,
            'purpose' => 'Test',
            'date_needed' => now()->addDay(),
            'expected_return_date' => now()->addDays(2),
            'status' => 'pending',
            'remarks' => ''
        ]);

        $this->actingAs($user);

        $this->patch("/equipment-requests/{$req->id}/status", [
            'status' => 'approved'
        ]);
        $this->assertEquals(4, $equipment->fresh()->available_quantity);
        $this->assertEquals('approved', $req->fresh()->status);

        // Try again (simulate resubmitting form)
        $this->patch("/equipment-requests/{$req->id}/status", [
            'status' => 'approved'
        ]);
        
        // Stock should remain 4
        $this->assertEquals(4, $equipment->fresh()->available_quantity);
    }

    public function test_approving_overlapping_facility_slot_is_refused()
    {
        $user = User::factory()->create();
        $facility = Facility::create([
            'name' => 'Hall A',
            'location' => 'Main',
            'capacity' => 100,
            'status' => 'available'
        ]);

        $res1 = FacilityReservation::create([
            'facility_id' => $facility->id,
            'user_id' => $user->id,
            'title' => 'Event 1',
            'reservation_date' => now()->addDay()->toDateString(),
            'time_start' => '10:00:00',
            'time_end' => '12:00:00',
            'status' => 'pending'
        ]);

        $res2 = FacilityReservation::create([
            'facility_id' => $facility->id,
            'user_id' => $user->id,
            'title' => 'Event 2',
            'reservation_date' => now()->addDay()->toDateString(),
            'time_start' => '11:00:00',
            'time_end' => '13:00:00',
            'status' => 'pending'
        ]);

        $this->actingAs($user);

        // Approve first
        $this->patch("/facility-reservations/{$res1->id}/status", [
            'status' => 'approved'
        ]);
        $this->assertEquals('approved', $res1->fresh()->status);

        // Approve second (conflict)
        $this->patch("/facility-reservations/{$res2->id}/status", [
            'status' => 'approved'
        ]);
        $this->assertEquals('pending', $res2->fresh()->status);
    }

    public function test_cancelled_reservation_cannot_be_approved()
    {
        $user = User::factory()->create();
        $facility = Facility::create([
            'name' => 'Hall A',
            'location' => 'Main',
            'capacity' => 100,
            'status' => 'available'
        ]);

        $res = FacilityReservation::create([
            'facility_id' => $facility->id,
            'user_id' => $user->id,
            'title' => 'Event 1',
            'reservation_date' => now()->addDay()->toDateString(),
            'time_start' => '10:00:00',
            'time_end' => '12:00:00',
            'status' => 'cancelled'
        ]);

        $this->actingAs($user);

        $this->patch("/facility-reservations/{$res->id}/status", [
            'status' => 'approved'
        ]);
        
        $this->assertEquals('cancelled', $res->fresh()->status);
    }
}
