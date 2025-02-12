<?php

namespace App\Http\Controllers\API\Storefront;

use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\EcommercePresentationResource;
use App\Models\EcommercePresentation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresentationController extends Controller
{
    /**
     * Search and retrieve presentations based on filters and sorting options.
     *
     * This method filters presentations by status, name, slug, date range, and sorting options.
     * The results are returned in a paginated format.
     *
     * @param  Request  $request  The request instance containing filter and sorting parameters.
     * @return JsonResponse  A JSON response with paginated presentation data.
     */
    public function search(Request $request): JsonResponse
    {
        $query = EcommercePresentation::where('active', 1)
            ->whereIn('status', StatusEnum::actives())
            ->when(
                $request->input('search'),
                fn($query, $search) => $query->where(fn($q) =>
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('slug', 'LIKE', "%{$search}%")
                )
            )
            ->when($request->input('fromDate'), fn($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($request->input('toDate'), fn($query, $to) => $query->whereDate('created_at', '<=', $to));

        if (in_array($request->input('sort'), ['name']) && in_array(strtolower($request->input('order')), ['asc', 'desc'])) {
            $query->orderBy($request->input('sort'), $request->input('order'));
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $presentations = $query->paginate($request->input('perPage', 10))
            ->withQueryString()
            ->through(fn(EcommercePresentation $item) => EcommercePresentationResource::make($item));

        return $this->returnJsonResponse(
            message: 'Presentations successfully fetched.',
            data: $presentations
        );
    }
}
