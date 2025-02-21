<?php

namespace App\Http\Controllers\API\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShowEcommerceProductRatingRequest;
use App\Http\Requests\StoreEcommerceProductRatingRequest;
use App\Http\Requests\UpdateEcommerceProductRatingRequest;
use App\Http\Resources\Storefront\EcommerceProductRatingResource;
use App\Models\EcommerceProductRating;
use Exception;
use Illuminate\Http\Request;

class EcommerceProductRatingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $ratinged = EcommerceProductRating::where('user_id', $user->id)
        ->paginate($request->has('perPage') ? $request->perPage : 20)
        ->withQueryString()
        ->through(fn(EcommerceProductRating $item) => EcommerceProductRatingResource::make($item));

        return $this->returnJsonResponse(
            message: 'Rating successfully fetched.',
            data: $ratinged,
        );
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEcommerceProductRatingRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();
        $validated['user_id'] = $user->id;

        $rating = EcommerceProductRating::create($validated);

        return $this->returnJsonResponse(
            message: 'Rating created successfully.',
            data: new EcommerceProductRatingResource($rating),
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(ShowEcommerceProductRatingRequest $request, EcommerceProductRating $rating)
    {
        return $this->returnJsonResponse(
            message: 'Rating successfully fetched.',
            data: new EcommerceProductRatingResource($rating->refresh()),
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEcommerceProductRatingRequest $request, EcommerceProductRating $rating)
    {
        try {
            $validated = array_filter($request->validated(), fn($each) => $each !== null);
            $isUpdated = $rating->update($validated);

            if(!$isUpdated) {
                return $this->returnJsonResponse(
                    message: 'Ops, nothing to update, try passing a different value.',
                );
            }

        return $this->returnJsonResponse(
            message: 'Rating updated successfully.',
            data: new EcommerceProductRatingResource($rating->refresh()),
        );

        } catch (Exception $e) {
            throw new Exception('Oops, failed to update rating at the moment. ' . $e->getMessage());
        }
    }
}
