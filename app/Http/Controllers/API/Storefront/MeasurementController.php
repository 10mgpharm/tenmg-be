<?php

namespace App\Http\Controllers\API\Storefront;

use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\EcommerceMeasurementResource;
use App\Models\EcommerceMeasurement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeasurementController extends Controller
{
    /**
     * Search for active measurements based on given criteria.
     *
     * Filters results using optional parameters like search query, date range, 
     * and sorting preferences. Returns paginated results.
     *
     * @param  Request  $request  Incoming request containing search parameters.
     * @return JsonResponse  JSON response with paginated list of measurements.
     */
    public function search(Request $request): JsonResponse
    {
        $query = EcommerceMeasurement::where('active', 1)
            ->whereIn('status', StatusEnum::actives())
            ->when($request->input('search'), fn($query, $search) =>
                $query->where(fn($q) =>
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                )
            );

        if ($from = $request->input('fromDate')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('toDate')) {
            $query->whereDate('created_at', '<=', $to);
        }

        if (in_array($request->input('sort'), ['name']) && in_array(strtolower($request->input('order')), ['asc', 'desc'])) {
            $query->orderBy($request->input('sort'), $request->input('order'));
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $measurements = $query->paginate($request->input('perPage', 10))
            ->withQueryString()
            ->through(fn(EcommerceMeasurement $item) => new EcommerceMeasurementResource($item));

        return $this->returnJsonResponse(
            message: 'Measurements successfully fetched.',
            data: $measurements
        );
    }
}
