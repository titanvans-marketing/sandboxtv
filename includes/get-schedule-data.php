<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$sessionCart = isset($_SESSION['titan_service_cart']) && is_array($_SESSION['titan_service_cart'])
    ? array_values($_SESSION['titan_service_cart'])
    : [];

if (!function_exists('titanCanonicalizeScheduleServiceType')) {
    function titanCanonicalizeScheduleServiceType($value): string
    {
        $raw = strtolower(trim((string) $value));
        $raw = preg_replace('/\s+/', ' ', $raw);

        if (!is_string($raw) || $raw === '') {
            return '';
        }

        if (preg_match('/\bservice\s*a\b/', $raw)) {
            return 'service a';
        }

        if (preg_match('/\bservice\s*b\b/', $raw)) {
            return 'service b';
        }

        if (preg_match('/\btire\s*rotation\b/', $raw)) {
            return 'tire rotation';
        }

        if (preg_match('/\bwheel\s*alignment\b/', $raw)) {
            return 'wheel alignment';
        }

        if (preg_match('/\balignment\s*service\b/', $raw)) {
            return 'wheel alignment';
        }

        return '';
    }
}

if (!function_exists('titanExtractScheduleServiceTypesFromCart')) {
    function titanExtractScheduleServiceTypesFromCart(array $cart): array
    {
        $serviceTypes = [];

        foreach ($cart as $item) {
            if (!is_array($item)) {
                continue;
            }

            $requiresDate = array_key_exists('requiresDate', $item)
                ? (bool) $item['requiresDate']
                : true;

            if ($requiresDate === false) {
                continue;
            }

            $rawName = (string) (
                $item['serviceName']
                ?? $item['name']
                ?? $item['title']
                ?? ''
            );

            $canonical = titanCanonicalizeScheduleServiceType($rawName);

            if ($canonical !== '') {
                $serviceTypes[] = $canonical;
            }
        }

        return array_values(array_unique($serviceTypes));
    }
}

if (!function_exists('titanScheduleSlotsToFrontend')) {
    function titanScheduleSlotsToFrontend(array $scheduleSlots): array
    {
        $frontendSchedule = [];

        foreach ($scheduleSlots as $date => $slots) {
            $frontendSchedule[$date] = [];

            if (!is_array($slots)) {
                continue;
            }

            foreach ($slots as $slot) {
                $frontendSchedule[$date][] = [
                    'time' => (string) ($slot['time'] ?? ''),
                    'available' => isset($slot['available']) ? (bool) $slot['available'] : true,
                    'label' => (string) ($slot['label'] ?? ''),
                    'capacity' => isset($slot['capacity']) ? (int) $slot['capacity'] : null,
                    'allowedBays' => isset($slot['allowedBays']) && is_array($slot['allowedBays'])
                        ? array_values($slot['allowedBays'])
                        : [],
                ];
            }
        }

        return $frontendSchedule;
    }
}

$hasExplicitServiceRequest = false;

if (isset($_GET['services'])) {
    $servicesParam = $_GET['services'];

    if (is_array($servicesParam)) {
        $trimmed = array_values(array_filter(array_map(
            static fn($value): string => trim((string) $value),
            $servicesParam
        )));
        $hasExplicitServiceRequest = $trimmed !== [];
    } else {
        $hasExplicitServiceRequest = trim((string) $servicesParam) !== '';
    }
}

if (!$hasExplicitServiceRequest && isset($_GET['serviceType'])) {
    $hasExplicitServiceRequest = trim((string) $_GET['serviceType']) !== '';
}

if (!$hasExplicitServiceRequest) {
    $selectedServiceTypes = titanExtractScheduleServiceTypesFromCart($sessionCart);
}

try {
    require __DIR__ . '/../includes/schedule-data.php';

    $requestedServices = isset($requestedServiceTypes) && is_array($requestedServiceTypes)
        ? array_values($requestedServiceTypes)
        : (isset($selectedServiceTypes) && is_array($selectedServiceTypes)
            ? array_values($selectedServiceTypes)
            : []);

    echo json_encode([
        'ok' => true,
        'requestedServices' => $requestedServices,
        'schedule' => titanScheduleSlotsToFrontend(isset($scheduleSlots) && is_array($scheduleSlots) ? $scheduleSlots : []),
        'meta' => [
            'closedWeekdays' => isset($closedWeekdaysJs) && is_array($closedWeekdaysJs)
                ? array_values($closedWeekdaysJs)
                : [0, 5, 6],
            'allowedBays' => isset($serviceBays) && is_array($serviceBays)
                ? array_values($serviceBays)
                : [],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    exit;
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'message' => 'Unable to load schedule data.',
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    exit;
}