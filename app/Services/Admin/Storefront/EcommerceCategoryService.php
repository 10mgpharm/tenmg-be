<?php

namespace App\Services\Admin\Storefront;

use App\Models\EcommerceCategory;
use App\Models\EcommerceProduct;
use App\Services\Interfaces\Storefront\IEcommerceCategoryService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

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
        $query = $category->products()->where('active', 1)->whereIn('status', ['ACTIVE', 'APPROVED']);

        // Filter by product names or slugs if provided
        if ($product_name = $request->input('productName')) {
            $product_names = is_array($product_name) ? $product_name : explode(',', $product_name);
            $product_names = array_unique(array_map('trim', $product_names)); // Remove duplicates and trim values

            $query->where(function ($q) use ($product_names) {
                foreach ($product_names as $name) {
                    $q->orWhere('name', 'LIKE', "%{$name}%")
                        ->orWhere('slug', 'LIKE', "%{$name}%");
                }
            });
        }

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

        // Filter by "brand" if provided
        if ($brandName = $request->input('brand')) {
            $brands = is_array($brandName) ? $brandName : explode(',', $brandName);
            $brands = array_unique(array_map('trim', $brands));  // Remove duplicates and trim values

            $query->whereHas('brand', function ($q) use ($brands) {
                foreach ($brands as $brand) {
                    $q->orWhere('name', 'like', '%'.$brand.'%');
                }
            });
        }

        // Retrieve paginated results, ordered by latest creation date
        return $query->latest()->paginate();
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
        $query = EcommerceProduct::where('active', 1)->whereIn('status', ['ACTIVE', 'APPROVED']);

        // Filter by multiple "categories" if provided
        if ($categories = $request->input('categories')) {
            $categories = is_array($categories) ? $categories : explode(',', $categories);
            $categories = array_unique(array_map('trim', $categories)); // Remove duplicates and trim values

            $query->whereHas('category', function ($q) use ($categories) {
                foreach ($categories as $category) {
                    $q->orWhere('name', 'LIKE', "%{$category}%")
                        ->orWhere('slug', 'LIKE', "%{$category}%");
                }
            });
        }

        // Filter by product names or slugs if provided
        if ($product_name = $request->input('productName')) {
            $product_names = is_array($product_name) ? $product_name : explode(',', $product_name);
            $product_names = array_unique(array_map('trim', $product_names)); // Remove duplicates and trim values

            $query->where(function ($q) use ($product_names) {
                foreach ($product_names as $name) {
                    $q->orWhere('name', 'LIKE', "%{$name}%")
                        ->orWhere('slug', 'LIKE', "%{$name}%");
                }
            });
        }

        // Filter by "minAmount" if provided
        if ($min_amount = $request->input('minAmount')) {
            $query->where('amount', '>=', $min_amount);
        }

        // Filter by "maxAmount" if provided
        if ($max_amount = $request->input('maxAmount')) {
            $query->where('amount', '<=', $max_amount);
        }

        // Filter by "from_date" if provided
        if ($from = $request->input('fromDate')) {
            $query->whereDate('created_at', '>=', $from);
        }

        // Filter by "to_date" if provided
        if ($to = $request->input('toDate')) {
            $query->whereDate('created_at', '<=', $to);
        }

        // Filter by "brand" if provided
        if ($brandName = $request->input('brand')) {
            $brands = is_array($brandName) ? $brandName : explode(',', $brandName);
            $brands = array_unique(array_map('trim', $brands));  // Remove duplicates and trim values

            $query->whereHas('brand', function ($q) use ($brands) {
                foreach ($brands as $brand) {
                    $q->orWhere('name', 'LIKE', "%{$brand}%");
                }
            });
        }

        // Retrieve paginated results, ordered by latest creation date
        return $query->latest()->paginate();
    }
}
