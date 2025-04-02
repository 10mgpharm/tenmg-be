<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteEcommerceDiscountRequest;
use App\Http\Requests\ListEcommerceDiscountRequest;
use App\Http\Requests\ShowEcommerceDiscountRequest;
use App\Http\Requests\StoreEcommerceDiscountRequest;
use App\Http\Requests\UpdateEcommerceDiscountRequest;
use App\Http\Resources\EcommerceDiscountResource;
use App\Models\EcommerceDiscount;
use App\Models\User;
use App\Services\EcommerceDiscountService;
use Illuminate\Http\Request;

class EcommerceDiscountController extends Controller
{
    public function __construct(private EcommerceDiscountService $discountService,) {}


    /**
     * Retrieve all discounts.
     *
     * Fetches and filters discounts based on the validated request parameters.
     *
     * @param ListEcommerceDiscountRequest $request Validated request instance.
     * @return \Illuminate\Http\JsonResponse JSON response containing the discounts.
     */
    public function index(ListEcommerceDiscountRequest $request)
    {

        $discounts = $this->discountService->index($request);

        return $this->returnJsonResponse(
            message: 'Discounts successfully fetched.',
            data: $discounts
        );
    }



    /**
     * Create a new discount.
     *
     * Validates the request data, creates a new discount associated with the authenticated user,
     * and returns the newly created discount resource.
     *
     * @param StoreEcommerceDiscountRequest $request Validated request instance.
     * @return \Illuminate\Http\JsonResponse JSON response containing the created discount.
     */
    public function store(StoreEcommerceDiscountRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        $discount = $this->discountService->store($validated, $user);

        if (!$discount) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t add discount at the moment. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: 'Discount created successfully.',
            data: new EcommerceDiscountResource($discount)
        );
    }

    /**
     * Display the specified discount.
     *
     * Returns details of a specific discount, including its business and applicable products.
     *
     * @param ShowEcommerceDiscountRequest $request Validated request instance.
     * @param EcommerceDiscount $discount The discount to display.
     * @return \Illuminate\Http\JsonResponse JSON response containing the discount details.
     */
    public function show(ShowEcommerceDiscountRequest $request, EcommerceDiscount $discount)
    {
        return $discount
            ? $this->returnJsonResponse(
                message: 'Discount successfully fetched.',
                data: new EcommerceDiscountResource($discount->load(['business', 'applicableProducts']))
            )
            : $this->returnJsonResponse(
                message: 'Oops, cannot view discount at the moment. Please try again later.'
            );
    }

    /**
     * Update the specified discount.
     *
     * Validates the request data, updates the specified discount, and returns the updated discount resource.
     *
     * @param UpdateEcommerceDiscountRequest $request Validated request instance.
     * @param EcommerceDiscount $discount The discount to update.
     * @return \Illuminate\Http\JsonResponse JSON response containing the updated discount.
     */
    public function update(UpdateEcommerceDiscountRequest $request,  EcommerceDiscount $discount)
    {
        $validated = $request->validated();
        $user = $request->user();

        $isUpdated = $this->discountService->update($validated, $user, $discount);

        if (!$isUpdated) {
            return $this->returnJsonResponse(
                message: 'Unable to update the discount at this time. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: "Discount successfully updated.",
            data: new EcommerceDiscountResource($discount->refresh())
        );
    }

    /**
     * Delete the specified discount.
     *
     * Removes the specified discount from the database and returns a success message.
     *
     * @param DeleteEcommerceDiscountRequest $request Validated request instance.
     * @param EcommerceDiscount $discount The discount to delete.
     * @return \Illuminate\Http\JsonResponse JSON response indicating the result of the operation.
     */
    public function destroy(DeleteEcommerceDiscountRequest $request, EcommerceDiscount $discount)
    {
        $isDeleted = $this->discountService->delete($discount);

        if (!$isDeleted) {
            return $this->returnJsonResponse(
                message: 'Unable to delete the discount at this time. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: "Discount successfully deleted.",
        );
    }

    /**
     * Search discounts.
     *
     * Fetches and filters discounts based on the validated request parameters.
     *
     * @param ListEcommerceDiscountRequest $request Validated request instance.
     * @return \Illuminate\Http\JsonResponse JSON response containing the discounts.
     */
    public function search(ListEcommerceDiscountRequest $request)
    {

        $discounts = $this->discountService->search($request);

        return $this->returnJsonResponse(
            message: 'Discounts successfully fetched.',
            data: $discounts
        );
    }

    public function count(ListEcommerceDiscountRequest $request)
    {

        $result = EcommerceDiscount::businesses()
            ->selectRaw('
                COUNT(CASE WHEN status != "EXPIRED" THEN 1 END) as total,
                SUM(CASE WHEN status = "ACTIVE" THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = "INACTIVE" THEN 1 ELSE 0 END) as inactive
            ')
            ->first();
            
        $counts = [
            'total' => $result->total,
            'active' => (int) $result->getRawOriginal('active'),
            'inactive' => (int) $result->inactive,
        ];

        return $this->returnJsonResponse(
            message: 'Discounts successfully counted.',
            data: $counts
        );
    }
}
