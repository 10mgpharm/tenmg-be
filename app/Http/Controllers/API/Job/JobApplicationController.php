<?php

namespace App\Http\Controllers\API\Job;

use App\Http\Controllers\Controller;
use App\Http\Requests\Job\StoreJobApplicationRequest;
use App\Http\Resources\Job\JobApplicationResource;
use App\Models\Jobs\JobApplication;
use App\Services\Job\JobApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobApplicationController extends Controller
{
    public function __construct(private JobApplicationService $jobApplicationService) {}

    public function index(Request $request): JsonResponse
    {
        $applications = $this->jobApplicationService->paginate(
            filters: $request->only(['search', 'salary_type']),
            perPage: (int) $request->get('perPage', 10)
        );

        $data = JobApplicationResource::collection($applications)->response()->getData(true);

        return $this->returnJsonResponse(
            message: 'Job applications retrieved successfully.',
            data: $data
        );
    }

    public function store(StoreJobApplicationRequest $request): JsonResponse
    {
        $application = $this->jobApplicationService->submit($request->validated());

        return $this->returnJsonResponse(
            message: 'Application submitted successfully.',
            data: new JobApplicationResource($application),
            statusCode: JsonResponse::HTTP_CREATED
        );
    }

    public function show(JobApplication $jobApplication): JsonResponse
    {
        return $this->returnJsonResponse(
            message: 'Job application fetched successfully.',
            data: new JobApplicationResource($jobApplication)
        );
    }
}
