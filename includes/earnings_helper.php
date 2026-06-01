<?php
/**
 * Zesto — Earnings & Fare Calculation Helper
 */

class EarningsHelper {
    private static ?array $settings = null;

    /**
     * Fetch settings row once per request
     */
    public static function getSettings(): array {
        if (self::$settings === null) {
            try {
                $stmt = db()->query("SELECT * FROM delivery_settings WHERE id = 1 LIMIT 1");
                $res = $stmt->fetch();
                if ($res) {
                    self::$settings = $res;
                }
            } catch (Exception $e) {
                // Fallback
            }

            if (!self::$settings) {
                self::$settings = [
                    'base_fare' => 40.00,
                    'per_km_charge' => 5.00,
                    'min_delivery_charge' => 40.00,
                    'peak_hour_bonus' => 0.00,
                    'rain_bonus' => 0.00,
                    'festival_bonus' => 0.00,
                ];
            }
        }
        return self::$settings;
    }

    /**
     * Calculate delivery earnings details for a given distance in kilometers.
     */
    public static function calculate(float $distance): array {
        $settings = self::getSettings();
        
        $baseFare = (float)$settings['base_fare'];
        $perKmCharge = (float)$settings['per_km_charge'];
        $minCharge = (float)$settings['min_delivery_charge'];
        $peakHour = (float)$settings['peak_hour_bonus'];
        $rainBonus = (float)$settings['rain_bonus'];
        $festivalBonus = (float)$settings['festival_bonus'];

        $distanceCharge = round($distance * $perKmCharge, 2);
        
        $calculatedTotal = $baseFare + $distanceCharge + $peakHour + $rainBonus + $festivalBonus;
        $totalEarnings = max($minCharge, $calculatedTotal);

        return [
            'base_fare' => $baseFare,
            'distance_charge' => $distanceCharge,
            'peak_hour_bonus' => $peakHour,
            'rain_bonus' => $rainBonus,
            'festival_bonus' => $festivalBonus,
            'total_earnings' => round($totalEarnings, 2),
            'distance_travelled' => round($distance, 2)
        ];
    }
}
