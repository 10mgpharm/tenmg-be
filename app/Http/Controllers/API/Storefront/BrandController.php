<?php

namespace App\Http\Controllers\API\Storefront;

use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\EcommerceBrandResource;
use App\Models\EcommerceBrand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    /**
     * Search for brands based on given criteria.
     *
     * Filters include name, slug, date range, and sorting options.
     * Results are returned in a paginated JSON format.
     *
     * @param  Request  $request  Incoming request with search parameters.
     * @return JsonResponse Returns a JSON response with a paginated list of brands.
     */
    public function search(Request $request): JsonResponse
    {
        $query = EcommerceBrand::where('active', 1)
            ->whereIn('status', StatusEnum::actives())
            ->when($request->input('search'), fn($query, $search) => $query->where(
                fn($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
            ))
            ->when($request->input('fromDate'), fn($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($request->input('toDate'), fn($query, $to) => $query->whereDate('created_at', '<=', $to));

        if (in_array($request->input('sort'), ['name']) && in_array(strtolower($request->input('order')), ['asc', 'desc'])) {
            $query->orderBy($request->input('sort'), $request->input('order'));
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $brands = $query->paginate($request->input('perPage', 10))
            ->withQueryString()
            ->through(fn(EcommerceBrand $item) => EcommerceBrandResource::make($item));

        return $this->returnJsonResponse(
            message: 'Brands successfully fetched.',
            data: $brands
        );
    }
}
