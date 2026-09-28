<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\Facility;
use App\Models\FacilityReservation;
use Illuminate\Support\Carbon;

/**
 * AlternativeSuggester — When a resource is unavailable, instantly queries
 * the database and suggests alternatives:
 *  - Alternate available time slots for the same facility
 *  - Substitute equipment with sufficient stock
 *  - Different vacant rooms/facilities
 */
class AlternativeSuggester
{
    /**
     * Suggest available time slots for the same facility on the same date.
     * Scans the day in 1-hour increments and finds free windows.
     *
     * @return array<int, array{start: string, end: string}>
     */
    public static function suggestAlternateTimeSlots(
        Facility $facility,
        string $date,
        string $requestedStart,
        string $requestedEnd
    ): array {
        // Get all approved/pending reservations for this facility on this date
        $reservations = FacilityReservation::where('facility_id', $facility->id)
            ->where('reservation_date', $date)
            ->whereIn('status', ['pending', 'approved'])
            ->orderBy('time_start')
            ->get(['time_start', 'time_end']);

        // Calculate requested duration in minutes
        $requestedDuration = Carbon::parse($requestedStart)->diffInMinutes(Carbon::parse($requestedEnd));
        if ($requestedDuration < 30) $requestedDuration = 60; // minimum 1 hour

        // Define operating hours (07:00 - 21:00)
        $dayStart = Carbon::parse($date . ' 07:00');
        $dayEnd   = Carbon::parse($date . ' 21:00');

        // Build list of occupied intervals
        $occupied = [];
        foreach ($reservations as $res) {
            $occupied[] = [
                'start' => Carbon::parse($date . ' ' . $res->time_start),
                'end'   => Carbon::parse($date . ' ' . $res->time_end),
            ];
        }

        // Scan for free windows
        $suggestions = [];
        $cursor = $dayStart->copy();

        while ($cursor->copy()->addMinutes($requestedDuration)->lte($dayEnd) && count($suggestions) < 5) {
            $windowEnd = $cursor->copy()->addMinutes($requestedDuration);
            $isFree = true;

            foreach ($occupied as $occ) {
                // Check overlap: cursor < occ.end AND windowEnd > occ.start
                if ($cursor->lt($occ['end']) && $windowEnd->gt($occ['start'])) {
                    $isFree = false;
                    // Skip cursor to end of this occupied block
                    $cursor = $occ['end']->copy();
                    break;
                }
            }

            if ($isFree) {
                $slotStart = $cursor->format('H:i');
                $slotEnd   = $windowEnd->format('H:i');

                // Don't suggest the exact same slot that was rejected
                if ($slotStart !== $requestedStart || $slotEnd !== $requestedEnd) {
                    $suggestions[] = [
                        'type'     => 'time_slot',
                        'facility' => $facility->name,
                        'date'     => $date,
                        'start'    => $slotStart,
                        'end'      => $slotEnd,
                    ];
                }

                $cursor->addMinutes(30); // slide by 30 mins for next option
            }
        }

        // Also suggest same slot on the next 3 days
        for ($i = 1; $i <= 3 && count($suggestions) < 8; $i++) {
            $altDate = Carbon::parse($date)->addDays($i)->toDateString();
            $altConflicts = FacilityReservation::conflicts(
                $facility->id, $altDate, $requestedStart, $requestedEnd
            )->count();

            if ($altConflicts === 0) {
                $suggestions[] = [
                    'type'     => 'alternate_date',
                    'facility' => $facility->name,
                    'date'     => $altDate,
                    'start'    => $requestedStart,
                    'end'      => $requestedEnd,
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Suggest different facilities that are free during the requested time.
     *
     * @return array<int, array{facility_id: int, name: string, location: string, capacity: int}>
     */
    public static function suggestAlternateFacilities(
        Facility $excludeFacility,
        string $date,
        string $timeStart,
        string $timeEnd
    ): array {
        $facilities = Facility::where('id', '!=', $excludeFacility->id)
            ->where('status', 'available')
            ->get();

        $suggestions = [];

        foreach ($facilities as $fac) {
            $conflicts = FacilityReservation::conflicts($fac->id, $date, $timeStart, $timeEnd)->count();

            if ($conflicts === 0) {
                $suggestions[] = [
                    'type'        => 'alternate_facility',
                    'facility_id' => $fac->id,
                    'name'        => $fac->name,
                    'location'    => $fac->location,
                    'capacity'    => $fac->capacity,
                ];

                if (count($suggestions) >= 5) break;
            }
        }

        return $suggestions;
    }

    /**
     * Suggest substitute equipment in the same or similar category.
     *
     * @return array<int, array{equipment_id: int, name: string, category: string, available: int}>
     */
    public static function suggestAlternateEquipment(Equipment $excludeItem, int $neededQty): array
    {
        $suggestions = [];

        // First: same category, different item
        $sameCategory = Equipment::where('id', '!=', $excludeItem->id)
            ->where('category', $excludeItem->category)
            ->where('status', 'available')
            ->where('available_quantity', '>=', $neededQty)
            ->orderByDesc('available_quantity')
            ->limit(3)
            ->get();

        foreach ($sameCategory as $item) {
            $suggestions[] = [
                'type'         => 'same_category',
                'equipment_id' => $item->id,
                'name'         => $item->name,
                'category'     => $item->category,
                'available'    => $item->available_quantity,
            ];
        }

        // Second: any category with available stock
        if (count($suggestions) < 5) {
            $otherItems = Equipment::where('id', '!=', $excludeItem->id)
                ->where('category', '!=', $excludeItem->category)
                ->where('status', 'available')
                ->where('available_quantity', '>=', $neededQty)
                ->orderByDesc('available_quantity')
                ->limit(5 - count($suggestions))
                ->get();

            foreach ($otherItems as $item) {
                $suggestions[] = [
                    'type'         => 'other_category',
                    'equipment_id' => $item->id,
                    'name'         => $item->name,
                    'category'     => $item->category,
                    'available'    => $item->available_quantity,
                ];
            }
        }

        return $suggestions;
    }
}
