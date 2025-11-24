<?php

namespace App\Http\Controllers\API\Job;

use App\Http\Controllers\Controller;
use App\Http\Requests\Job\StoreJobRequest;
use App\Http\Requests\Job\UpdateJobRequest;
use App\Http\Resources\Job\JobResource;
use App\Models\Jobs\Job;
use App\Services\Job\JobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function __construct(private JobService $jobService) {}

    public function index(Request $request): JsonResponse
    {
        $jobs = $this->jobService->paginate(
            filters: $request->only(['search', 'department', 'status']),
            perPage: (int) $request->get('perPage', 10)
        );

        $resources = JobResource::collection($jobs)->response()->getData(true);

        return $this->returnJsonResponse(
            message: 'Jobs retrieved successfully.',
            data: $resources
        );
    }

    public function store(StoreJobRequest $request): JsonResponse
    {
        $job = $this->jobService->store($request->validated());

        return $this->returnJsonResponse(
            message: 'Job created successfully.',
            data: new JobResource($job),
            statusCode: JsonResponse::HTTP_CREATED
        );
    }

    public function show(Job $job): JsonResponse
    {
        return $this->returnJsonResponse(
            message: 'Job fetched successfully.',
            data: new JobResource($job)
        );
    }

    public function update(UpdateJobRequest $request, Job $job): JsonResponse
    {
        $updatedJob = $this->jobService->update($job, $request->validated());

        return $this->returnJsonResponse(
            message: 'Job updated successfully.',
            data: new JobResource($updatedJob)
        );
    }

    public function destroy(Job $job): JsonResponse
    {
        $this->jobService->delete($job);

        return $this->returnJsonResponse(
            message: 'Job deleted successfully.'
        );
    }
}
