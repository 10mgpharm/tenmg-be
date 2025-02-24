<?php

namespace App\Http\Controllers\API\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\DeleteEcommerceProductReviewRequest;
use App\Http\Requests\Storefront\ShowEcommerceProductReviewRequest;
use App\Http\Requests\Storefront\StoreEcommerceProductReviewRequest;
use App\Http\Requests\Storefront\UpdateEcommerceProductReviewRequest;
use App\Http\Resources\Storefront\EcommerceProductReviewResource;
use App\Models\EcommerceProductReview;
use Illuminate\Http\Request;

class EcommerceProductReviewController extends Controller
{
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $reviewed = EcommerceProductReview::where('user_id', $user->id)
        ->when(
            $request->input('type'),
            fn($query, $type) => strtoupper($type) == 'REVIEWED' ? $query : null) // TODO: ...
        ->paginate($request->has('perPage') ? $request->perPage : 20)
        ->withQueryString()
        ->through(fn(EcommerceProductReview $item) => EcommerceProductReviewResource::make($item));

        return $this->returnJsonResponse(
            message: 'Reviews successfully fetched.',
            data: $reviewed,
        );
    }

    /**
     * Create a resource in storage.
     */
    public function store(StoreEcommerceProductReviewRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();
        $validated['user_id'] = $user->id;

        $review = EcommerceProductReview::create($validated);

        return $this->returnJsonResponse(
            message: 'Review created successfully.',
            data: new EcommerceProductReviewResource($review),
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(ShowEcommerceProductReviewRequest $request, EcommerceProductReview $review)
    {
        return $this->returnJsonResponse(
            message: 'Review successfully fetched.',
            data: new EcommerceProductReviewResource($review->refresh()),
        );
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEcommerceProductReviewRequest $request, EcommerceProductReview $review)
    {
        $validated = $request->validated();
        $isUpdated = $review->update($validated);

        if(!$isUpdated) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t update review at the moment. Please try again later.',
            );
        }

        return $this->returnJsonResponse(
            message: 'Review updated successfully.',
            data: new EcommerceProductReviewResource($review),
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeleteEcommerceProductReviewRequest $request, EcommerceProductReview $review)
    {
        $review->delete();

        return $this->returnJsonResponse(
            message: 'Review deleted successfully.',
        );
    }
}
