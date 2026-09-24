<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveTransportDayRequest;
use App\Models\Job;
use App\Models\TransportDay;
use App\Services\Scheduling\TransportDayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TransportDayController extends Controller
{
    public function __construct(private readonly TransportDayManager $manager) {}

    public function index(): View
    {
        return view('transport-days.index', [
            'transportDays' => TransportDay::query()
                ->withCount('jobs')
                ->orderByDesc('run_date')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('transport-days.create');
    }

    public function store(SaveTransportDayRequest $request): RedirectResponse
    {
        $transportDay = $this->manager->create($request->validated());

        return redirect()
            ->route('transport-days.show', ['transportDay' => $transportDay])
            ->with('status', 'Transport day created.');
    }

    public function show(TransportDay $transportDay): View
    {
        return view('transport-days.show', [
            'transportDay' => $transportDay,
            'memberJobs' => $transportDay->jobs()->with('customer', 'currentWorkingRevision')->get(),
            'assignableJobs' => Job::query()
                ->whereNull('transport_day_id')
                ->where('status', '!=', 'lost')
                ->with('customer', 'currentWorkingRevision')
                ->orderByDesc('updated_at')
                ->get(),
        ]);
    }

    public function update(TransportDay $transportDay, SaveTransportDayRequest $request): RedirectResponse
    {
        $this->manager->update($transportDay, $request->validated());

        return redirect()
            ->route('transport-days.show', ['transportDay' => $transportDay])
            ->with('status', 'Transport day updated.');
    }

    public function addJob(TransportDay $transportDay): RedirectResponse
    {
        $data = request()->validate([
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
        ]);

        $job = Job::query()->findOrFail($data['job_id']);

        if ($job->status === 'lost') {
            throw ValidationException::withMessages([
                'job_id' => 'A lost job cannot be scheduled into a transport day.',
            ]);
        }

        $this->manager->addJob($transportDay, $job);

        return redirect()->route('transport-days.show', ['transportDay' => $transportDay]);
    }

    public function removeJob(TransportDay $transportDay, Job $job): RedirectResponse
    {
        $this->assertMembership($transportDay, $job);
        $this->manager->removeJob($job);

        return redirect()->route('transport-days.show', ['transportDay' => $transportDay]);
    }

    public function moveJobUp(TransportDay $transportDay, Job $job): RedirectResponse
    {
        $this->assertMembership($transportDay, $job);
        $this->manager->moveJobUp($job);

        return redirect()->route('transport-days.show', ['transportDay' => $transportDay]);
    }

    public function moveJobDown(TransportDay $transportDay, Job $job): RedirectResponse
    {
        $this->assertMembership($transportDay, $job);
        $this->manager->moveJobDown($job);

        return redirect()->route('transport-days.show', ['transportDay' => $transportDay]);
    }

    private function assertMembership(TransportDay $transportDay, Job $job): void
    {
        abort_unless($job->transport_day_id === $transportDay->id, 404);
    }
}
