<?php

namespace App\Services\Interfaces;

use App\Models\CarouselImage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

interface ICarouselImageService
{
    /**
     * Retrieve a paginated collection of carousel images based on filters.
     *
     * @param  \Illuminate\Http\Request  $request  The request instance containing filters.
     * @return \Illuminate\Pagination\LengthAwarePaginator Paginated list of carousel images.
     */
    public function index(Request $request): LengthAwarePaginator;

    /**
     * Store a new carousel image in the database.
     *
     * @param  array  $validated  The validated data for the carousel image.
     * @param  \App\Models\User  $user  The authenticated user creating the carousel image.
     * @return \App\Models\CarouselImage|null The created carousel image or null on failure.
     */
    public function store(array $validated, User $user): ?CarouselImage;

    /**
     * Update an existing carousel image in the database.
     *
     * @param  array  $validated  The validated data for updating the carousel image.
     * @param  \App\Models\User  $user  The authenticated user updating the carousel image.
     * @param  \App\Models\CarouselImage  $carousel_image  The carousel image to be updated.
     * @return bool True if the update was successful, otherwise false.
     */
    public function update(array $validated, User $user, CarouselImage $carousel_image): bool;
}
