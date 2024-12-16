<?php

namespace App\Services\Admin;

use App\Enums\StatusEnum;
use App\Models\EcommerceBrand;
use App\Models\EcommerceCategory;
use App\Models\EcommerceMeasurement;
use App\Models\EcommerceMedicationType;
use App\Models\EcommerceMedicationVariation;
use App\Models\EcommercePackage;
use App\Models\EcommercePresentation;
use App\Models\EcommerceProduct;
use App\Models\EcommerceProductDetail;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\Interfaces\IEcommerceProductService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EcommerceProductService implements IEcommerceProductService
{
    public function __construct(private AttachmentService $attachmentService) {}

    /**
     * Store a new product.
     *
     * This method creates a new product after ensuring the category, brand, and
     * medication type exist, creating them if necessary. It uses a transaction
     * to ensure all steps either complete or fail together.
     *
     * @param  array  $validated  The validated data for creating the product.
     * @param  User  $user  The user creating the product.
     * @return EcommerceProduct|null The created product or null on failure.
     *
     * @throws Exception If product creation fails.
     */
    public function store(array $validated, User $user): ?EcommerceProduct
    {
        try {
            return DB::transaction(function () use ($validated, $user) {

                $business = $user->ownerBusinessType ?? $user->businesses()
                    ->firstWhere('user_id', $user->id);

                // Ensure category exists or create it
                $category = EcommerceCategory::firstOrCreate(
                    ['name' => $validated['category_name']],
                    [
                        'slug' => Str::slug($validated['category_name']),
                        'business_id' => $business?->id,
                        'created_by_id' => $user->id,
                        'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::APPROVED->value,
                        'active' => true,
                    ]
                );

                // Ensure brand exists or create it
                $brand = EcommerceBrand::firstOrCreate(
                    ['name' => $validated['brand_name']],
                    [
                        'slug' => Str::slug($validated['brand_name']),
                        'business_id' => $business?->id,
                        'created_by_id' => $user->id,
                        'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::APPROVED->value,
                        'active' => true,
                    ]
                );

                // Ensure medication type exists or create it
                $medicationType = EcommerceMedicationType::firstOrCreate(
                    ['name' => $validated['medication_type_name']],
                    [
                        'slug' => Str::slug($validated['medication_type_name']),
                        'business_id' => $business?->id,
                        'created_by_id' => $user->id,
                        'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::APPROVED->value,
                        'active' => true,
                    ]
                );

                // Ensure measurement exists or create it
                $measurement = EcommerceMeasurement::firstOrCreate(
                    ['name' => $validated['measurement_name']],
                    [
                        'business_id' => $business?->id,
                        'created_by_id' => $user->id,
                        'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::APPROVED->value,
                        'active' => true,
                    ]
                );

                // Ensure presentation exists or create it
                $presentation = EcommercePresentation::firstOrCreate(
                    ['name' => $validated['presentation_name']],
                    [
                        'business_id' => $business?->id,
                        'created_by_id' => $user->id,
                        'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::APPROVED->value,
                        'active' => true,
                    ]
                );

                // Ensure package exists or create it
                $package = EcommercePackage::firstOrCreate(
                    ['name' => $validated['package_name']],
                    [
                        'business_id' => $business?->id,
                        'created_by_id' => $user->id,
                        'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::APPROVED->value,
                        'active' => true,
                    ]
                );

                // Ensure variation exists or create it
                $variation = EcommerceMedicationVariation::firstOrCreate(
                    [
                        'weight' => $validated['weight'],
                        'strength_value' => (int) preg_replace('/\D/', '', $validated['strength_value']) ?: 0,
                        'strength' => $validated['strength_value'],
                        'ecommerce_presentation_id' => $presentation->id,
                        'ecommerce_package_id' => $package->id,
                        'ecommerce_medication_type_id' => $medicationType->id,
                    ],
                    [
                        'business_id' => $business?->id,
                        'created_by_id' => $user->id,
                        'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::APPROVED->value,
                        'active' => true,
                    ]
                );

                $product = $user->products()->create([
                    'business_id' => $business?->id,

                    'ecommerce_category_id' => $category->id,
                    'ecommerce_brand_id' => $brand->id,
                    'ecommerce_medication_type_id' => $medicationType->id,
                    'ecommerce_package_id' => $package->id,
                    'ecommerce_variation_id' => $variation->id,
                    'ecommerce_presentation_id' => $presentation->id,
                    'ecommerce_measurement_id' => $measurement->id,

                    'created_by_id' => $user->id,

                    'quantity' => $validated['quantity'],
                    'actual_price' => $validated['actual_price'],
                    'discount_price' => $validated['discount_price'],

                    'name' => $validated['product_name'],
                    'description' => $validated['product_description'],
                    'slug' => Str::slug($validated['product_name']),

                    'low_stock_level' => $validated['low_stock_level'] ?? null,
                    'out_stock_level' => $validated['out_stock_level'] ?? null,

                    'expired_at' => $validated['expired_at'],

                    'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::APPROVED->value,
                    'active' => true,

                ]);

                $product->productDetails()->create($validated);

                // Save uploaded file
                if (request()->hasFile('thumbnailFile')) {
                    $created = $this->attachmentService->saveNewUpload(
                        request()->file('thumbnailFile'),
                        $product->id,
                        EcommerceProduct::class,
                    );

                    $product->update(['thumbnail_file_id' => $created->id]);
                }

                return $product;
            });
        } catch (Exception $e) {
            throw new Exception('Failed to create product: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing product.
     *
     * This method updates an existing product. It ensures that if the category,
     * brand, or medication type has been changed, they exist or are created.
     *
     * @param  array  $validated  The validated data for updating the product.
     * @param  User  $user  The user updating the product.
     * @param  EcommerceProduct  $product  The product to be updated.
     * @return bool|null True on success, null on failure.
     *
     * @throws Exception If the product update fails.
     */
    public function update(array $validated, User $user, EcommerceProduct $product): ?bool
    {
        try {
            return DB::transaction(function () use ($validated, $user, $product) {

                // Filter out empty/null values.
                $validated = array_filter($validated);

                // Extract business information
                $business = $user->ownerBusinessType
                    ?? $user->businesses()->firstWhere('user_id', $user->id);

                // Ensure category exists or create it
                if (!empty($validated['category_name'])) {
                    $category = EcommerceCategory::firstOrCreate(
                        ['name' => $validated['category_name']],
                        [
                            'slug' => Str::slug($validated['category_name']),
                            'business_id' => $business?->id,
                            'created_by_id' => $user->id,
                            'status' =>  $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::APPROVED->value,
                            'active' => true,
                        ]
                    );

                    $validated['ecommerce_category_id'] = $category->id;
                }

                // Ensure brand exists or create it
                if (!empty($validated['brand_name'])) {
                    $brand = EcommerceBrand::firstOrCreate(
                        ['name' => $validated['brand_name']],
                        [
                            'slug' => Str::slug($validated['brand_name']),
                            'business_id' => $business?->id,
                            'created_by_id' => $user->id,
                            'status' =>  $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::APPROVED->value,
                            'active' => true,
                        ]
                    );

                    $validated['ecommerce_brand_id'] = $brand->id;
                }

                // Ensure medication type exists or create it
                if (!empty($validated['medication_type_name'])) {
                    $medicationType = EcommerceMedicationType::firstOrCreate(
                        ['name' => $validated['medication_type_name']],
                        [
                            'slug' => Str::slug($validated['medication_type_name']),
                            'business_id' => $business?->id,
                            'created_by_id' => $user->id,
                            'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::APPROVED->value,
                            'active' => true,
                        ]
                    );

                    $validated['ecommerce_medication_type_id'] = $medicationType->id;
                }

                // Ensure measurement exists or create it
                if (!empty($validated['measurement_name'])) {
                    $measurement = EcommerceMeasurement::firstOrCreate(
                        ['name' => $validated['measurement_name']],
                        [
                            'business_id' => $business?->id,
                            'created_by_id' => $user->id,
                            'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::APPROVED->value,
                            'active' => true,
                        ]
                    );

                    $validated['ecommerce_measurement_id'] = $measurement->id;
                }

                // Ensure presentation exists or create it
                if (!empty($validated['presentation_name'])) {
                    $presentation = EcommercePresentation::firstOrCreate(
                        ['name' => $validated['presentation_name']],
                        [
                            'business_id' => $business?->id,
                            'created_by_id' => $user->id,
                            'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::APPROVED->value,
                            'active' => true,
                        ]
                    );

                    $validated['ecommerce_presentation_id'] = $presentation->id;
                }


                // Ensure package exists or create it
                if (!empty($validated['package_name'])) {
                    $package = EcommercePackage::firstOrCreate(
                        ['name' => $validated['package_name']],
                        [
                            'business_id' => $business?->id,
                            'created_by_id' => $user->id,
                            'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::APPROVED->value,
                            'active' => true,
                        ]
                    );

                    $validated['ecommerce_package_id'] = $package->id;
                }


                // Ensure variation exists or create it
                if (!empty($validated['weight']) && !empty($validated['strength_value'])) {

                    $variation = EcommerceMedicationVariation::firstOrCreate(
                        array_filter([ // Include only non-empty keys
                            'weight' => $validated['weight'],
                            'strength_value' => (int) preg_replace('/\D/', '', $validated['strength_value']) ?: 0,
                            'strength' => $validated['strength_value'],
                            'ecommerce_presentation_id' => $presentation->id ?? null,
                            'ecommerce_package_id' => $package->id ?? null,
                            'ecommerce_medication_type_id' => $medicationType->id ?? null,
                        ], fn($each) => $each !== null && $each !== false),
                        [
                            'business_id' => $business?->id,
                            'updated_by_id' => $user->id,
                            'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::APPROVED->value,
                            'active' => true,
                        ]
                    );

                    $validated['ecommerce_variation_id'] = $variation->id;
                }



                // Save uploaded file
                if (request()->hasFile('thumbnailFile')) {
                    $created = $this->attachmentService->saveNewUpload(
                        request()->file('thumbnailFile'),
                        $product->id,
                        EcommerceProduct::class,
                    );

                    $validated['thumbnail_file_id'] = $created->id;
                }

                $updateProduct = $product->update([
                    ...array_filter($validated),
                    'updated_by_id' => $user->id,
                    'slug' => Str::slug($validated['product_name'] ?? $product->name),
                ]);

                $updateProductDetails = $product->productDetails?->update($validated);

                return $updateProduct || $updateProductDetails;
            });
        } catch (Exception $e) {
            throw new Exception('Failed to update product: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a paginated list of products based on the provided filters, such as inventory status, category,
     * branch, medication type, variation, and package.
     *
     * The filters support both arrays and comma-separated values for multiple options.
     * Duplicates in array inputs are removed before processing.
     *
     * @return LengthAwarePaginator Paginated list of filtered products.
     */
    public function search(Request $request): LengthAwarePaginator
    {
        $query = EcommerceProduct::query();

        // Filter by Inventory Status
        if ($inventoryStatus = $request->input('inventory')) {
            // Handle multiple inventory statuses if provided as an array or comma-separated values
            $inventoryStatuses = is_array($inventoryStatus) ? $inventoryStatus : explode(',', $inventoryStatus);
            $inventoryStatuses = array_unique(array_map('trim', $inventoryStatuses));  // Remove duplicates and trim values

            $query->where(function ($query) use ($inventoryStatuses) {
                foreach ($inventoryStatuses as $status) {
                    $query->when($status === 'OUT OF STOCK', function ($q) {
                        $q->whereNull('current_stock')
                            ->orWhere('current_stock', 0);
                    })->when($status === 'LOW STOCK', function ($q) {
                        $q->whereNotNull('starting_stock')
                            ->whereColumn('current_stock', '<=', DB::raw('starting_stock / 2'));
                    })->when($status === 'IN STOCK', function ($q) {
                        $q->whereNotNull('current_stock')
                            ->where('current_stock', '>', DB::raw('starting_stock / 2'));
                    });
                }
            });
        }

        // Filter by Related Model Names (handling arrays or comma-separated values)
        if ($categoryName = $request->input('category')) {
            $categories = is_array($categoryName) ? $categoryName : explode(',', $categoryName);
            $categories = array_unique(array_map('trim', $categories));  // Remove duplicates and trim values
            $query->whereHas('category', function ($q) use ($categories) {
                foreach ($categories as $category) {
                    $q->orWhere('name', 'like', '%' . $category . '%');
                }
            });
        }

        if ($brandName = $request->input('brand')) {
            $brands = is_array($brandName) ? $brandName : explode(',', $brandName);
            $brands = array_unique(array_map('trim', $brands));  // Remove duplicates and trim values
            $query->whereHas('brand', function ($q) use ($brands) {
                foreach ($brands as $brand) {
                    $q->orWhere('name', 'like', '%' . $brand . '%');
                }
            });
        }

        if ($medicationTypeName = $request->input('medicationType')) {
            $medicationTypes = is_array($medicationTypeName) ? $medicationTypeName : explode(',', $medicationTypeName);
            $medicationTypes = array_unique(array_map('trim', $medicationTypes));  // Remove duplicates and trim values
            $query->whereHas('medicationType', function ($q) use ($medicationTypes) {
                foreach ($medicationTypes as $medicationType) {
                    $q->orWhere('name', 'like', '%' . $medicationType . '%');
                }
            });
        }

        if ($variationName = $request->input('variation')) {
            $variations = is_array($variationName) ? $variationName : explode(',', $variationName);
            $variations = array_unique(array_map('trim', $variations));  // Remove duplicates and trim values
            $query->whereHas('variation', function ($q) use ($variations) {
                foreach ($variations as $variation) {
                    $q->orWhere('name', 'like', '%' . $variation . '%');
                }
            });
        }

        if ($packageName = $request->input('package')) {
            $packages = is_array($packageName) ? $packageName : explode(',', $packageName);
            $packages = array_unique(array_map('trim', $packages));  // Remove duplicates and trim values
            $query->whereHas('package', function ($q) use ($packages) {
                foreach ($packages as $package) {
                    $q->orWhere('name', 'like', '%' . $package . '%');
                }
            });
        }

        if ($from = $request->input('fromDate')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('toDate')) {
            $query->whereDate('created_at', '<=', $to);
        }

        // Retrieve paginated results
        return $query->latest()->paginate();
    }
}
