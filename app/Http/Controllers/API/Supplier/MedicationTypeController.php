<?php

namespace App\Http\Controllers\API\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\DeleteEcommerceMedicationRequest;
use App\Http\Requests\Supplier\ListMedicationTypeRequest;
use App\Http\Requests\Supplier\StoreEcommerceMedicationRequest;
use App\Http\Requests\Supplier\UpdateEcommerceMedicationRequest;
use App\Http\Resources\EcommerceMedicationTypeResource;
use App\Models\EcommerceMedicationType;
use App\Services\Admin\EcommerceMedicationTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MedicationTypeController extends Controller
{
    /**
     * MedicationTypeController constructor.
     *
     * @param \App\Services\Admin\EcommerceMedicationTypeService $medicationTypeService
     */
    public function __construct(private EcommerceMedicationTypeService $medicationTypeService) {}


    /**
     * Retrieve a paginated list of medication types for the authenticated user's business.
     *
     * @param  \App\Http\Requests\Admin\ListMedicationTypeRequest  $request  Validated request instance.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ListMedicationTypeRequest $request): JsonResponse
    {
        $medicationTypes = EcommerceMedicationType::latest()->paginate();

        return $this->returnJsonResponse(
            message: 'Medication types successfully fetched.',
            data: EcommerceMedicationTypeResource::collection($medicationTypes)->response()->getData(true)
        );
    }

    /**
     * Store a new medication type for the authenticated user's business.
     *
     * @param  \App\Http\Requests\Supplier\StoreEcommerceMedicationRequest  $request  Validated request instance.
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreEcommerceMedicationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $medicationType = $this->medicationTypeService->store($validated, $user);

        if (! $medicationType) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t create medication type at the moment. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: 'Medication type successfully created.',
            data: new EcommerceMedicationTypeResource($medicationType)
        );
    }

    /**
     * Update an existing medication type for the authenticated user's business.
     *
     * @param  \App\Http\Requests\Admin\UpdateEcommerceMedicationRequest 
     * $request - Validated request instance.
     * @param  \App\Models\EcommerceMedicationType 
     * $medication_type - The medication type to be updated.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateEcommerceMedicationRequest $request, EcommerceMedicationType $medication_type): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $medicationType = $this->medicationTypeService->update($validated, $user, $medication_type);

        if (! $medicationType) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t update medication type at the moment. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: 'Medication type successfully updated.',
            data: new EcommerceMedicationTypeResource($medication_type->refresh())
        );
    }

    /**
     * Delete an ecommerce medication type.
     *
     * @param EcommerceMedicationType $medication_type The medication type to be deleted.
     * @return JsonResponse Returns a JSON response indicating success or failure.
     */
    public function destroy(DeleteEcommerceMedicationRequest $request, EcommerceMedicationType $medication_type): JsonResponse
    {
        $isDeleted = $this->medicationTypeService->delete($medication_type);

        if (! $isDeleted) {
            return $this->returnJsonResponse(
                message: 'Cannot delete this medication type because it has associated products.',
                statusCode: Response::HTTP_BAD_REQUEST,
            );
        }

        return $this->returnJsonResponse(
            message: 'Medication type successfully deleted.',
            statusCode: Response::HTTP_OK,
        );
    }
}
