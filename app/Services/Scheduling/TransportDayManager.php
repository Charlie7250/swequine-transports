<?php

namespace App\Services\Scheduling;

use App\Models\Job;
use App\Models\TransportDay;
use InvalidArgumentException;

class TransportDayManager
{
    public function create(array $attributes): TransportDay
    {
        return TransportDay::query()->create($attributes);
    }

    public function update(TransportDay $day, array $attributes): TransportDay
    {
        $day->update($attributes);

        return $day;
    }

    public function addJob(TransportDay $day, Job $job): void
    {
        if ($job->status === 'lost') {
            throw new InvalidArgumentException('A lost job cannot be scheduled into a transport day.');
        }

        $job->forceFill([
            'transport_day_id' => $day->id,
            'transport_day_sequence' => $this->nextSequence($day),
        ])->save();
    }

    public function removeJob(Job $job): void
    {
        $job->forceFill([
            'transport_day_id' => null,
            'transport_day_sequence' => null,
        ])->save();
    }

    public function moveJobUp(Job $job): void
    {
        $previous = $this->neighbour($job, 'previous');

        if ($previous !== null) {
            $this->swapSequences($job, $previous);
        }
    }

    public function moveJobDown(Job $job): void
    {
        $next = $this->neighbour($job, 'next');

        if ($next !== null) {
            $this->swapSequences($job, $next);
        }
    }

    private function nextSequence(TransportDay $day): int
    {
        return (int) $day->jobs()->max('transport_day_sequence') + 1;
    }

    private function neighbour(Job $job, string $direction): ?Job
    {
        $query = Job::query()
            ->where('transport_day_id', $job->transport_day_id)
            ->whereKeyNot($job->id);

        return $direction === 'previous'
            ? $query->where('transport_day_sequence', '<', $job->transport_day_sequence)
                ->orderByDesc('transport_day_sequence')
                ->first()
            : $query->where('transport_day_sequence', '>', $job->transport_day_sequence)
                ->orderBy('transport_day_sequence')
                ->first();
    }

    private function swapSequences(Job $job, Job $other): void
    {
        $jobSequence = $job->transport_day_sequence;

        $job->forceFill(['transport_day_sequence' => $other->transport_day_sequence])->save();
        $other->forceFill(['transport_day_sequence' => $jobSequence])->save();
    }
}
