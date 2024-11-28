<?php

namespace App\Services\Admin;

use App\Models\CarouselImage;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\Interfaces\ICarouselImageService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CarouselImageService implements ICarouselImageService
{
    public function __construct(private AttachmentService $attachmentService) {}

    /**
     * Retrieve a paginated list of carousel images with optional filtering.
     */
    public function index(Request $request): LengthAwarePaginator
    {
        $query = CarouselImage::query();

        if ($name = $request->input('name')) {
            $query->where('name', 'LIKE', '%'.$name.'%')
                ->orWhere('description', 'LIKE', '%'.$name.'%');
        }

        return $query->latest()->paginate();
    }

    /**
     * Store a new carousel image in the database, including file upload.
     *
     * @param  array  $validated  The validated data.
     * @param  \App\Models\User  $user  The user performing the action.
     * @return \App\Models\CarouselImage|null The created carousel image.
     */
    public function store(array $validated, User $user): ?CarouselImage
    {
        try {
            return DB::transaction(function () use ($validated, $user) {

                // Create the carousel image record
                $carousel_image = CarouselImage::create([
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'created_by_id' => $user->id,
                ]);

                // Handle image file upload
                if (request()->hasFile('image')) {
                    $created = $this->attachmentService->saveNewUpload(
                        request()->file('image'),
                        $carousel_image->id,
                        CarouselImage::class,
                    );
                    $carousel_image->update(['image_file_id' => $created->id]);
                }

                return $carousel_image;
            });
        } catch (Exception $e) {
            throw new Exception('Failed to create carousel image: '.$e->getMessage());
        }
    }

    /**
     * Update an existing carousel image and handle image file upload.
     *
     * @param  array  $validated  The validated data.
     * @param  \App\Models\User  $user  The user performing the action.
     * @param  \App\Models\CarouselImage  $carousel_image  The carousel image to update.
     * @return bool Whether the update was successful.
     */
    public function update(array $validated, User $user, CarouselImage $carousel_image): bool
    {
        try {
            return DB::transaction(function () use ($validated, $user, $carousel_image) {

                // Handle image file upload if provided
                if (request()->hasFile('image')) {
                    $created = $this->attachmentService->saveNewUpload(
                        request()->file('image'),
                        $carousel_image->id,
                        CarouselImage::class,
                    );
                    $validated['image_file_id'] = $created->id;
                }

                // Update the carousel image
                return $carousel_image->update([
                    ...$validated,
                    'updated_by_id' => $user->id,
                ]);
            });
        } catch (Exception $e) {
            throw new Exception('Failed to update carousel image: '.$e->getMessage());
        }
    }

    /**
     * Search for carousel images based on filters such as name or description.
     */
    public function search(Request $request): LengthAwarePaginator
    {
        $query = CarouselImage::query();

        if ($name = $request->input('name')) {
            $query->where('name', 'LIKE', '%'.$name.'%')
                ->orWhere('description', 'LIKE', '%'.$name.'%');
        }

        return $query->latest()->paginate();
    }
}
