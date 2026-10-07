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
 * Every suggestion carries a concrete, future date (and time range for facilities)
 * so the user can see exactly WHEN the alternative is available. If the requested
 * date/time is already in the past, suggestions are shifted to the next valid date.
 *
 * Rules:
 *  - Facilities:
 *    a. Other facilities free at the (suggested) date/time, capacity >= original, closest capacity first
 *    b. Same facility, nearest free time slots on the (suggested) day (within config operating hours)
 *    c. Same facility and same time on the following days
 *    Results are mixed so date options are always shown alongside alternate facilities.
 *    Capped at config max_suggestions (default 5).
 *    ONLY approved reservations count as occupied; pending rows are ignored.
 *
 *  - Equipment:
 *    a. Substitutes with the exact SAME FUNCTION (e.g. Extension Cord 5m -> Extension Cord 10m)
 *    b. If no same function equipment available, suggest nearest free borrow dates (available time)
 *       for the requested equipment
 *    c. If same function substitutes exist, remaining slots show nearest free borrow dates
 *    Capped at config max_suggestions (default 5).
 */
class AlternativeSuggester
{
    /** Human-friendly date, e.g. "Mon, Oct 6". */
    protected static function fmtDate(string $date): string
    {
        return Carbon::parse($date)->format('D, M j');
    }

    /** Human-friendly time, e.g. "8:00 AM". */
    protected static function fmtTime(string $date, string $time): string
    {
        return Carbon::parse($date . ' ' . $time)->format('g:i A');
    }

    /** Human-friendly date range, e.g. "Mon, Oct 6" or "Mon, Oct 6 – Wed, Oct 8". */
    protected static function fmtRange(string $from, string $to): string
    {
        return $from === $to
            ? self::fmtDate($from)
            : self::fmtDate($from) . ' – ' . self::fmtDate($to);
    }

    /**
     * Build up to 5 ordered facility suggestions, each with a suggested date and time:
     *  a. Other facilities free at the suggested date/time, with capacity >= the original, closest capacity first
     *  b. Same facility, nearest free time slots on the suggested day
     *  c. Same facility and same time on the following days
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

        $timeStart = Carbon::parse($timeStart)->format('H:i');
        $timeEnd   = Carbon::parse($timeEnd)->format('H:i');

        // ─── Determine the base (suggested) date: never in the past ───
        $shifted = false;
        if (Carbon::parse($date . ' ' . $timeStart)->isPast()) {
            $shifted = true;
            $date = Carbon::parse(Carbon::today()->toDateString() . ' ' . $timeStart)->isFuture()
                ? Carbon::today()->toDateString()
                : Carbon::tomorrow()->toDateString();
        }

        $facilityList = [];
        $slotList     = [];
        $dateList     = [];

        // ─── a. Other facilities free at the suggested date/time (cap >= original, closest first) ───
        $otherFacilities = Facility::where('id', '!=', $facility->id)
            ->where('status', 'available')
            ->where('capacity', '>=', $facility->capacity)
            ->get()
            ->sortBy(function ($fac) use ($facility) {
                return abs($fac->capacity - $facility->capacity);
            });

        $timeLabel = self::fmtTime($date, $timeStart) . '–' . self::fmtTime($date, $timeEnd);

        foreach ($otherFacilities as $fac) {
            if (count($facilityList) >= $maxSuggestions) {
                break;
            }

            // Only approved reservations block a slot; pending rows are ignored
            $hasConflict = FacilityReservation::conflicts($fac->id, $date, $timeStart, $timeEnd)->exists();

            if (!$hasConflict) {
                $facilityList[] = [
                    'type'        => 'alternate_facility',
                    'facility_id' => $fac->id,
                    'name'        => $fac->name,
                    'location'    => $fac->location,
                    'capacity'    => $fac->capacity,
                    'date'        => $date,
                    'start'       => $timeStart,
                    'end'         => $timeEnd,
                    'label'       => "{$fac->name} ({$fac->location}) · " . self::fmtDate($date) . ", {$timeLabel}",
                ];
            }
        }

        // Same-facility time/date suggestions only make sense if the facility itself is usable
        if ($facility->status === 'available') {
            // ─── b. Same facility, nearest free time slots on the suggested day ───
            $opStartStr = config('icoep.auto_approve.facility.operating_start', '06:00');
            $opEndStr   = config('icoep.auto_approve.facility.operating_end', '23:00');

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
                $isFree = !$cursor->isPast();

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
                    // (when the date was shifted, the same times on the new day are valid)
                    if ($shifted || $sStart !== $timeStart || $sEnd !== $timeEnd) {
                        $distance = abs($cursor->diffInMinutes($reqStart));
                        $candidateSlots[] = [
                            'distance' => $distance,
                            'slot'     => [
                                'type'     => 'time_slot',
                                'facility' => $facility->name,
                                'date'     => $date,
                                'start'    => $sStart,
                                'end'      => $sEnd,
                                'label'    => self::fmtDate($date) . ', ' . self::fmtTime($date, $sStart) . '–' . self::fmtTime($date, $sEnd),
                            ],
                        ];
                    }
                }

                $cursor->addMinutes(30);
            }

            // Nearest to requested start time first
            usort($candidateSlots, fn($a, $b) => $a['distance'] <=> $b['distance']);
            $slotList = array_column($candidateSlots, 'slot');

            // ─── c. Same facility and same time on the following days ───
            for ($i = 1; $i <= 7 && count($dateList) < $maxSuggestions; $i++) {
                $altDate = Carbon::parse($date)->addDays($i)->toDateString();
                $hasConflict = FacilityReservation::conflicts(
                    $facility->id,
                    $altDate,
                    $timeStart,
                    $timeEnd
                )->exists();

                if (!$hasConflict) {
                    $dateList[] = [
                        'type'     => 'alternate_date',
                        'facility' => $facility->name,
                        'date'     => $altDate,
                        'start'    => $timeStart,
                        'end'      => $timeEnd,
                        'label'    => self::fmtDate($altDate) . ', ' . self::fmtTime($altDate, $timeStart) . '–' . self::fmtTime($altDate, $timeEnd),
                    ];
                }
            }
        }

        // ─── Mix results so date options always appear alongside alternate facilities ───
        $suggestions = array_merge(
            array_slice($facilityList, 0, 2),
            array_slice($slotList, 0, 1),
            array_slice($dateList, 0, 2)
        );
        $leftovers = array_merge(
            array_slice($slotList, 1),
            array_slice($dateList, 2),
            array_slice($facilityList, 2)
        );
        foreach ($leftovers as $item) {
            if (count($suggestions) >= $maxSuggestions) {
                break;
            }
            $suggestions[] = $item;
        }

        return array_slice($suggestions, 0, $maxSuggestions);
    }

    /**
     * Functional synonym groupings for campus/inventory equipment.
     */
    protected static array $functionalGroups = [
        // Power distribution & extension
        ['extension cord', 'extension cable', 'extension wire', 'power strip', 'cord reel', 'extension'],
        // Video projection & screens
        ['lcd projector', 'dlp projector', 'video projector', 'overhead projector', 'projector screen', 'projector'],
        // Audio sound output & PA systems
        ['portable speaker', 'speaker system', 'portable pa system', 'pa system', 'sound system', 'amplifier', 'speaker'],
        // Audio input & microphones
        ['wireless microphone', 'wired microphone', 'lapel microphone', 'wireless mic', 'wired mic', 'lapel mic', 'lavalier', 'microphone', 'mic'],
        // Video display cabling
        ['hdmi cable', 'vga cable', 'displayport cable', 'hdmi', 'vga', 'displayport'],
        // Cameras & photography
        ['dslr camera', 'digital camera', 'mirrorless camera', 'camcorder', 'video camera', 'camera'],
        // Camera mounts & stands
        ['tripod stand', 'camera tripod', 'monopod', 'camera stand', 'tripod'],
        // Presentation controllers & pointers
        ['wireless presenter', 'laser pointer', 'slide clicker', 'clicker', 'presenter'],
        // Laptops & computers
        ['laptop windows', 'laptop mac', 'notebook', 'macbook', 'netbook', 'laptop'],
        // Presentation & writing boards
        ['whiteboard portable', 'marker board', 'chalkboard', 'blackboard', 'whiteboard'],
    ];

    /**
     * Check if two categories are functionally compatible (e.g. Audio-Visual and Audio).
     */
    protected static function areCategoriesCompatible(string $catA, string $catB): bool
    {
        if ($catA === $catB) {
            return true;
        }

        $compatiblePairs = [
            ['audio-visual', 'audio'],
            ['audio-visual', 'presentation'],
            ['electrical', 'accessories'],
            ['photography', 'accessories'],
        ];

        foreach ($compatiblePairs as [$c1, $c2]) {
            if (($catA === $c1 && $catB === $c2) || ($catA === $c2 && $catB === $c1)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract core functional keywords/tokens from equipment name,
     * stripping length/metrics (e.g. 5m, 10 meters), numbers, and generic adjectives.
     *
     * @return array<string>
     */
    protected static function extractFunctionalTokens(string $name): array
    {
        $clean = strtolower($name);

        // Remove metrics and units: 5m, 10m, 5 meters, 10 meters, 5ft, 100ft, 4k, 1080p, etc.
        $clean = preg_replace('/\b\d+\s*(m|meter|meters|ft|feet|inch|inches|cm|mm|gb|tb|w|watt|watts|v|volt|volts|ah|mah|k|p)\b/i', ' ', $clean);
        // Remove standalone numbers
        $clean = preg_replace('/\b\d+\b/', ' ', $clean);
        // Remove punctuation and special characters
        $clean = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $clean);

        // Generic modifier stop-words that don't indicate function by themselves
        $stopWords = [
            'portable', 'standard', 'heavy', 'duty', 'compact', 'mini', 'large', 'small',
            'medium', 'pro', 'basic', 'set', 'pair', 'pack', 'color', 'black', 'white',
            'silver', 'digital', 'analog', 'smart', 'model', 'series', 'type', 'windows',
            'mac', 'pc', 'wireless', 'wired', 'usb',
        ];

        $tokens = preg_split('/\s+/', $clean, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return array_values(array_filter($tokens, function ($t) use ($stopWords) {
            return strlen($t) > 2 && !in_array($t, $stopWords, true);
        }));
    }

    /**
     * Check if two equipment items serve the exact SAME function.
     * For example:
     *  - "Extension Cord (5m)" and "Extension Cord (10m)" => true
     *  - "Extension 5 meters" and "Extension 10 meters" => true
     *  - "Portable Speaker System" and "Portable PA System" => true
     *  - "Extension Cord (10m)" and "LCD Projector" => false
     *  - "LCD Projector" and "Portable PA System" => false
     */
    public static function hasSameFunction(Equipment $a, Equipment $b): bool
    {
        if ($a->id === $b->id) {
            return false;
        }

        $catA = strtolower(trim($a->category ?? ''));
        $catB = strtolower(trim($b->category ?? ''));

        // If categories are completely incompatible, they do not serve the same function
        if (!self::areCategoriesCompatible($catA, $catB)) {
            return false;
        }

        $nameA = strtolower(trim($a->name ?? ''));
        $nameB = strtolower(trim($b->name ?? ''));

        // 1. Check functional synonym groups
        foreach (self::$functionalGroups as $group) {
            $matchA = false;
            $matchB = false;
            foreach ($group as $kw) {
                if (str_contains($nameA, $kw)) {
                    $matchA = true;
                }
                if (str_contains($nameB, $kw)) {
                    $matchB = true;
                }
            }
            if ($matchA && $matchB) {
                return true;
            }
        }

        // 2. Tokenize and check shared functional keywords
        $tokensA = self::extractFunctionalTokens($nameA);
        $tokensB = self::extractFunctionalTokens($nameB);

        if (!empty($tokensA) && !empty($tokensB)) {
            $common = array_intersect($tokensA, $tokensB);
            if (!empty($common)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Suggest equipment alternatives, strictly enforcing functional equivalence:
     *  a. Other equipment with the SAME FUNCTION available for the requested period
     *     (e.g., Extension Cord 5m -> Extension Cord 10m)
     *  b. If no same function equipment available (or to fill remaining suggestions),
     *     suggest available borrow dates/time for the requested equipment.
     *  c. NEVER suggest equipment with different functions from other categories.
     *
     * @return array<int, array{type: string, equipment_id: int, name: string, category?: string, available?: int, date_needed?: string, expected_return_date?: string, label: string}>
     */
    public static function suggestAlternateEquipment(
        Equipment $excludeItem,
        int $neededQty,
        ?string $dateNeeded = null,
        ?string $expectedReturn = null
    ): array {
        $maxSuggestions = (int) config('icoep.auto_approve.equipment.max_suggestions', 5);
        $suggestions = [];

        // ─── Determine the base (suggested) period: never in the past, same duration ───
        $today = Carbon::today();
        $start = $dateNeeded ? Carbon::parse($dateNeeded)->startOfDay() : $today->copy();
        $end   = $expectedReturn ? Carbon::parse($expectedReturn)->startOfDay() : $start->copy();
        if ($end->lt($start)) {
            $end = $start->copy();
        }
        $durationDays = (int) $start->diffInDays($end);
        if ($start->lt($today)) {
            $start = $today->copy();
            $end   = $start->copy()->addDays($durationDays);
        }
        $dateNeeded     = $start->toDateString();
        $expectedReturn = $end->toDateString();
        $periodLabel    = self::fmtRange($dateNeeded, $expectedReturn);

        // Helper to determine available stock for an equipment item during the requested period
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

        // ─── 1. Find substitutes that have the exact SAME FUNCTION ───
        $candidateItems = Equipment::where('id', '!=', $excludeItem->id)
            ->where('status', 'available')
            ->get();

        $sameFunctionList = [];
        foreach ($candidateItems as $candidate) {
            if (self::hasSameFunction($excludeItem, $candidate)) {
                $stock = $calcStock($candidate);
                if ($stock >= $neededQty) {
                    $sameFunctionList[] = [
                        'item'  => $candidate,
                        'stock' => $stock,
                    ];
                }
            }
        }

        // Sort same-function items by available stock descending
        usort($sameFunctionList, fn($a, $b) => $b['stock'] <=> $a['stock']);

        foreach ($sameFunctionList as $c) {
            if (count($suggestions) >= $maxSuggestions) {
                break;
            }
            $suggestions[] = [
                'type'                 => 'alternate_equipment',
                'equipment_id'         => $c['item']->id,
                'name'                 => $c['item']->name,
                'category'             => $c['item']->category,
                'available'            => $c['stock'],
                'date_needed'          => $dateNeeded,
                'expected_return_date' => $expectedReturn,
                'label'                => "{$c['item']->name} ({$c['stock']} avail.) · {$periodLabel}",
            ];
        }

        // ─── 2. Available time suggestions for the requested equipment ───
        // If no same function equipment available, OR to provide flexible schedule choices,
        // scan forward for nearest free borrow periods for the requested equipment.
        $slotsNeeded = $maxSuggestions - count($suggestions);

        if ($slotsNeeded > 0 && $excludeItem->status === 'available' && $excludeItem->quantity >= $neededQty) {
            $found = 0;
            // Scan up to 60 days ahead for free borrow periods with the same duration
            for ($i = 0; $i <= 60 && $found < $slotsNeeded; $i++) {
                $from = $start->copy()->addDays($i)->toDateString();
                $to   = $start->copy()->addDays($i + $durationDays)->toDateString();

                // Skip the exact period requested if it already has insufficient stock
                if ($from === $dateNeeded && $to === $expectedReturn && $calcStock($excludeItem) < $neededQty) {
                    continue;
                }

                $booked = EquipmentRequest::where('equipment_id', $excludeItem->id)
                    ->whereIn('status', ['approved', 'borrowed'])
                    ->where('date_needed', '<=', $to)
                    ->where('expected_return_date', '>=', $from)
                    ->sum('quantity');
                $free = max(0, $excludeItem->quantity - $booked);

                if ($free >= $neededQty) {
                    $suggestions[] = [
                        'type'                 => 'alternate_borrow_date',
                        'equipment_id'         => $excludeItem->id,
                        'name'                 => $excludeItem->name,
                        'available'            => $free,
                        'date_needed'          => $from,
                        'expected_return_date' => $to,
                        'label'                => self::fmtRange($from, $to) . " ({$free} avail.)",
                    ];
                    $found++;
                }
            }
        }

        // ─── 3. Fallback: If requested item itself is unavailable (maintenance/retired), ───
        // and no same-function items were available on the requested date, look for free dates
        // on any available same-function candidate items.
        if (empty($suggestions) && $candidateItems->isNotEmpty()) {
            foreach ($candidateItems as $candidate) {
                if (count($suggestions) >= $maxSuggestions) {
                    break;
                }
                if (self::hasSameFunction($excludeItem, $candidate) && $candidate->quantity >= $neededQty) {
                    for ($i = 1; $i <= 30 && count($suggestions) < $maxSuggestions; $i++) {
                        $from = $start->copy()->addDays($i)->toDateString();
                        $to   = $start->copy()->addDays($i + $durationDays)->toDateString();

                        $booked = EquipmentRequest::where('equipment_id', $candidate->id)
                            ->whereIn('status', ['approved', 'borrowed'])
                            ->where('date_needed', '<=', $to)
                            ->where('expected_return_date', '>=', $from)
                            ->sum('quantity');
                        $free = max(0, $candidate->quantity - $booked);

                        if ($free >= $neededQty) {
                            $suggestions[] = [
                                'type'                 => 'alternate_equipment',
                                'equipment_id'         => $candidate->id,
                                'name'                 => $candidate->name,
                                'category'             => $candidate->category,
                                'available'            => $free,
                                'date_needed'          => $from,
                                'expected_return_date' => $to,
                                'label'                => "{$candidate->name} ({$free} avail.) · " . self::fmtRange($from, $to),
                            ];
                        }
                    }
                }
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
