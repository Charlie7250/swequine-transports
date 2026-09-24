<?php

namespace App\Support;

class DashboardPrototypeFixture
{
    public static function make(): array
    {
        return [
            'summary' => self::summary(),
            'days' => self::days(),
            'unassigned_jobs' => self::unassignedJobs(),
        ];
    }

    private static function summary(): array
    {
        return [
            ['label' => 'Today', 'value' => '1 day', 'meta' => '3 jobs scheduled today', 'icon' => 'calendar'],
            ['label' => 'Awaiting response', 'value' => '2 quotes', 'meta' => 'Pending now', 'icon' => 'document'],
            ['label' => 'Booked ahead', 'value' => '4 jobs', 'meta' => 'Next 7 days', 'icon' => 'truck'],
            ['label' => 'Completed', 'value' => '6 jobs', 'meta' => 'Last 7 days', 'icon' => 'check'],
            ['label' => 'Open quoted value', 'value' => '£1,840', 'meta' => 'Pending and quoted jobs', 'icon' => 'chart'],
        ];
    }

    private static function days(): array
    {
        return [
            [
                'date' => 'Sunday 20 September 2026',
                'name' => 'Devon and Somerset run',
                'depot' => 'EX15 1AA',
                'job_count' => 3,
                'miles' => 186,
                'duration' => '5 hr 40 min',
                'is_open' => true,
                'jobs' => [
                    self::job(1047, 1, 'North Devon Veterinary Referral and Rehabilitation Centre', 2, 'EX31 4JB', 'TA4 3TP', 'booked', '£620'),
                    self::job(1052, 2, 'Moorland Equestrian', 1, 'TA4 3TP', 'EX20 4LU', 'pending', '£285'),
                    self::job(1055, 3, 'Harriet Collins', 1, 'EX20 4LU', 'EX15 1AA', 'quoted', '£340'),
                ],
            ],
            [
                'date' => 'Monday 21 September 2026',
                'name' => 'Cornwall collections',
                'depot' => 'EX15 1AA',
                'job_count' => 2,
                'miles' => 214,
                'duration' => '6 hr 15 min',
                'is_open' => false,
                'jobs' => [
                    self::job(1061, 1, 'Redbrook Livery', 1, 'PL15 8DF', 'EX23 0LA', 'booked', '£410'),
                    self::job(1064, 2, 'Penrose Sport Horses', 2, 'EX23 0LA', 'TR3 6AG', 'draft', null),
                ],
            ],
            [
                'date' => 'Tuesday 22 September 2026',
                'name' => null,
                'depot' => 'EX15 1AA',
                'job_count' => 0,
                'miles' => null,
                'duration' => null,
                'is_open' => false,
                'jobs' => [],
            ],
        ];
    }

    private static function unassignedJobs(): array
    {
        return [
            self::job(1070, null, 'Willowbank Equestrian Partnership', 1, 'EX17 6AA', null, 'draft', null),
        ];
    }

    private static function job(
        int $id,
        ?int $sequence,
        string $customer,
        int $horseCount,
        ?string $pickup,
        ?string $dropoff,
        string $status,
        ?string $value,
    ): array {
        return [
            'id' => $id,
            'sequence' => $sequence,
            'customer' => $customer,
            'horse_count' => $horseCount,
            'pickup' => $pickup,
            'dropoff' => $dropoff,
            'status' => $status,
            'value' => $value,
        ];
    }
}
