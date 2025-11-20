<?php

namespace App\Services\Job;

use App\Models\Jobs\Job;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class JobService
{
    /**
     * Retrieve paginated jobs with optional filters.
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Job::query()
            ->when(
                Arr::get($filters, 'search'),
                fn (Builder $query, $search) => $query->where(function (Builder $builder) use ($search) {
                    $builder->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('department', 'LIKE', "%{$search}%")
                        ->orWhere('mission', 'LIKE', "%{$search}%");
                })
            )
            ->when(
                Arr::get($filters, 'department'),
                fn (Builder $query, $department) => $query->where('department', $department)
            )
            ->when(
                Arr::get($filters, 'status'),
                fn (Builder $query, $status) => $query->where('status', $this->normalizeStatus($status))
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create a new job.
     */
    public function store(array $payload): Job
    {
        $data = $this->preparePayload($payload);

        return Job::create($data);
    }

    /**
     * Update an existing job.
     */
    public function update(Job $job, array $payload): Job
    {
        $data = $this->preparePayload($payload);
        $job->update($data);

        return $job->refresh();
    }

    /**
     * Delete a job.
     */
    public function delete(Job $job): bool
    {
        return (bool) $job->delete();
    }

    /**
     * Ensure payload aligns with DB expectations.
     */
    protected function preparePayload(array $payload): array
    {
        if (isset($payload['status'])) {
            $payload['status'] = $this->normalizeStatus($payload['status']);
        }

        if (isset($payload['employment_type']) && is_array($payload['employment_type'])) {
            $payload['employment_type'] = array_values(array_filter($payload['employment_type']));
        }

        if (isset($payload['requirements']) && is_array($payload['requirements'])) {
            $payload['requirements'] = array_values(array_filter($payload['requirements']));
        }

        if (! isset($payload['slug']) && isset($payload['title'])) {
            $payload['slug'] = Str::slug($payload['title']).'-'.Str::random(6);
        }

        return $payload;
    }

    protected function normalizeStatus(string $status): string
    {
        return strtoupper($status);
    }
}
