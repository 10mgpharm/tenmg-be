<?php

namespace App\Http\Controllers\API\Storefront;

use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\EcommerceMedicationTypeResource;
use App\Models\EcommerceMedicationType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicationTypeController extends Controller
{
    /**
     * Search for medication types based on filters such as name, date range, and sorting order.
     *
     * @param  Request  $request  The request containing search and filter parameters.
     * @return JsonResponse  A paginated JSON response containing filtered medication types.
     */
    public function search(Request $request): JsonResponse
    {
        $query = EcommerceMedicationType::where('active', 1)
            ->whereIn('status', StatusEnum::actives())
            ->when(
                $request->input('search'),
                fn($q, $search) =>
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
            )
            ->when(
                $request->input('fromDate'),
                fn($q, $from) =>
                $q->whereDate('created_at', '>=', $from)
            )
            ->when(
                $request->input('toDate'),
                fn($q, $to) =>
                $q->whereDate('created_at', '<=', $to)
            );

        $validColumns = ['name']; // Define valid sortable columns
        if (in_array($request->input('sort'), $validColumns) && in_array(strtolower($request->input('order', 'desc')), ['asc', 'desc'])) {
            $query->orderBy($request->input('sort'), $request->input('order'));
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $medicationTypes = $query->paginate($request->input('perPage', 10))
            ->withQueryString()
            ->through(fn(EcommerceMedicationType $item) => new EcommerceMedicationTypeResource($item));

        return $this->returnJsonResponse(
            message: 'Medication types successfully fetched.',
            data: $medicationTypes
        );
    }
}
