<?php

namespace App\Services\Admin\Storefront;

use App\Models\EcommerceCategory;
use App\Models\EcommerceProduct;
use App\Services\Interfaces\Storefront\IEcommerceCategoryService;
use App\Http\Resources\Storefront\EcommerceProductResource;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EcommerceCategoryService implements IEcommerceCategoryService
{
    /**
     * Retrieve a paginated list of products within a specific category,
     * filtered by various parameters such as product name, amount, date range, and brand.
     *
     * This method allows filtering products by product name/slug, amount range, date range, and brand.
     * It processes both arrays and comma-separated values for multiple filter options.
     *
     * @param  Request  $request  The incoming HTTP request containing filter parameters.
     * @param  EcommerceCategory  $category  The category to filter products by.
     * @return LengthAwarePaginator A paginated list of filtered products.
     */
    public function products(Request $request, EcommerceCategory $category): LengthAwarePaginator
    {
        // Start the query from the products relationship
        $query = $category->products()->where('active', 1)->whereIn('status', ['ACTIVE', 'APPROVED'])
          // Filter by product name
        ->when(
            $request->input('search'),
            fn($query, $search) => $query->where(
                fn($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'LIKE', "%{$search}%"))
        )
        // Filter by inventory status (OUT OF STOCK, LOW STOCK, IN STOCK)
        ->when($request->input('inventories'), function ($query, $inventory) {
            $inventories = is_array($inventory) ? $inventory : explode(',', $inventory);
            $inventories = array_unique(array_map('trim', $inventories));

            $query->whereHas('productDetails', fn ($q) => 
                $q->where(function ($query) use($inventories) {
                    foreach ($inventories as $status) {
                        $query->when(
                            $status === 'OUT OF STOCK',
                            fn($q) => $q->orWhereNull('current_stock')->orWhere('current_stock', 0)
                        )->when(
                            $status === 'LOW STOCK',
                            fn($q) => $q->orWhereNotNull('starting_stock')->orWhereColumn('current_stock', '<=', DB::raw('starting_stock / 2'))
                        )->when(
                            $status === 'IN STOCK',
                            fn($q) => $q->orWhereNotNull('current_stock')->orWhere('current_stock', '>', DB::raw('starting_stock / 2'))
                        );
                    }
                })
            );
        })

        // Filter by brand names (case-insensitive partial match)
        ->when($request->input('brands'), function ($query, $brands) {
            $brands = is_array($brands) ? $brands : explode(',', $brands);
            $brands = array_unique(array_map('trim', $brands));

            $query->whereHas(
                'brand',
                fn($q) =>
                $q->where(function ($query) use ($brands) {
                    foreach ($brands as $brand) {
                        $query->orWhere('name', 'like', '%' . $brand . '%')
                            ->orWhere('slug', 'like', '%' . $brand . '%');
                    }
                })
            );
        });

    // Filter by "minAmount" if provided
    if ($min_amount = $request->input('minAmount')) {
        $query->where('actual_price', '>=', $min_amount);
    }

    // Filter by "maxAmount" if provided
    if ($max_amount = $request->input('maxAmount')) {
        $query->where('actual_price', '<=', $max_amount);
    }

    // Filter by "from_date" if provided
    if ($from = $request->input('fromDate')) {
        $query->whereDate('created_at', '>=', $from);
    }

    // Filter by "to_date" if provided
    if ($to = $request->input('toDate')) {
        $query->whereDate('created_at', '<=', $to);
    }


    // Sort by specified column and order (default: created_at desc)
    if ($request->has('sort') && $request->has('order')) {
        $sortColumn = $request->input('sort');
        $sortOrder = $request->input('order');

        $validColumns = ['name']; // Define valid sortable columns
        if (in_array($sortColumn, $validColumns) && in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $query->orderBy($sortColumn, $sortOrder);
        }
    } else {
        $query->orderBy('created_at', 'desc');
    }

    // Retrieve paginated results
    return $query
        ->paginate($request->has('perPage') ? $request->perPage : 10)
        ->withQueryString()
        ->through(fn(EcommerceProduct $item) => EcommerceProductResource::make($item));
    }

    /**
     * Retrieve a paginated list of products based on the provided filters, such as inventory status, category,
     * branch, medication type, variation, and package.
     *
     * The filters support both arrays and comma-separated values for multiple options.
     * Duplicates in array inputs are removed before processing.
     *
     * @param  Request  $request  The incoming HTTP request containing filter parameters.
     * @return LengthAwarePaginator A paginated list of filtered products.
     */
    public function search(Request $request): LengthAwarePaginator
    {
        // Start the query directly from the EcommerceProduct model
        $query = EcommerceProduct::where('active', 1)->whereIn('status', ['ACTIVE', 'APPROVED'])
            // Filter by product name
            ->when(
                $request->input('search'),
                fn($query, $search) => $query->where(
                    fn($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'LIKE', "%{$search}%"))
            )
            // Filter by inventory status (OUT OF STOCK, LOW STOCK, IN STOCK)
            ->when($request->input('inventories'), function ($query, $inventory) {
                $inventories = is_array($inventory) ? $inventory : explode(',', $inventory);
                $inventories = array_unique(array_map('trim', $inventories));

                $query->whereHas('productDetails', fn ($q) => 
                    $q->where(function ($query) use($inventories) {
                        foreach ($inventories as $status) {
                            $query->when(
                                $status === 'OUT OF STOCK',
                                fn($q) => $q->orWhereNull('current_stock')->orWhere('current_stock', 0)
                            )->when(
                                $status === 'LOW STOCK',
                                fn($q) => $q->orWhereNotNull('starting_stock')->orWhereColumn('current_stock', '<=', DB::raw('starting_stock / 2'))
                            )->when(
                                $status === 'IN STOCK',
                                fn($q) => $q->orWhereNotNull('current_stock')->orWhere('current_stock', '>', DB::raw('starting_stock / 2'))
                            );
                        }
                    })
                );
            })
            // Filter by category names (case-insensitive partial match)
            ->when($request->input('categories'), function ($query, $categories) {
                $categories = is_array($categories) ? $categories : explode(',', $categories);
                $categories = array_unique(array_map('trim', $categories));

                $query->whereHas(
                    'category',
                    fn($q) =>
                    $q->where(function ($query) use ($categories) {
                        foreach ($categories as $category) {
                            $query->orWhere('name', 'like', '%' . $category . '%')
                                ->orWhere('slug', 'like', '%' . $category . '%');
                        }
                    })
                );
            })
            // Filter by brand names (case-insensitive partial match)
            ->when($request->input('brands'), function ($query, $brands) {
                $brands = is_array($brands) ? $brands : explode(',', $brands);
                $brands = array_unique(array_map('trim', $brands));

                $query->whereHas(
                    'brand',
                    fn($q) =>
                    $q->where(function ($query) use ($brands) {
                        foreach ($brands as $brand) {
                            $query->orWhere('name', 'like', '%' . $brand . '%')
                                ->orWhere('slug', 'like', '%' . $brand . '%');
                        }
                    })
                );
            });

        // dd($query->toRawSql());

        // Filter by "minAmount" if provided
        if ($min_amount = $request->input('minAmount')) {
            $query->where('actual_price', '>=', $min_amount);
        }

        // Filter by "maxAmount" if provided
        if ($max_amount = $request->input('maxAmount')) {
            $query->where('actual_price', '<=', $max_amount);
        }

        // Filter by "from_date" if provided
        if ($from = $request->input('fromDate')) {
            $query->whereDate('created_at', '>=', $from);
        }

        // Filter by "to_date" if provided
        if ($to = $request->input('toDate')) {
            $query->whereDate('created_at', '<=', $to);
        }


        // Sort by specified column and order (default: created_at desc)
        if ($request->has('sort') && $request->has('order')) {
            $sortColumn = $request->input('sort');
            $sortOrder = $request->input('order');

            $validColumns = ['name']; // Define valid sortable columns
            if (in_array($sortColumn, $validColumns) && in_array(strtolower($sortOrder), ['asc', 'desc'])) {
                $query->orderBy($sortColumn, $sortOrder);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Retrieve paginated results
        return $query
            ->paginate($request->has('perPage') ? $request->perPage : 10)
            ->withQueryString()
            ->through(fn(EcommerceProduct $item) => EcommerceProductResource::make($item));
    }
}
