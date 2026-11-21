<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Device;
use App\Models\TelemetryLog;
use App\Models\Rule;

/**
 * Generates realistic sensor physics (Sine/Noise waves, Brownian drift, harmonic vibration, anomalies).
 */
class SimulationService
{
    private Device $deviceModel;
    private TelemetryLog $telemetryModel;
    private Rule $ruleModel;

    public function __construct()
    {
        $this->deviceModel = new Device();
        $this->telemetryModel = new TelemetryLog();
        $this->ruleModel = new Rule();
    }

    /**
     * Compute realistic physics telemetry point based on sensor type and time.
     *
     * @param array<string, mixed> $device
     * @param float|null $previousValue
     * @return array{metric: string, value: float, unit: string}
     */
    public function generateNextReading(array $device, ?float $previousValue = null): array
    {
        $type = strtolower($device['type'] ?? 'temperature');
        $t = microtime(true);
        $randSeed = mt_rand(0, 1000) / 1000.0;

        switch ($type) {
            case 'radiation':
                // Base background radiation in µSv/h: 0.12 to 0.28 µSv/h
                // Occasional micro-flux spike if random < 0.05
                $base = 0.16 + (sin($t / 12.0) * 0.04);
                $noise = ($randSeed - 0.5) * 0.06;
                $spike = (mt_rand(1, 100) > 96) ? (mt_rand(40, 75) / 100.0) : 0.0;
                $value = max(0.02, $base + $noise + $spike);
                return [
                    'metric' => 'radiation',
                    'value' => round($value, 3),
                    'unit' => 'µSv/h',
                ];

            case 'temperature':
                // Core thermal dynamics: 35°C to 75°C normal, occasional thermal excursion > 82°C
                $base = 52.0 + (sin($t / 14.0) * 18.0);
                $flutter = cos($t / 3.0) * 3.5;
                $noise = ($randSeed - 0.5) * 2.0;
                $spike = (mt_rand(1, 100) > 95) ? mt_rand(15, 26) : 0.0;
                $value = max(15.0, $base + $flutter + $noise + $spike);
                return [
                    'metric' => 'temperature',
                    'value' => round($value, 1),
                    'unit' => '°C',
                ];

            case 'pressure':
                // Hydraulic/Pneumatic oscillation: 6.0 to 11.5 bar, spike > 12 bar
                $base = 8.5 + (sin($t / 9.0) * 2.2);
                $pulse = sin($t * 2.5) * 0.45;
                $noise = ($randSeed - 0.5) * 0.35;
                $spike = (mt_rand(1, 100) > 96) ? (mt_rand(25, 45) / 10.0) : 0.0;
                $value = max(1.0, $base + $pulse + $noise + $spike);
                return [
                    'metric' => 'pressure',
                    'value' => round($value, 2),
                    'unit' => 'bar',
                ];

            case 'humidity':
                // Atmospheric cleanroom humidity: 40% to 65% RH, occasional surge > 70%
                $base = 48.0 + (cos($t / 18.0) * 14.0);
                $jitter = ($randSeed - 0.5) * 3.0;
                $spike = (mt_rand(1, 100) > 96) ? mt_rand(14, 25) : 0.0;
                $value = max(10.0, min(99.0, $base + $jitter + $spike));
                return [
                    'metric' => 'humidity',
                    'value' => round($value, 1),
                    'unit' => '%RH',
                ];

            case 'voltage':
                // Substation AC phase voltage: ~230V, occasional sag < 210V
                $base = 230.0 + (sin($t / 6.0) * 5.0);
                $ripple = ($randSeed - 0.5) * 4.0;
                $sag = (mt_rand(1, 100) > 95) ? -mt_rand(22, 35) : 0.0;
                $value = max(180.0, $base + $ripple + $sag);
                return [
                    'metric' => 'voltage',
                    'value' => round($value, 1),
                    'unit' => 'V',
                ];

            case 'vibration':
                // Gyro/rotor harmonic resonance: 1.0 to 5.0 mm/s, resonance breach > 6.5 mm/s
                $base = 2.8 + (abs(sin($t * 1.8)) * 2.4);
                $harmonics = cos($t * 4.0) * 0.8;
                $noise = ($randSeed - 0.5) * 0.6;
                $spike = (mt_rand(1, 100) > 96) ? (mt_rand(25, 45) / 10.0) : 0.0;
                $value = max(0.1, $base + $harmonics + $noise + $spike);
                return [
                    'metric' => 'vibration',
                    'value' => round($value, 2),
                    'unit' => 'mm/s',
                ];

            default:
                // Generic metric simulation
                $value = 50.0 + (sin($t / 10.0) * 20.0) + (($randSeed - 0.5) * 5.0);
                return [
                    'metric' => $type,
                    'value' => round($value, 2),
                    'unit' => 'units',
                ];
        }
    }

    /**
     * Executes one simulation cycle across all active mesh nodes.
     * Logs readings, evaluates rules, creates incidents, and updates node states.
     *
     * @return array<string, mixed>
     */
    public function simulateAllActiveDevices(): array
    {
        $devices = $this->deviceModel->getOnlineDevices();
        $generatedReadings = [];
        $allIncidents = [];
        $now = date('Y-m-d H:i:s');
        $timeLabel = date('H:i:s');

        foreach ($devices as $device) {
            $reading = $this->generateNextReading($device);

            // Save reading in database
            $this->telemetryModel->log(
                $device['id'],
                $reading['metric'],
                $reading['value'],
                $reading['unit'],
                $now
            );

            // Evaluate active rules
            $incidents = $this->ruleModel->evaluate(
                $device['id'],
                $reading['metric'],
                $reading['value'],
                $device['name']
            );

            if (!empty($incidents)) {
                foreach ($incidents as $inc) {
                    $allIncidents[] = $inc;
                }
            } else {
                // If device was in warning/critical but now within safe range,
                // self-heal after normal reading
                if ($device['status'] === 'warning' && mt_rand(1, 10) > 7) {
                    $this->deviceModel->updateStatus($device['id'], 'online');
                }
            }

            $generatedReadings[] = [
                'device_id' => $device['id'],
                'device_name' => $device['name'],
                'type' => $device['type'],
                'location' => $device['location'],
                'status' => $device['status'],
                'metric' => $reading['metric'],
                'value' => $reading['value'],
                'unit' => $reading['unit'],
                'timestamp' => $now,
                'time_label' => $timeLabel,
            ];
        }

        // Fetch fresh snapshot for gauges
        $snapshot = $this->telemetryModel->getLatestSnapshot();
        $fleetStats = $this->deviceModel->getFleetStats();
        $aggregates = $this->telemetryModel->getAggregates();

        return [
            'timestamp' => $now,
            'time_label' => $timeLabel,
            'readings' => $generatedReadings,
            'snapshot' => $snapshot,
            'incidents' => $allIncidents,
            'fleet_stats' => $fleetStats,
            'aggregates' => $aggregates,
        ];
    }
}
