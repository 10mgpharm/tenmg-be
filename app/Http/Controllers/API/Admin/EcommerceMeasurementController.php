<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteEcommerceMeasurementRequest;
use App\Http\Requests\Admin\ListEcommerceMeasurementRequest;
use App\Http\Requests\Admin\ShowEcommerceMeasurementRequest;
use App\Http\Requests\Admin\StoreEcommerceMeasurementRequest;
use App\Http\Requests\Admin\UpdateEcommerceMeasurementRequest;
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
     */
    public function index(ListEcommerceMeasurementRequest $request): JsonResponse
    {
        $measurements = EcommerceMeasurement::where('active', 1)->where('status', 'APPROVED')->get();

        return $this->returnJsonResponse(
            message: 'Measurements successfully fetched.',
            data: EcommerceMeasurementResource::collection($measurements)
        );
    }

    /**
     * Store a new ecommerce measurement.
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

    /**
     * Search and filter EcommerceMeasurements based on the provided criteria.
     *
     * @param ListEcommerceMeasurementRequest $request The incoming request containing search, filter, and pagination parameters.
     * @return JsonResponse A JSON response with the paginated list of presentations.
     */
    public function search(ListEcommerceMeasurementRequest $request): JsonResponse
    {
        $query = EcommerceMeasurement::query()
            ->when($request->input('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', strtoupper($status));
            })
            ->when($request->input('active'), function ($query, $active) {
                $query->where('active', '=', $active == 'active' ? 1 : 0);
            });

        if ($request->has('sort') && $request->has('order')) {
            $sortColumn = $request->input('sort');
            $sortOrder = $request->input('order');

            $validColumns = ['name'];
            if (in_array($sortColumn, $validColumns) && in_array(strtolower($sortOrder), ['asc', 'desc'])) {
                $query->orderBy($sortColumn, $sortOrder);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $measurement = $query
            ->paginate($request->has('perPage') ? $request->perPage : 10)
            ->withQueryString()
            ->through(fn (EcommerceMeasurement $item) => EcommerceMeasurementResource::make($item));

        return $this->returnJsonResponse(
            message: 'Measurement successfully fetched.',
            data: $measurement
        );
    }
}
