<?php

namespace App\Http\Controllers\Supplier;

use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\EcommerceMedicationTypeResource;
use App\Models\EcommerceMedicationType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcommerceMedicationTypeController extends Controller
{
    /**
     * Handle the retrieval of ecommerce medication types for the supplier.
     *
     * This method fetches ecommerce medication types associated with the authenticated supplier.
     * It supports filtering by search term, active status, and sorting by specific fields.
     * The response includes paginated brand data formatted as resources.
     *
     * @param Request $request The incoming HTTP request containing query parameters.
     * @return JsonResponse The paginated list of medication types and a success message.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $business = $user->ownerBusinessType
        ?? $user->businesses()->firstWhere('user_id', $user->id);

        // Build query for fetching ecommerce medication types
        $query = EcommerceMedicationType::where('business_id', $business?->id)
            ->orWhere(fn($query) => $query->whereNull('business_id')->where('active', 1)->whereIn('status',  StatusEnum::actives()) )
            ->when(
                $request->input('search'),
                fn($query, $search) => $query->where('name', 'like', "%{$search}%")
            )
            ->when(
                $request->input('status'),
                fn($query, $status) => $query->whereIn(
                    'status', 
                    is_array($status)
                        ? array_unique(array_merge(...array_map(fn($s) => StatusEnum::mapper(trim($s)), $status)))
                        : array_unique(array_merge(...array_map(fn($s) => StatusEnum::mapper(trim($s)), explode(",", $status))))
                )
            )
            
            // Filter by active status (active/inactive mapped to 1/0)
            ->when(
                $request->input('active'),
                fn($query, $active) =>
                $query->where('active', '=', $active == 'active' ? 1 : 0)
            );

        // Apply sorting if specified
        if ($request->has('sort') && $request->has('order')) {
            $sortColumn = $request->input('sort');
            $sortOrder = $request->input('order');

            // Validate sorting column and order
            $validColumns = ['name'];
            if (in_array($sortColumn, $validColumns) && in_array(strtolower($sortOrder), ['asc', 'desc'])) {
                $query->orderBy($sortColumn, $sortOrder);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $medication_types = $query
            ->paginate($request->input('perPage', 10))
            ->withQueryString()
            ->through(fn(EcommerceMedicationType $item) => EcommerceMedicationTypeResource::make($item));

        return $this->returnJsonResponse(
            message: 'Medication types successfully fetched.',
            data: $medication_types
        );
    }
}
