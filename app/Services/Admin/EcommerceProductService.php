<?php

namespace App\Services\Admin;

use App\Enums\StatusEnum;
use App\Helpers\UtilityHelper;
use App\Http\Resources\EcommerceProductResource;
use App\Models\EcommerceBrand;
use App\Models\EcommerceCategory;
use App\Models\EcommerceMeasurement;
use App\Models\EcommerceMedicationType;
use App\Models\EcommerceMedicationVariation;
use App\Models\EcommercePresentation;
use App\Models\EcommerceProduct;
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

                // Doc: when business is null that means the product and its dependencies are for global used and created by admin
                $productBusinessId = $user->ownerBusinessType?->id ?: $user->businesses()
                        ->firstWhere('user_id', $user->id)?->id;

                if ($user && $user->hasRole('admin')) {
                    $validated['business_id'] = null;
                } else {
                    $validated['business_id'] = $user->ownerBusinessType?->id ?: $user->businesses()
                        ->firstWhere('user_id', $user->id)?->id;
                }

                // Ensure category exists or create it
                $category = EcommerceCategory::firstOrCreate(
                    [ 'slug' => Str::slug($validated['category_name'])],
                    [
                        'name' => $validated['category_name'],
                        'business_id' => $validated['business_id'],
                        'created_by_id' => $user->id,
                        'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::PENDING->value,
                        'active' => $user->hasRole('admin'),
                    ]
                );

                // Ensure brand exists or create it
                $brand = EcommerceBrand::firstOrCreate(
                    ['name' => $validated['brand_name']],
                    [
                        'slug' => UtilityHelper::generateSlug('BRD'),
                        'business_id' => $validated['business_id'],
                        'created_by_id' => $user->id,
                        'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::PENDING->value,
                        'active' => $user->hasRole('admin'),
                    ]
                );

                // Ensure medication type exists or create it
                $medicationType = EcommerceMedicationType::firstOrCreate(
                    ['name' => $validated['medication_type_name']],
                    [
                        'slug' => UtilityHelper::generateSlug('MED'),
                        'business_id' => $validated['business_id'],
                        'created_by_id' => $user->id,
                        'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::PENDING->value,
                        'active' => $user->hasRole('admin'),
                    ]
                );

                // Ensure measurement exists or create it
                $measurement = EcommerceMeasurement::firstOrCreate(
                    ['name' => $validated['measurement_name']],
                    [
                        'business_id' => $validated['business_id'],
                        'created_by_id' => $user->id,
                        'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::PENDING->value,
                        'active' => $user->hasRole('admin'),
                    ]
                );

                // Ensure presentation exists or create it
                $presentation = EcommercePresentation::firstOrCreate(
                    ['name' => $validated['presentation_name']],
                    [
                        'business_id' => $validated['business_id'],
                        'created_by_id' => $user->id,
                        'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::PENDING->value,
                        'active' => $user->hasRole('admin'),
                    ]
                );

                $validated['status'] = isset($validated['active']) && $validated['active'] === true 
                ? (isset($validated['status']) && in_array($validated['status'], StatusEnum::actives()) 
                    ? $validated['status'] 
                    : $validated['status'] ?? StatusEnum::ACTIVE->value) 
                : ($validated['status'] ?? StatusEnum::INACTIVE->value);

                $validated['active'] = (in_array($validated['status'], StatusEnum::actives()))
                ? ($validated['active'] ?? true )
                : (false);


                $product = $user->products()->create([
                    ...array_filter($validated),

                    'business_id' => $productBusinessId,

                    'ecommerce_category_id' => $category->id,
                    'ecommerce_brand_id' => $brand->id,
                    'ecommerce_medication_type_id' => $medicationType->id,
                    // 'ecommerce_variation_id' => $variation?->id,
                    'ecommerce_presentation_id' => $presentation->id,
                    'ecommerce_measurement_id' => $measurement->id,

                    'created_by_id' => $user->id,


                    'name' => $validated['product_name'],
                    'description' => $validated['product_description'],
                    'slug' => UtilityHelper::generateSlug('PRD'),


                ]);

                 // make a copy of the variation
                $variation = EcommerceMedicationVariation::create(
                    [
                        'weight' => $validated['weight'] ?? null,
                        'strength_value' => $validated['strength_value'],
                        'ecommerce_presentation_id' => $presentation->id,
                        'ecommerce_medication_type_id' => $medicationType->id,
                        'ecommerce_measurement_id' => $measurement->id,
                        'package_per_roll' => $validated['package_per_roll'] ?? null,
                        'ecommerce_product_id' => $product->id,
                        'business_id' => $validated['business_id'],

                        'created_by_id' => $user->id,
                        'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::APPROVED->value,
                        'active' => $user->hasRole('admin'),
                    ]
                );

                $product->update(['ecommerce_variation_id' => $variation->id]);
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

                // Doc: when business is null that means the product and its dependencies are for global used and created by admin
                if ($user && $user->hasRole('admin')) {
                    $validated['business_id'] = null;
                } else {
                    $validated['business_id'] = $user->ownerBusinessType?->id ?: $user->businesses()
                        ->firstWhere('user_id', $user->id)?->id;
                }

                // Ensure category exists or create it
                if (!empty($validated['category_name'])) {
                    $category = EcommerceCategory::firstOrCreate(
                        ['name' => $validated['category_name']],
                        [
                            'slug' => Str::slug($validated['category_name']),
                            'business_id' => $validated['business_id'],
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
                            'business_id' => $validated['business_id'],
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
                            'business_id' => $validated['business_id'],
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
                            'business_id' => $validated['business_id'],
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
                            'business_id' => $validated['business_id'],
                            'created_by_id' => $user->id,
                            'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::APPROVED->value,
                            'active' => true,
                        ]
                    );

                    $validated['ecommerce_presentation_id'] = $presentation->id;
                }


                // Ensure variation first or update it
                if (!empty($validated['weight']) && !empty($validated['strength_value'])) {

                    $variation = EcommerceMedicationVariation::find($product->ecommerce_variation_id);

                    if($variation){
                        $updated = array_filter([ // Include only non-empty keys
                            'weight' => $validated['weight'],
                            'strength_value' => $validated['strength_value'],
                            'ecommerce_presentation_id' => $presentation->id ?? null,
                            'ecommerce_medication_type_id' => $medicationType->id ?? null,
                            'ecommerce_measurement_id' => $measurement->id ?? null,
                            'package_per_roll' => $validated['package_per_roll'] ?? null,
                            'ecommerce_product_id' => $product->id,
                            'business_id' => $validated['business_id'],
                        ], fn($each) => $each !== null && $each !== false);
                    
                        // Update the variation attributes
                        $variation->update([
                            ...$updated,
                            'updated_by_id' => $user->id,
                            'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::APPROVED->value,
                            'active' => true,
                        ]);
                    }
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

                $validated['status'] = isset($validated['active']) && $validated['active'] === true 
                ? (isset($validated['status']) && in_array($validated['status'], StatusEnum::actives()) 
                    ? $validated['status'] 
                    : $validated['status'] ?? StatusEnum::ACTIVE->value) 
                : ($validated['status'] ?? ($category->status ?? StatusEnum::INACTIVE->value));

                $validated['active'] = (in_array($validated['status'], StatusEnum::actives()))
                ? ($validated['active'] ?? true )
                : (false);

                $updateProduct = $product->update([
                    ...array_filter($validated, fn($each) => $each !== null),
                    'name' => $validated['product_name'] ?? $product->name,
                    'description' => $validated['product_description'] ?? $product->description,
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
        $query = EcommerceProduct::query()
            // Filter by product name
            ->when(
                $request->input('search'),
                fn($query, $search) =>
                $query->where('name', 'like', "%{$search}%")
            )
            // Filter by product status (e.g., ACTIVE, INACTIVE)
            ->when(
                $request->input('status'),
                fn($query, $status) => is_array($status) ? $query->whereIn('status', array_unique(array_map('trim', array_map('strtoupper', $status)))) : $query->whereIn('status', array_unique(array_map('trim', array_map('strtoupper', explode(",", $status)))))
            )
            // Filter by active status (active/inactive mapped to 1/0)
            ->when(
                $request->input('active'),
                fn($query, $active) =>
                $query->where('active', '=', $active == 'active' ? 1 : 0)
            )
            // Filter by inventory status (OUT OF STOCK, LOW STOCK, IN STOCK)
            ->when($request->input('inventory'), function ($query, $inventory) {
                $inventories = is_array($inventory) ? $inventory : explode(',', $inventory);
                $inventories = array_unique(array_map('trim', $inventories));
            
                $query->where(function ($query) use ($inventories) {
                    foreach ($inventories as $status) {
                        $query->when(
                            $status === 'OUT OF STOCK',
                            fn($q) => $q->orWhereNull('quantity')
                                        ->orWhere('quantity', 0)
                                        ->orWhereColumn('quantity', '=', 'out_stock_level')
                        )->when(
                            $status === 'LOW STOCK',
                            fn($q) => $q->orWhereColumn('quantity', '=', 'low_stock_level')
                        )->when(
                            $status === 'IN STOCK',
                            fn($q) => $q->orWhereColumn('quantity', '>', 'low_stock_level')
                                        ->whereColumn('quantity', '!=', 'out_stock_level')
                        );
                    }
                });
            })
            
            // Filter by category names (case-insensitive partial match)
            ->when($request->input('category'), function ($query, $category) {
                $categories = is_array($category) ? $category : explode(',', $category);
                $categories = array_unique(array_map('trim', $categories));

                $query->whereHas(
                    'category',
                    fn($q) =>
                    $q->where(function ($query) use ($categories) {
                        foreach ($categories as $category) {
                            $query->orWhere('name', 'like', '%' . $category . '%');
                        }
                    })
                );
            })
            // Filter by brand names (case-insensitive partial match)
            ->when($request->input('brand'), function ($query, $brand) {
                $brands = is_array($brand) ? $brand : explode(',', $brand);
                $brands = array_unique(array_map('trim', $brands));

                $query->whereHas(
                    'brand',
                    fn($q) =>
                    $q->where(function ($query) use ($brands) {
                        foreach ($brands as $brand) {
                            $query->orWhere('name', 'like', '%' . $brand . '%');
                        }
                    })
                );
            })
            // Filter by medication types (case-insensitive partial match)
            ->when($request->input('medicationType'), function ($query, $medicationType) {
                $medicationTypes = is_array($medicationType) ? $medicationType : explode(',', $medicationType);
                $medicationTypes = array_unique(array_map('trim', $medicationTypes));

                $query->whereHas(
                    'medicationType',
                    fn($q) =>
                    $q->where(function ($query) use ($medicationTypes) {
                        foreach ($medicationTypes as $medicationType) {
                            $query->orWhere('name', 'like', '%' . $medicationType . '%');
                        }
                    })
                );
            });

        // Filter by creation date range
        if ($from = $request->input('fromDate')) {
            $query->whereDate('created_at', '>=', $from);
        }

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

        if(!$request->user()->hasRole('admin')){
            $query->businesses();
        }
        // Return paginated results with applied filters and transformations
        return $query
            ->paginate($request->has('perPage') ? $request->perPage : 10)
            ->withQueryString()
            ->through(fn(EcommerceProduct $item) => EcommerceProductResource::make($item));
    }
}
