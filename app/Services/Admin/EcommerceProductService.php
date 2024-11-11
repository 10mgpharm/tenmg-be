<?php


namespace App\Services\Admin;

use App\Enums\StatusEnum;
use App\Models\EcommerceBrand;
use App\Models\EcommerceCategory;
use App\Models\EcommerceMedicationType;
use App\Models\EcommerceProduct;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\Interfaces\IEcommerceProductService;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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
     * @param array $validated The validated data for creating the product.
     * @param User $user The user creating the product.
     * @return EcommerceProduct|null The created product or null on failure.
     * @throws Exception If product creation fails.
     */
    public function store(array $validated, User $user): ?EcommerceProduct
    {
        try {
            return DB::transaction(function () use ($validated, $user) {
                // Ensure category exists or create it
                $category = EcommerceCategory::firstOrCreate(
                    ['name' => $validated['category_name']],
                    [
                        'slug' => Str::slug($validated['category_name']),
                        'business_id' => $user->ownerBusinessType->id,
                        'created_by_id' => $user->id,
                        'status' => StatusEnum::APPROVED->value,
                        'active' => true,
                    ]
                );

                // Ensure brand exists or create it
                $brand = EcommerceBrand::firstOrCreate(
                    ['name' => $validated['brand_name']],
                    [
                        'slug' => Str::slug($validated['brand_name']),
                        'business_id' => $user->ownerBusinessType->id,
                        'created_by_id' => $user->id,
                        'status' => StatusEnum::APPROVED->value,
                        'active' => true,
                    ]
                );

                // Ensure medication type exists or create it
                $medicationType = EcommerceMedicationType::firstOrCreate(
                    ['name' => $validated['medication_type_name']],
                    [
                        'slug' => Str::slug($validated['medication_type_name']),
                        'business_id' => $user->ownerBusinessType->id,
                        'created_by_id' => $user->id,
                        'status' => StatusEnum::APPROVED->value,
                        'active' => true,
                    ]
                );

                $product = $user->products()->create([
                    ...$validated,
                    'business_id' => $user->ownerBusinessType->id,
                    'ecommerce_category_id' => $category->id,
                    'ecommerce_brand_id' => $brand->id,
                    'ecommerce_medication_type_id' => $medicationType->id,
                    'created_by_id' => $user->id,
                    'slug' => Str::slug($validated['name']),
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
     * @param array $validated The validated data for updating the product.
     * @param User $user The user updating the product.
     * @param EcommerceProduct $product The product to be updated.
     * @return bool|null True on success, null on failure.
     * @throws Exception If the product update fails.
     */
    public function update(array $validated, User $user, EcommerceProduct $product): ?bool
    {
        try {
            return DB::transaction(function () use ($validated, $user, $product) {
                // Ensure category exists or create it
                $category = EcommerceCategory::firstOrCreate(
                    ['name' => $validated['category_name']],
                    [
                        'slug' => Str::slug($validated['category_name']),
                        'business_id' => $user->ownerBusinessType->id,
                        'created_by_id' => $user->id,
                        'status' => StatusEnum::APPROVED->value,
                        'active' => true,
                    ]
                );

                // Ensure brand exists or create it
                $brand = EcommerceBrand::firstOrCreate(
                    ['name' => $validated['brand_name']],
                    [
                        'slug' => Str::slug($validated['brand_name']),
                        'business_id' => $user->ownerBusinessType->id,
                        'created_by_id' => $user->id,
                        'status' => StatusEnum::APPROVED->value,
                        'active' => true,
                    ]
                );

                // Ensure medication type exists or create it
                $medicationType = EcommerceMedicationType::firstOrCreate(
                    ['name' => $validated['medication_type_name']],
                    [
                        'slug' => Str::slug($validated['medication_type_name']),
                        'business_id' => $user->ownerBusinessType->id,
                        'created_by_id' => $user->id,
                        'status' => StatusEnum::APPROVED->value,
                        'active' => true,
                    ]
                );

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
                    ...$validated,
                    'business_id' => $user->ownerBusinessType->id,
                    'ecommerce_category_id' => $category->id,
                    'ecommerce_brand_id' => $brand->id,
                    'ecommerce_medication_type_id' => $medicationType->id,
                    'updated_by_id' => $user->id,
                    'slug' => Str::slug($validated['name'] ?? $product->name),
                ]);
                
                $updateProductDetails = $product->productDetails()->update($validated);
                
                return $updateProduct || $updateProductDetails;
                
            });
        } catch (Exception $e) {
            throw new Exception('Failed to update product: ' . $e->getMessage());
        }
    }
}
