<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\EquipmentRequest;
use App\Models\Facility;
use App\Models\FacilityReservation;
use Illuminate\Support\Carbon;

/**
 * AlternativeSuggester — Pure deterministic logic for generating alternative
 * facility bookings and equipment borrow options.
 *
 * Rules:
 *  - Facilities:
 *    a. Other facilities free at same date/time, capacity >= original, closest capacity first
 *    b. Same facility, nearest free time slots the same day (within config operating hours)
 *    c. Same facility and same time on the next 3 days
 *    Capped at config max_suggestions (default 5).
 *    ONLY approved reservations count as occupied; pending rows are ignored.
 *
 *  - Equipment:
 *    a. Same category substitutes with sufficient stock first
 *    b. Other categories with sufficient stock second
 *    Capped at config max_suggestions (default 5).
 */
class AlternativeSuggester
{
    /**
     * Build up to 5 ordered facility suggestions:
     *  a. Other facilities free at the same date/time, with capacity >= the original, closest capacity first
     *  b. Same facility, nearest free time slots the same day
     *  c. Same facility and same time on the next 3 days
     *
     * @return array<int, array{type: string, label: string, facility_id?: int, name?: string, location?: string, capacity?: int, date?: string, start?: string, end?: string}>
     */
    public static function suggestFacilityAlternatives(
        Facility $facility,
        string $date,
        string $timeStart,
        string $timeEnd
    ): array {
        $maxSuggestions = (int) config('icoep.auto_approve.facility.max_suggestions', 5);
        $suggestions = [];

        // ─── a. Other facilities free at the same date/time (cap >= original, closest first) ───
        $otherFacilities = Facility::where('id', '!=', $facility->id)
            ->where('status', 'available')
            ->where('capacity', '>=', $facility->capacity)
            ->get()
            ->sortBy(function ($fac) use ($facility) {
                return abs($fac->capacity - $facility->capacity);
            });

        foreach ($otherFacilities as $fac) {
            if (count($suggestions) >= $maxSuggestions) {
                break;
            }

            // Only approved reservations block a slot; pending rows are ignored
            $hasConflict = FacilityReservation::conflicts($fac->id, $date, $timeStart, $timeEnd)->exists();

            if (!$hasConflict) {
                $suggestions[] = [
                    'type'        => 'alternate_facility',
                    'facility_id' => $fac->id,
                    'name'        => $fac->name,
                    'location'    => $fac->location,
                    'capacity'    => $fac->capacity,
                    'label'       => "{$fac->name} ({$fac->location})",
                ];
            }
        }

        // ─── b. Same facility, nearest free time slots the same day ───
        if (count($suggestions) < $maxSuggestions) {
            $opStartStr = config('icoep.auto_approve.facility.operating_start', '07:00');
            $opEndStr   = config('icoep.auto_approve.facility.operating_end', '20:00');

            $reqStart = Carbon::parse($date . ' ' . $timeStart);
            $reqEnd   = Carbon::parse($date . ' ' . $timeEnd);
            $durationMinutes = $reqStart->diffInMinutes($reqEnd);
            if ($durationMinutes < 30) {
                $durationMinutes = 60;
            }

            $dayStart = Carbon::parse($date . ' ' . $opStartStr);
            $dayEnd   = Carbon::parse($date . ' ' . $opEndStr);

            // Fetch occupied approved blocks
            $approvedRes = FacilityReservation::where('facility_id', $facility->id)
                ->whereDate('reservation_date', $date)
                ->where('status', 'approved')
                ->orderBy('time_start')
                ->get();

            $occupied = [];
            foreach ($approvedRes as $r) {
                $occupied[] = [
                    'start' => Carbon::parse($date . ' ' . $r->time_start),
                    'end'   => Carbon::parse($date . ' ' . $r->time_end),
                ];
            }

            // Scan candidate windows in 30-min intervals
            $candidateSlots = [];
            $cursor = $dayStart->copy();

            while ($cursor->copy()->addMinutes($durationMinutes)->lte($dayEnd)) {
                $windowEnd = $cursor->copy()->addMinutes($durationMinutes);
                $isFree = true;

                foreach ($occupied as $occ) {
                    if ($cursor->lt($occ['end']) && $windowEnd->gt($occ['start'])) {
                        $isFree = false;
                        break;
                    }
                }

                if ($isFree) {
                    $sStart = $cursor->format('H:i');
                    $sEnd   = $windowEnd->format('H:i');

                    // Do not suggest the exact requested slot that conflicted
                    if ($sStart !== $timeStart || $sEnd !== $timeEnd) {
                        $distance = abs($cursor->diffInMinutes($reqStart));
                        $candidateSlots[] = [
                            'distance' => $distance,
                            'slot'     => [
                                'type'     => 'time_slot',
                                'facility' => $facility->name,
                                'date'     => $date,
                                'start'    => $sStart,
                                'end'      => $sEnd,
                                'label'    => "{$date} {$sStart}–{$sEnd}",
                            ],
                        ];
                    }
                }

                $cursor->addMinutes(30);
            }

            // Nearest to requested start time first
            usort($candidateSlots, fn($a, $b) => $a['distance'] <=> $b['distance']);

            foreach ($candidateSlots as $cand) {
                if (count($suggestions) >= $maxSuggestions) {
                    break;
                }
                $suggestions[] = $cand['slot'];
            }
        }

        // ─── c. Same facility and same time on the next 3 days ───
        if (count($suggestions) < $maxSuggestions) {
            for ($i = 1; $i <= 3 && count($suggestions) < $maxSuggestions; $i++) {
                $altDate = Carbon::parse($date)->addDays($i)->toDateString();
                $hasConflict = FacilityReservation::conflicts(
                    $facility->id,
                    $altDate,
                    $timeStart,
                    $timeEnd
                )->exists();

                if (!$hasConflict) {
                    $suggestions[] = [
                        'type'     => 'alternate_date',
                        'facility' => $facility->name,
                        'date'     => $altDate,
                        'start'    => $timeStart,
                        'end'      => $timeEnd,
                        'label'    => "{$altDate} {$timeStart}–{$timeEnd}",
                    ];
                }
            }
        }

        return array_slice($suggestions, 0, $maxSuggestions);
    }

    /**
     * Suggest substitute equipment:
     *  a. Same category substitutes with sufficient stock first
     *  b. Other categories with sufficient stock second
     *
     * @return array<int, array{type: string, equipment_id: int, name: string, category: string, available: int, label: string}>
     */
    public static function suggestAlternateEquipment(
        Equipment $excludeItem,
        int $neededQty,
        ?string $dateNeeded = null,
        ?string $expectedReturn = null
    ): array {
        $maxSuggestions = (int) config('icoep.auto_approve.equipment.max_suggestions', 5);
        $suggestions = [];

        // Helper to determine available stock for an equipment item
        $calcStock = function (Equipment $item) use ($dateNeeded, $expectedReturn) {
            if ($dateNeeded && $expectedReturn) {
                $conflicts = EquipmentRequest::where('equipment_id', $item->id)
                    ->whereIn('status', ['approved', 'borrowed'])
                    ->where('date_needed', '<=', $expectedReturn)
                    ->where('expected_return_date', '>=', $dateNeeded)
                    ->sum('quantity');

                $periodStock = max(0, $item->quantity - $conflicts);
                return min($periodStock, $item->available_quantity);
            }
            return $item->available_quantity;
        };

        // 1. Same category substitutes
        $sameCat = Equipment::where('id', '!=', $excludeItem->id)
            ->where('category', $excludeItem->category)
            ->where('status', 'available')
            ->get();

        $sameCatList = [];
        foreach ($sameCat as $item) {
            $stock = $calcStock($item);
            if ($stock >= $neededQty) {
                $sameCatList[] = ['item' => $item, 'stock' => $stock];
            }
        }

        usort($sameCatList, fn($a, $b) => $b['stock'] <=> $a['stock']);

        foreach ($sameCatList as $c) {
            if (count($suggestions) >= $maxSuggestions) {
                break;
            }
            $suggestions[] = [
                'type'         => 'alternate_equipment',
                'equipment_id' => $c['item']->id,
                'name'         => $c['item']->name,
                'category'     => $c['item']->category,
                'available'    => $c['stock'],
                'label'        => "{$c['item']->name} ({$c['item']->category}, {$c['stock']} avail.)",
            ];
        }

        // 2. Other categories
        if (count($suggestions) < $maxSuggestions) {
            $otherCat = Equipment::where('id', '!=', $excludeItem->id)
                ->where('category', '!=', $excludeItem->category)
                ->where('status', 'available')
                ->get();

            $otherCatList = [];
            foreach ($otherCat as $item) {
                $stock = $calcStock($item);
                if ($stock >= $neededQty) {
                    $otherCatList[] = ['item' => $item, 'stock' => $stock];
                }
            }

            usort($otherCatList, fn($a, $b) => $b['stock'] <=> $a['stock']);

            foreach ($otherCatList as $c) {
                if (count($suggestions) >= $maxSuggestions) {
                    break;
                }
                $suggestions[] = [
                    'type'         => 'alternate_equipment',
                    'equipment_id' => $c['item']->id,
                    'name'         => $c['item']->name,
                    'category'     => $c['item']->category,
                    'available'    => $c['stock'],
                    'label'        => "{$c['item']->name} ({$c['item']->category}, {$c['stock']} avail.)",
                ];
            }
        }

        return array_slice($suggestions, 0, $maxSuggestions);
    }

    /**
     * Backward-compatibility aliases
     */
    public static function suggestAlternateTimeSlots(
        Facility $facility,
        string $date,
        string $requestedStart,
        string $requestedEnd
    ): array {
        return self::suggestFacilityAlternatives($facility, $date, $requestedStart, $requestedEnd);
    }

    public static function suggestAlternateFacilities(
        Facility $excludeFacility,
        string $date,
        string $timeStart,
        string $timeEnd
    ): array {
        return self::suggestFacilityAlternatives($excludeFacility, $date, $timeStart, $timeEnd);
    }
}
