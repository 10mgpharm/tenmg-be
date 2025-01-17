<?php

namespace App\Services\Admin;

use App\Http\Requests\Admin\ListFaqRequest;
use App\Models\Faq;
use App\Models\User;
use App\Services\Interfaces\IFaqService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FaqService implements IFaqService
{
    /**
     * {@inheritDoc}
     */
    public function index(ListFaqRequest $request): LengthAwarePaginator
    {
        $query = Faq::query();

        if ($question = $request->input('question')) {
            $query->where('name', 'LIKE', '%'.$question.'%');
        }

        if ($answer = $request->input('answer')) {
            $query->where('name', 'LIKE', '%'.$answer.'%');
        }

        return $query->latest('id')->paginate();
    }

    /**
     * {@inheritDoc}
     */
    public function store(array $validated, User $user): Faq
    {
        try {
            return DB::transaction(function () use ($validated, $user) {
                return Faq::create([
                    ...$validated,
                    'created_by_id' => $user->id,
                ]);
            });
        } catch (Exception $e) {
            throw new Exception('Failed to create FAQ: '.$e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update(array $validated, User $user, Faq $faq): bool
    {
        try {
            return DB::transaction(fn () => $faq->update([
                ...$validated,
                'updated_by_id' => $user->id,
            ]));
        } catch (Exception $e) {
            throw new Exception('Failed to update the FAQ: '.$e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function search(Request $request): LengthAwarePaginator
    {
        $query = Faq::query();

        if ($question = $request->input('question')) {
            $query->where('name', 'LIKE', '%'.$question.'%');
        }

        if ($answer = $request->input('answer')) {
            $query->where('name', 'LIKE', '%'.$answer.'%');
        }

        return $query->latest('id')->paginate();
    }
}
