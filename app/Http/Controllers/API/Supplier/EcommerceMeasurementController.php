<?php

namespace App\Http\Controllers\API\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\DeleteEcommerceMeasurementRequest;
use App\Http\Requests\Supplier\ListEcommerceMeasurementRequest;
use App\Http\Requests\Supplier\ShowEcommerceMeasurementRequest;
use App\Http\Requests\Supplier\StoreEcommerceMeasurementRequest;
use App\Http\Requests\Supplier\UpdateEcommerceMeasurementRequest;
use App\Http\Resources\EcommerceMeasurementResource;
use App\Models\EcommerceMeasurement;
use App\Services\Admin\EcommerceMeasurementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class EcommerceMeasurementController extends Controller
{
    public function __construct(private EcommerceMeasurementService $measurementService) {}

    /**
     * List all ecommerce measurements.
     *
     * @param ListEcommerceMeasurementRequest $request
     * @return JsonResponse
     */
    public function index(ListEcommerceMeasurementRequest $request): JsonResponse
    {
        $measurements = EcommerceMeasurement::latest()->businesses()->paginate();

        return $this->returnJsonResponse(
            message: 'Measurements successfully fetched.',
            data: EcommerceMeasurementResource::collection($measurements)->response()->getData(true)
        );
    }

    /**
     * Store a new ecommerce measurement.
     *
     * @param StoreEcommerceMeasurementRequest $request
     * @return JsonResponse
     */
    public function store(StoreEcommerceMeasurementRequest $request): JsonResponse
    {
        $measurement = $this->measurementService->store(
            $request->validated(),
            $request->user()
        );

        return $measurement
            ? $this->returnJsonResponse(
                message: 'Measurement successfully created.',
                data: new EcommerceMeasurementResource($measurement)
            )
            : $this->returnJsonResponse(
                message: 'Oops, cannot create measurement at the moment. Please try again later.'
            );
    }

    /**
     * Show an ecommerce measurement.
     *
     * @param ShowEcommerceMeasurementRequest $request
     * @return JsonResponse
     */
    public function show(ShowEcommerceMeasurementRequest $request, EcommerceMeasurement $measurement): JsonResponse
    {
        return $measurement
            ? $this->returnJsonResponse(
                message: 'Measurement successfully fetched.',
                data: new EcommerceMeasurementResource($measurement)
            )
            : $this->returnJsonResponse(
                message: 'Oops, cannot fetch measurement at the moment. Please try again later.'
            );
    }

    /**
     * Update an existing ecommerce measurement.
     *
     * @param UpdateEcommerceMeasurementRequest $request
     * @param EcommerceMeasurement $measurement
     * @return JsonResponse
     */
    public function update(UpdateEcommerceMeasurementRequest $request, EcommerceMeasurement $measurement): JsonResponse
    {
        $isUpdated = $this->measurementService->update(
            $request->validated(),
            $request->user(),
            $measurement
        );

        return $isUpdated
            ? $this->returnJsonResponse(
                message: 'Measurement successfully updated.',
                data: new EcommerceMeasurementResource($measurement->refresh())
            )
            : $this->returnJsonResponse(
                message: 'Oops, cannot update measurement at the moment. Please try again later.'
            );
    }

    /**
     * Delete an ecommerce measurement.
     *
     * @param DeleteEcommerceMeasurementRequest $request
     * @param EcommerceMeasurement $measurement
     * @return JsonResponse
     */
    public function destroy(DeleteEcommerceMeasurementRequest $request, EcommerceMeasurement $measurement): JsonResponse
    {
        $isDeleted = $this->measurementService->delete($measurement);

        return $isDeleted
            ? $this->returnJsonResponse(
                message: 'Measurement successfully deleted.',
                statusCode: Response::HTTP_OK
            )
            : $this->returnJsonResponse(
                message: 'Cannot delete this measurement because it has associated products.',
                statusCode: Response::HTTP_BAD_REQUEST
            );
    }
}
