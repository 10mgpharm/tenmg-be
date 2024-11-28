<?php

namespace App\Services\Interfaces;

use App\Http\Requests\Admin\ListFaqRequest;
use App\Models\Faq;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface IInviteService
 *
 * Defines the contract for handling invite operations.
 */
interface IFaqService
{
    /**
     * Fetch a paginated list of FAQs based on the provided filters.
     *
     * @param  ListFaqRequest  $request  The request containing filter parameters.
     * @return LengthAwarePaginator Paginated FAQs list.
     */
    public function index(ListFaqRequest $request): LengthAwarePaginator;

    /**
     * Store a new FAQ with the given validated data.
     *
     * @param  array  $validated  Validated FAQ data.
     * @param  User  $user  The user creating the FAQ.
     * @return Faq The newly created FAQ instance.
     *
     * @throws \Exception If the FAQ creation fails.
     */
    public function store(array $validated, User $user): Faq;

    /**
     * Update an existing FAQ with the given validated data.
     *
     * @param  array  $validated  Validated FAQ data.
     * @param  User  $user  The user updating the FAQ.
     * @param  Faq  $faq  The FAQ instance to update.
     * @return bool True if the update was successful.
     *
     * @throws \Exception If the FAQ update fails.
     */
    public function update(array $validated, User $user, Faq $faq): bool;

    /**
     * Search FAQs based on specific parameters.
     *
     * @param  Request  $request  The request containing search parameters.
     * @return LengthAwarePaginator Paginated search results.
     */
    public function search(Request $request): LengthAwarePaginator;
}
