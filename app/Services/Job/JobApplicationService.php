<?php

namespace App\Services\Job;

use App\Mail\JobApplicationSubmitted;
use App\Models\Jobs\JobApplication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;

class JobApplicationService
{
    public function submit(array $payload): JobApplication
    {
        /** @var UploadedFile|null $resumeFile */
        $resumeFile = $payload['resume'] ?? null;

        // Don't store resume, remove from payload
        unset($payload['resume']);
        $payload['resume'] = null;

        $application = JobApplication::create($payload);

        // Pass raw file data directly to email, don't store anywhere
        $resumeData = null;
        $resumeFileName = null;
        $resumeMimeType = null;

        if ($resumeFile instanceof UploadedFile) {
            $resumeData = file_get_contents($resumeFile->getRealPath());
            $resumeFileName = $resumeFile->getClientOriginalName();
            $resumeMimeType = $resumeFile->getMimeType();
        }

        Mail::to(config('jobs.applications.notification_email'))
            ->send(new JobApplicationSubmitted($application, $resumeData, $resumeFileName, $resumeMimeType));

        return $application;
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return JobApplication::query()
            ->when(
                Arr::get($filters, 'search'),
                fn ($query, $search) => $query->where(function ($builder) use ($search) {
                    $builder->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                })
            )
            ->when(
                Arr::get($filters, 'salary_type'),
                fn ($query, $salaryType) => $query->where('salary_type', $salaryType)
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}
