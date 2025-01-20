<?php

namespace App\Http\Controllers\Supplier;

use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\EcommerceCategoryResource;
use App\Models\EcommerceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcommerceCategoryController extends Controller
{
    /**
     * Handle the retrieval of ecommerce categories for the supplier.
     *
     * This method fetches ecommerce categories associated with the authenticated supplier.
     * It supports filtering by search term, active status, and sorting by specific fields.
     * The response includes paginated brand data formatted as resources.
     *
     * @param Request $request The incoming HTTP request containing query parameters.
     * @return JsonResponse The paginated list of categories and a success message.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $business = $user->ownerBusinessType
        ?? $user->businesses()->firstWhere('user_id', $user->id);

        // Build query for fetching ecommerce categories
        $query = EcommerceCategory::where('business_id', $business?->id)
            ->orWhere(fn($query) => $query->whereNull('business_id')->where('active', 1)->whereIn('status', StatusEnum::actives()) )
            ->when(
                $request->input('search'),
                fn($query, $search) => $query->where('name', 'like', "%{$search}%")
            )
            ->when(
                $request->input('status'),
                fn($query, $status) => $query->where('active', $status === 'active' ? 1 : 0)
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

        $categories = $query
            ->paginate($request->input('perPage', 10))
            ->withQueryString()
            ->through(fn(EcommerceCategory $item) => EcommerceCategoryResource::make($item));

        return $this->returnJsonResponse(
            message: 'Categories successfully fetched.',
            data: $categories
        );
    }
}
