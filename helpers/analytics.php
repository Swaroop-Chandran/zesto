<?php
/**
 * Zesto — Analytics Visualization Helpers
 */

/**
 * Format duration in seconds to a human-readable string (e.g. "12m 30s")
 */
function formatSecondsToDuration($seconds): string {
    if ($seconds === null || $seconds === false) {
        return 'N/A';
    }
    $sec = (int)$seconds;
    if ($sec < 60) {
        return $sec . 's';
    }
    $min = floor($sec / 60);
    $rem = $sec % 60;
    return $min . 'm ' . $rem . 's';
}

/**
 * Format a trend change percentage with indicators.
 */
function formatTrendIndicator(float $current, float $previous): string {
    if ($previous <= 0) {
        return '<span class="text-emerald-600 font-bold">New</span>';
    }
    $diff = (($current - $previous) / $previous) * 100;
    if ($diff > 0) {
        return '<span class="text-emerald-600 font-bold">▲ ' . round($diff, 1) . '%</span>';
    } elseif ($diff < 0) {
        return '<span class="text-red-500 font-bold">▼ ' . round(abs($diff), 1) . '%</span>';
    }
    return '<span class="text-gray-400 font-bold">0%</span>';
}
