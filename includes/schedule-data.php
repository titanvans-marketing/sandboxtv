<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Schedule Data
|--------------------------------------------------------------------------
| Final output shape used by app-scheduling.php:
|
| $scheduleSlots = [
|   '2026-03-18' => [
|     [
|       'time' => '9:00 AM',
|       'available' => true,
|       'label' => 'Morning Appointment',
|       'capacity' => 1,
|       'allowedBays' => ['Alignment Bay'],
|     ],
|   ],
| ];
|
| Notes:
| - All dates are YYYY-MM-DD.
| - Days with no configured slots simply will not exist in this array.
|   The frontend calendar still shows those days, but as "No appointments".
| - This file reads the selected service from:
|     1) $selectedServiceTypes if app-scheduling.php defines it before include
|     2) $_GET['services']
|     3) $_GET['serviceType']
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------
*/

$timezone = new DateTimeZone('America/Denver');
$today = new DateTimeImmutable('today', $timezone);

/*
|--------------------------------------------------------------------------
| Range to generate
|--------------------------------------------------------------------------
*/

$rangeStart = new DateTimeImmutable('first day of this month', $timezone);
$rangeEnd = $rangeStart->modify('+5 months')->modify('last day of this month');

/*
|--------------------------------------------------------------------------
| Services JSON
|--------------------------------------------------------------------------
*/

$servicesJsonPath = dirname(__DIR__, 2) . '/data/services.json';

/*
|--------------------------------------------------------------------------
| Physical bays
|--------------------------------------------------------------------------
| Bay 1: Accessory
| Bay 2: Automotive
| Bay 3: Alignment
|--------------------------------------------------------------------------
*/

$allPhysicalBays = [
    'Accessory Bay',
    'Automotive Bay',
    'Alignment Bay',
];

/*
|--------------------------------------------------------------------------
| Canonical service -> allowed bays
|--------------------------------------------------------------------------
| Based on your diagram:
| - Service A -> Alignment ONLY (no Accessory and no Automotive)
| - Service B -> Alignment ONLY (no Accessory and no Automotive)
| - Tire Rotation -> Accessory or Automotive or Alignment
| - Wheel Alignment -> Alignment ONLY (no Accessory and no Automotive)
|--------------------------------------------------------------------------
*/

$serviceBayRules = [
    'service a' => ['Alignment Bay'],
    'service b' => ['Alignment Bay'],
    'tire rotation' => ['Accessory Bay', 'Automotive Bay', 'Alignment Bay'],
    'wheel alignment' => ['Alignment Bay'],
];

/*
|--------------------------------------------------------------------------
| Selected service input
|--------------------------------------------------------------------------
*/

$requestedServiceInput = $selectedServiceTypes
    ?? ($_GET['services'] ?? ($_GET['serviceType'] ?? null));

/*
|--------------------------------------------------------------------------
| Schedule grid config
|--------------------------------------------------------------------------
| 8:00 AM -> 6:00 PM in 30-minute increments
|--------------------------------------------------------------------------
*/

$businessDays = [1, 2, 3, 4];
$closedWeekdaysJs = [0, 5, 6]; // Sunday, Friday, Saturday for JavaScript Date.getDay()
$slotStart = '08:00';
$slotEnd = '18:00';
$slotIntervalMinutes = 60;

/*
|--------------------------------------------------------------------------
| Helpers: service selection / rules
|--------------------------------------------------------------------------
*/

if (!function_exists('normalizeServiceTypeKey')) {
    function normalizeServiceTypeKey($value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/\s+/', ' ', $value);

        return is_string($value) ? $value : '';
    }
}

if (!function_exists('canonicalizeRequestedServiceType')) {
    function canonicalizeRequestedServiceType($value): string
    {
        $value = normalizeServiceTypeKey($value);

        if ($value === '') {
            return '';
        }

        /*
         * Supports verbose service names coming from the cart/UI,
         * for example "... TITAN SERVICE B" or "Front Tire Rotation".
         */
        if (preg_match('/\bservice\s*a\b/', $value)) {
            return 'service a';
        }

        if (preg_match('/\bservice\s*b\b/', $value)) {
            return 'service b';
        }

        if (preg_match('/\btire\s*rotation\b/', $value)) {
            return 'tire rotation';
        }

        if (preg_match('/\bwheel\s*alignment\b/', $value)) {
            return 'wheel alignment';
        }

        if (preg_match('/\balignment\s*service\b/', $value)) {
            return 'wheel alignment';
        }

        return '';
    }
}

if (!function_exists('normalizeRequestedServiceTypes')) {
    function normalizeRequestedServiceTypes($value): array
    {
        if ($value === null) {
            return [];
        }

        $rawItems = [];

        if (is_array($value)) {
            $rawItems = $value;
        } else {
            $stringValue = trim((string) $value);

            if ($stringValue === '') {
                return [];
            }

            $rawItems = preg_split('/\s*,\s*/', $stringValue) ?: [];
        }

        $normalized = [];

        foreach ($rawItems as $item) {
            $key = canonicalizeRequestedServiceType($item);

            if ($key !== '') {
                $normalized[] = $key;
            }
        }

        return array_values(array_unique($normalized));
    }
}

if (!function_exists('resolveAllowedBaysForRequestedServices')) {
    function resolveAllowedBaysForRequestedServices(
        array $requestedServiceTypes,
        array $serviceBayRules,
        array $allPhysicalBays
    ): array {
        /*
         * No selected service yet:
         * allow all bays as a generic fallback.
         */
        if ($requestedServiceTypes === []) {
            return $allPhysicalBays;
        }

        $allowedSets = [];

        foreach ($requestedServiceTypes as $serviceTypeKey) {
            if (!isset($serviceBayRules[$serviceTypeKey])) {
                /*
                 * Unknown service type:
                 * fail safely instead of bypassing restrictions.
                 */
                return [];
            }

            $allowedSets[] = $serviceBayRules[$serviceTypeKey];
        }

        /*
         * If multiple services are bundled into one appointment,
         * only keep bays that can perform all of them.
         */
        $intersection = array_shift($allowedSets);

        foreach ($allowedSets as $set) {
            $intersection = array_values(array_intersect($intersection, $set));
        }

        return array_values(array_unique($intersection));
    }
}

$requestedServiceTypes = normalizeRequestedServiceTypes($requestedServiceInput);

$serviceBays = resolveAllowedBaysForRequestedServices(
    $requestedServiceTypes,
    $serviceBayRules,
    $allPhysicalBays
);

/*
|--------------------------------------------------------------------------
| Default recurring weekly schedule
|--------------------------------------------------------------------------
*/

if (!function_exists('buildBaseDailySlots')) {
    function buildBaseDailySlots(
        DateTimeZone $timezone,
        string $start,
        string $end,
        int $intervalMinutes,
        int $defaultCapacity,
        array $allowedBays
    ): array {
        $slots = [];

        $startAt = DateTimeImmutable::createFromFormat('H:i', $start, $timezone);
        $endAt = DateTimeImmutable::createFromFormat('H:i', $end, $timezone);

        if (!$startAt || !$endAt || $endAt < $startAt) {
            return $slots;
        }

        for (
            $cursor = $startAt;
            $cursor <= $endAt;
            $cursor = $cursor->modify('+' . $intervalMinutes . ' minutes')
        ) {
            $hour24 = (int) $cursor->format('G');

            if ($hour24 < 12) {
                $label = 'Morning Appointment';
            } elseif ($hour24 < 16) {
                $label = 'Afternoon Appointment';
            } else {
                $label = 'Evening Appointment';
            }

            $slots[] = [
                'time' => $cursor->format('g:i A'),
                'available' => true,
                'label' => $label,
                'capacity' => $defaultCapacity,
                'allowedBays' => $allowedBays,
            ];
        }

        return $slots;
    }
}

if (!function_exists('buildWeeklyTemplateFromBaseSlots')) {
    function buildWeeklyTemplateFromBaseSlots(array $baseDailySlots, array $businessDays): array
    {
        $template = [
            1 => [],
            2 => [],
            3 => [],
            4 => [],
            5 => [],
            6 => [],
            7 => [],
        ];

        foreach ($businessDays as $dayNumber) {
            $template[(int) $dayNumber] = $baseDailySlots;
        }

        return $template;
    }
}

$baseDailySlots = $serviceBays === []
    ? []
    : buildBaseDailySlots(
        $timezone,
        $slotStart,
        $slotEnd,
        $slotIntervalMinutes,
        count($serviceBays),
        $serviceBays
    );

$weeklyTemplate = buildWeeklyTemplateFromBaseSlots($baseDailySlots, $businessDays);

/*
|--------------------------------------------------------------------------
| Fully blocked dates
|--------------------------------------------------------------------------
*/

$blockedDates = [
    // '2026-03-29',
    // '2026-04-12',
    // '2026-05-25',
];

/*
|--------------------------------------------------------------------------
| Dates that should exist but show fully booked slots
|--------------------------------------------------------------------------
*/

$fullyBookedDates = [
    // '2026-04-08',
];

/*
|--------------------------------------------------------------------------
| Custom per-date overrides
|--------------------------------------------------------------------------
*/

$dateOverrides = [
    // '2026-03-21' => [
    //     ['time' => '9:00 AM', 'available' => true, 'label' => 'Weekend Service', 'capacity' => 1],
    //     ['time' => '11:00 AM', 'available' => true, 'label' => 'Weekend Service', 'capacity' => 1],
    //     ['time' => '1:00 PM', 'available' => false, 'label' => 'Weekend Service', 'capacity' => 1],
    // ],
];

/*
|--------------------------------------------------------------------------
| General helpers
|--------------------------------------------------------------------------
*/

if (!function_exists('normalizeScheduleSlot')) {
    function normalizeScheduleSlot(array $slot): array
    {
        return [
            'time' => (string) ($slot['time'] ?? ''),
            'available' => isset($slot['available']) ? (bool) $slot['available'] : true,
            'label' => (string) ($slot['label'] ?? ''),
            'capacity' => isset($slot['capacity']) ? (int) $slot['capacity'] : 1,
            'allowedBays' => isset($slot['allowedBays']) && is_array($slot['allowedBays'])
                ? array_values($slot['allowedBays'])
                : [],
        ];
    }
}

if (!function_exists('dateIsBlocked')) {
    function dateIsBlocked(string $dateKey, array $blockedDates): bool
    {
        return in_array($dateKey, $blockedDates, true);
    }
}

if (!function_exists('dateIsFullyBooked')) {
    function dateIsFullyBooked(string $dateKey, array $fullyBookedDates): bool
    {
        return in_array($dateKey, $fullyBookedDates, true);
    }
}

if (!function_exists('buildFullyBookedSlots')) {
    function buildFullyBookedSlots(array $slots): array
    {
        return array_map(
            static function (array $slot): array {
                $normalized = normalizeScheduleSlot($slot);
                $normalized['available'] = false;
                return $normalized;
            },
            $slots
        );
    }
}

if (!function_exists('loadServicesJson')) {
    function loadServicesJson(string $jsonPath): array
    {
        if (!is_file($jsonPath)) {
            return [];
        }

        $raw = file_get_contents($jsonPath);

        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return [];
        }

        if (isset($decoded['services']) && is_array($decoded['services'])) {
            return $decoded['services'];
        }

        if (isset($decoded['records']) && is_array($decoded['records'])) {
            return $decoded['records'];
        }

        return [];
    }
}

if (!function_exists('getServiceField')) {
    function getServiceField(array $row, string ...$keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return $row[$key];
            }

            if (
                isset($row['fields']) &&
                is_array($row['fields']) &&
                array_key_exists($key, $row['fields'])
            ) {
                return $row['fields'][$key];
            }
        }

        return null;
    }
}

if (!function_exists('normalizeScheduleTimeKey')) {
    function normalizeScheduleTimeKey($value, DateTimeZone $timezone): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $formats = ['g:i A', 'g:iA', 'H:i', 'H:i:s'];

        foreach ($formats as $format) {
            $dt = DateTimeImmutable::createFromFormat($format, $value, $timezone);

            if ($dt !== false) {
                return $dt->format('H:i');
            }
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return null;
        }

        return (new DateTimeImmutable('@' . $timestamp))
            ->setTimezone($timezone)
            ->format('H:i');
    }
}

if (!function_exists('normalizeServiceBay')) {
    function normalizeServiceBay($value, array $validBays): ?string
    {
        $value = strtolower(trim((string) $value));

        if ($value === '') {
            return null;
        }

        $aliases = [
            'accessory' => 'Accessory Bay',
            'accessory bay' => 'Accessory Bay',
            'automotive' => 'Automotive Bay',
            'automotive bay' => 'Automotive Bay',
            'alignment' => 'Alignment Bay',
            'alignment bay' => 'Alignment Bay',
        ];

        if (isset($aliases[$value]) && in_array($aliases[$value], $validBays, true)) {
            return $aliases[$value];
        }

        foreach ($validBays as $bay) {
            if (strcasecmp($value, $bay) === 0) {
                return $bay;
            }
        }

        return null;
    }
}

if (!function_exists('buildOccupiedBayIndex')) {
    function buildOccupiedBayIndex(
        array $serviceRows,
        array $validBays,
        DateTimeZone $timezone,
        int $slotIntervalMinutes
    ): array {
        $occupied = [];
        $slotIntervalMinutes = max(1, $slotIntervalMinutes);

        $floorToSlot = static function (DateTimeImmutable $dt) use ($slotIntervalMinutes): DateTimeImmutable {
            $hour = (int) $dt->format('H');
            $minute = (int) $dt->format('i');

            $flooredMinute = (int) (floor($minute / $slotIntervalMinutes) * $slotIntervalMinutes);

            return $dt->setTime($hour, $flooredMinute, 0);
        };

        $ceilToSlot = static function (DateTimeImmutable $dt) use ($slotIntervalMinutes): DateTimeImmutable {
            $hour = (int) $dt->format('H');
            $minute = (int) $dt->format('i');
            $second = (int) $dt->format('s');

            $base = $dt->setTime($hour, $minute, 0);
            $remainder = $minute % $slotIntervalMinutes;

            if ($remainder === 0 && $second === 0) {
                return $base;
            }

            $minutesToAdd = $remainder === 0
                ? $slotIntervalMinutes
                : ($slotIntervalMinutes - $remainder);

            return $base->modify('+' . $minutesToAdd . ' minutes');
        };

        foreach ($serviceRows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $scheduledBay = normalizeServiceBay(
                getServiceField($row, 'scheduledBay', 'Scheduled Bay', 'scheduled_bay'),
                $validBays
            );

            if ($scheduledBay === null) {
                continue;
            }

            $status = strtoupper(trim((string) getServiceField(
                $row,
                'status',
                'jobStatus',
                'Job Status',
                'job_status'
            )));

            if (in_array($status, ['CANCELED', 'CANCELLED', 'COMPLETE', 'COMPLETED'], true)) {
                continue;
            }

            $startRaw = trim((string) getServiceField(
                $row,
                'startDate',
                'Start Date',
                'start_date',
                'scheduledDate',
                'Scheduled Date',
                'scheduled_date',
                'date'
            ));

            $endRaw = trim((string) getServiceField(
                $row,
                'jobCompletionDate',
                'Job Completion Date',
                'job_completion_date',
                'endDate',
                'End Date',
                'end_date'
            ));

            if ($startRaw === '') {
                continue;
            }

            try {
                $startAt = (new DateTimeImmutable($startRaw))->setTimezone($timezone);
            } catch (Throwable $e) {
                continue;
            }

            try {
                $endAt = $endRaw !== ''
                    ? (new DateTimeImmutable($endRaw))->setTimezone($timezone)
                    : $startAt;
            } catch (Throwable $e) {
                $endAt = $startAt;
            }

            if ($endAt < $startAt) {
                $endAt = $startAt;
            }

            $slotStart = $floorToSlot($startAt);
            $slotEndExclusive = $ceilToSlot($endAt);

            /*
             * Guarantee at least one occupied slot.
             */
            if ($slotEndExclusive <= $slotStart) {
                $slotEndExclusive = $slotStart->modify('+' . $slotIntervalMinutes . ' minutes');
            }

            for (
                $cursor = $slotStart;
                $cursor < $slotEndExclusive;
                $cursor = $cursor->modify('+' . $slotIntervalMinutes . ' minutes')
            ) {
                $dateKey = $cursor->format('Y-m-d');
                $timeKey = $cursor->format('H:i');

                if (!isset($occupied[$dateKey])) {
                    $occupied[$dateKey] = [];
                }

                if (!isset($occupied[$dateKey][$timeKey])) {
                    $occupied[$dateKey][$timeKey] = [];
                }

                $occupied[$dateKey][$timeKey][$scheduledBay] = true;
            }
        }

        return $occupied;
    }
}

if (!function_exists('applyBayOccupancyToSlots')) {
    function applyBayOccupancyToSlots(
        string $dateKey,
        array $slots,
        array $occupiedBayIndex,
        array $allowedBays,
        DateTimeZone $timezone
    ): array {
        $result = [];
        $totalBays = count($allowedBays);

        if ($totalBays <= 0) {
            return [];
        }

        foreach ($slots as $slot) {
            $normalized = normalizeScheduleSlot($slot);
            $timeKey = normalizeScheduleTimeKey($normalized['time'], $timezone);

            if ($timeKey === null) {
                continue;
            }

            $occupiedAtTime = array_keys($occupiedBayIndex[$dateKey][$timeKey] ?? []);
            $occupiedAllowedBays = array_intersect($allowedBays, $occupiedAtTime);
            $occupiedCount = count($occupiedAllowedBays);

            $remainingCapacity = max(0, $totalBays - $occupiedCount);

            if ($remainingCapacity <= 0) {
                continue;
            }

            $normalized['available'] = true;
            $normalized['capacity'] = $remainingCapacity;
            $normalized['allowedBays'] = array_values($allowedBays);

            $result[] = $normalized;
        }

        return $result;
    }
}

/*
|--------------------------------------------------------------------------
| Build occupied index from services.json
|--------------------------------------------------------------------------
*/

$serviceRows = loadServicesJson($servicesJsonPath);

$occupiedBayIndex = buildOccupiedBayIndex(
    $serviceRows,
    $allPhysicalBays,
    $timezone,
    $slotIntervalMinutes
);

/*
|--------------------------------------------------------------------------
| Build final schedule slots
|--------------------------------------------------------------------------
*/

$scheduleSlots = [];

for (
    $cursor = $rangeStart;
    $cursor <= $rangeEnd;
    $cursor = $cursor->modify('+1 day')
) {
    $dateKey = $cursor->format('Y-m-d');
    $weekday = (int) $cursor->format('N');

    if (dateIsBlocked($dateKey, $blockedDates)) {
        continue;
    }

    if (array_key_exists($dateKey, $dateOverrides)) {
        $slots = array_map('normalizeScheduleSlot', $dateOverrides[$dateKey]);

        // Optional:
        // $slots = applyBayOccupancyToSlots(
        //     $dateKey,
        //     $slots,
        //     $occupiedBayIndex,
        //     $serviceBays,
        //     $timezone
        // );

        $scheduleSlots[$dateKey] = $slots;
        continue;
    }

    $templateSlots = $weeklyTemplate[$weekday] ?? [];

    if (!$templateSlots) {
        continue;
    }

    if (dateIsFullyBooked($dateKey, $fullyBookedDates)) {
        $scheduleSlots[$dateKey] = buildFullyBookedSlots($templateSlots);
        continue;
    }

    $templateSlots = applyBayOccupancyToSlots(
        $dateKey,
        $templateSlots,
        $occupiedBayIndex,
        $serviceBays,
        $timezone
    );

    if (!$templateSlots) {
        continue;
    }

    $scheduleSlots[$dateKey] = array_map('normalizeScheduleSlot', $templateSlots);
}