<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteCarouselImageRequest;
use App\Http\Requests\Admin\StoreCarouselImageRequest;
use App\Http\Resources\Admin\CarouselImageResource;
use App\Models\CarouselImage;
use App\Services\Admin\CarouselImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CarouselImageController extends Controller
{
    /**
     * CarouselImageController constructor.
     */
    public function __construct(private CarouselImageService $carouselImageService) {}

    /**
     * Retrieve all carousel images with optional filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $carousel_images = $this->carouselImageService->index($request);

        return $this->returnJsonResponse(
            message: 'Carousel images retrieved successfully.',
            data: CarouselImageResource::collection($carousel_images)->response()->getData(true),
        );
    }

    /**
     * Store a new carousel image in the database.
     */
    public function store(StoreCarouselImageRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $carousel_image = $this->carouselImageService->store($validated, $user);

        if (! $carousel_image) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t create carousel image at the moment. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: 'Carousel image uploaded successfully.',
            data: new CarouselImageResource($carousel_image),
            statusCode: Response::HTTP_CREATED,
        );
    }

    /**
     * Update an existing carousel image in the database.
     */
    public function update(StoreCarouselImageRequest $request, CarouselImage $storefront_image): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $updated = $this->carouselImageService->update($validated, $user, $storefront_image);

        if (! $updated) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t update carousel image at the moment. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: 'Carousel image updated successfully.',
            data: new CarouselImageResource($storefront_image->refresh()),
            statusCode: Response::HTTP_OK,
        );
    }

    public function destroy(DeleteCarouselImageRequest $request, CarouselImage $storefront_image)
    {
        if(CarouselImage::count() === 1){
            return $this->returnJsonResponse(
                message: 'Oops, can\'t delete all carousel images.'
            );
        }

        $storefront_image->delete();

        return $this->returnJsonResponse(
            message: 'Carousel image successfully deleted.',
        );
    }

    /**
     * Search for carousel images based on the provided criteria.
     */
    public function search(Request $request): JsonResponse
    {
        $carousel_images = $this->carouselImageService->search($request);

        return $this->returnJsonResponse(
            message: 'Carousel images retrieved successfully.',
            data: CarouselImageResource::collection($carousel_images)->response()->getData(true),
        );
    }
}
