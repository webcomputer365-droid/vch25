<?php
declare(strict_types=1);

function clock_label(int $minutes): string
{
    $dayOffset = intdiv(max(0, $minutes), 1440);
    $clock = date('g:i A', strtotime('00:00') + (($minutes % 1440) * 60));
    return $dayOffset > 0 ? $clock . ' +' . $dayOffset . ' day' : $clock;
}

function build_day_plan(array $places, string $startTime, string $pace): array
{
    $paceSettings = [
        'relaxed' => ['visit' => 1.2, 'label' => 'Relaxed'],
        'balanced' => ['visit' => 1.0, 'label' => 'Balanced'],
        'quick' => ['visit' => 0.8, 'label' => 'Quick'],
    ];
    if (!isset($paceSettings[$pace])) {
        $pace = 'balanced';
    }
    $startParts = array_map('intval', explode(':', $startTime));
    $cursor = ($startParts[0] * 60) + ($startParts[1] ?? 0);
    $originLat = HOME_LAT;
    $originLng = HOME_LNG;
    $remaining = array_values($places);
    $items = [];
    $warnings = [];
    $totalDistance = 0.0;
    $totalTravelTime = 0;
    $totalVisitTime = 0;

    while ($remaining) {
        $nearestIndex = 0;
        $nearestDistance = INF;
        foreach ($remaining as $index => $candidate) {
            $distance = haversine_km(
                $originLat,
                $originLng,
                (float) $candidate['latitude'],
                (float) $candidate['longitude']
            );
            if ($distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearestIndex = $index;
            }
        }
        $place = $remaining[$nearestIndex];
        array_splice($remaining, $nearestIndex, 1);

        // Road mileage/time are estimates derived from straight-line coordinates.
        $roadKm = $nearestDistance * 1.25;
        $travelMinutes = $nearestDistance < 0.25 ? 5 : max(5, (int) ceil($roadKm / 25 * 60));
        $cursor += $travelMinutes;
        $arrival = $cursor;
        $placeWarnings = [];

        if (!empty($place['opening_time']) && !empty($place['closing_time'])) {
            $openingParts = array_map('intval', explode(':', substr((string) $place['opening_time'], 0, 5)));
            $closingParts = array_map('intval', explode(':', substr((string) $place['closing_time'], 0, 5)));
            $opening = ($openingParts[0] * 60) + ($openingParts[1] ?? 0);
            $closing = ($closingParts[0] * 60) + ($closingParts[1] ?? 0);
            if ($arrival < $opening) {
                $placeWarnings[] = 'Arrive before opening; allow ' . format_duration($opening - $arrival) . ' waiting time.';
                $cursor = $opening;
            } elseif ($arrival >= $closing) {
                $placeWarnings[] = 'Estimated arrival is at or after the listed closing time. Confirm access locally.';
            }
        } else {
            $placeWarnings[] = 'Opening times are not listed. Confirm access before travelling.';
        }

        $visitStart = $cursor;
        $visitMinutes = max(20, (int) round((int) $place['visit_duration'] * $paceSettings[$pace]['visit']));
        $visitEnd = $visitStart + $visitMinutes;
        if (!empty($place['closing_time'])) {
            $closingParts = array_map('intval', explode(':', substr((string) $place['closing_time'], 0, 5)));
            $closing = ($closingParts[0] * 60) + ($closingParts[1] ?? 0);
            if ($visitEnd > $closing && $arrival < $closing) {
                $placeWarnings[] = 'The estimated visit may run past the listed closing time.';
            }
        }

        $items[] = [
            'place' => $place,
            'distance_km' => round($roadKm, 1),
            'travel_minutes' => $travelMinutes,
            'arrival_minutes' => $arrival,
            'visit_start_minutes' => $visitStart,
            'visit_end_minutes' => $visitEnd,
            'visit_minutes' => $visitMinutes,
            'warnings' => $placeWarnings,
        ];
        foreach ($placeWarnings as $warning) {
            $warnings[] = $place['name'] . ': ' . $warning;
        }
        $totalDistance += $roadKm;
        $totalTravelTime += $travelMinutes;
        $totalVisitTime += $visitMinutes;
        $cursor = $visitEnd;
        $originLat = (float) $place['latitude'];
        $originLng = (float) $place['longitude'];
    }

    return [
        'items' => $items,
        'warnings' => $warnings,
        'total_distance' => round($totalDistance, 1),
        'total_travel_time' => $totalTravelTime,
        'total_visit_time' => $totalVisitTime,
        'total_duration' => $totalTravelTime + $totalVisitTime,
        'start_minutes' => (int) (array_map('intval', explode(':', $startTime))[0] * 60 + (array_map('intval', explode(':', $startTime))[1] ?? 0)),
        'end_minutes' => $cursor,
        'pace' => $pace,
        'pace_label' => $paceSettings[$pace]['label'],
    ];
}
