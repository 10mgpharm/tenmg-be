<?php

namespace App\Http\Controllers\API\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCarouselImageRequest;
use App\Http\Resources\Admin\CarouselImageResource;
use App\Models\CarouselImage;
use App\Services\Admin\CarouselImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CarouselImageController extends Controller
{
    /**
     * CarouselImageController constructor.
     *
     * @param \App\Services\Admin\CarouselImageService $carouselImageService
     */
    public function __construct(private CarouselImageService $carouselImageService) {}

    /**
     * Retrieve all carousel images with optional filtering.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
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
     *
     * @param \App\Http\Requests\Admin\StoreCarouselImageRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreCarouselImageRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $carousel_image = $this->carouselImageService->store($validated, $user);

        if (!$carousel_image) {
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
     *
     * @param \App\Http\Requests\Admin\StoreCarouselImageRequest $request
     * @param \App\Models\CarouselImage $carousel_image
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(StoreCarouselImageRequest $request, CarouselImage $carousel_image): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $updated = $this->carouselImageService->update($validated, $user, $carousel_image);

        if (!$updated) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t update carousel image at the moment. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: 'Carousel image updated successfully.',
            data: new CarouselImageResource($carousel_image->refresh()),
            statusCode: Response::HTTP_OK,
        );
    }

    /**
     * Search for carousel images based on the provided criteria.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
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
