<?php

namespace App\Http\Controllers\API\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\DeleteEcommerceMedicationVariationRequest;
use App\Http\Requests\Supplier\ListEcommerceMedicationVariationRequest;
use App\Http\Requests\Supplier\ShowEcommerceMedicationVariationRequest;
use App\Http\Requests\Supplier\StoreEcommerceMedicationVariationRequest;
use App\Http\Requests\Supplier\UpdateEcommerceMedicationVariationRequest;
use App\Http\Resources\EcommerceMedicationVariationResource;
use App\Models\EcommerceMedicationVariation;
use App\Services\Admin\EcommerceMedicationVariationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class EcommerceMedicationVariationController extends Controller
{
    public function __construct(private EcommerceMedicationVariationService $medicationVariationService) {}

    /**
     * List all ecommerce medication variations.
     *
     * @param ListEcommerceMedicationVariationRequest $request
     * @return JsonResponse
     */
    public function index(ListEcommerceMedicationVariationRequest $request): JsonResponse
    {
        $medication_variations = EcommerceMedicationVariation::latest()->paginate();

        return $this->returnJsonResponse(
            message: 'Medication variations successfully fetched.',
            data: EcommerceMedicationVariationResource::collection($medication_variations)->response()->getData(true)
        );
    }

    /**
     * Store a new ecommerce medication_variation.
     *
     * @param StoreEcommerceMedicationVariationRequest $request
     * @return JsonResponse
     */
    public function store(StoreEcommerceMedicationVariationRequest $request): JsonResponse
    {
        $medication_variation = $this->medicationVariationService->store(
            $request->validated(),
            $request->user()
        );

        return $medication_variation
            ? $this->returnJsonResponse(
                message: 'Medication variation successfully created.',
                data: new EcommerceMedicationVariationResource($medication_variation)
            )
            : $this->returnJsonResponse(
                message: 'Oops, cannot create medication variation at the moment. Please try again later.'
            );
    }

    /**
     * Show an ecommerce medication_variation.
     *
     * @param ShowEcommerceMedicationVariationRequest $request
     * @return JsonResponse
     */
    public function show(ShowEcommerceMedicationVariationRequest $request, EcommerceMedicationVariation $medication_variation): JsonResponse
    {
        return $medication_variation
            ? $this->returnJsonResponse(
                message: 'Medication variation successfully fetched.',
                data: new EcommerceMedicationVariationResource($medication_variation->load(['presentation', 'package', 'medicationType']))
            )
            : $this->returnJsonResponse(
                message: 'Oops, cannot fetch medication variation at the moment. Please try again later.'
            );
    }

    /**
     * Update an existing ecommerce medication_variation.
     *
     * @param UpdateEcommerceMedicationVariationRequest $request
     * @param EcommerceMedicationVariation $medication_variation
     * @return JsonResponse
     */
    public function update(UpdateEcommerceMedicationVariationRequest $request, EcommerceMedicationVariation $medication_variation): JsonResponse
    {
        $isUpdated = $this->medicationVariationService->update(
            $request->validated(),
            $request->user(),
            $medication_variation
        );

        return $isUpdated
            ? $this->returnJsonResponse(
                message: 'Medication variation successfully updated.',
                data: new EcommerceMedicationVariationResource($medication_variation->refresh())
            )
            : $this->returnJsonResponse(
                message: 'Oops, cannot update medication variation at the moment. Please try again later.'
            );
    }

    /**
     * Delete an ecommerce medication_variation.
     *
     * @param DeleteEcommerceMedicationVariationRequest $request
     * @param EcommerceMedicationVariation $medication_variation
     * @return JsonResponse
     */
    public function destroy(DeleteEcommerceMedicationVariationRequest $request, EcommerceMedicationVariation $medication_variation): JsonResponse
    {
        $isDeleted = $this->medicationVariationService->delete($medication_variation);

        return $isDeleted
            ? $this->returnJsonResponse(
                message: 'Medication variation successfully deleted.',
                statusCode: Response::HTTP_OK
            )
            : $this->returnJsonResponse(
                message: 'Cannot delete this medication variation because it has associated products.',
                statusCode: Response::HTTP_BAD_REQUEST
            );
    }
}
