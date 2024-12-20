<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteEcommerceMedicationRequest;
use App\Http\Requests\Admin\ListMedicationTypeRequest;
use App\Http\Requests\Admin\ShowEcommerceMedicationTypeRequest;
use App\Http\Requests\Admin\StoreEcommerceMedicationRequest;
use App\Http\Requests\Admin\UpdateEcommerceMedicationRequest;
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
     */
    public function __construct(private EcommerceMedicationTypeService $medicationTypeService) {}

    /**
     * Retrieve a paginated list of medication types for the authenticated user's business.
     *
     * @param  \App\Http\Requests\Admin\ListMedicationTypeRequest  $request  Validated request instance.
     */
    public function index(ListMedicationTypeRequest $request): JsonResponse
    {
        $medicationTypesQuery = EcommerceMedicationType::query()
            ->with('variations')
            ->when($request->input('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->input('status'), function ($query, $status) {
                $query->where('active', '=', $status == 'active' ? 1 : 0);
            });

        if ($request->has('sort') && $request->has('order')) {
            $sortColumn = $request->input('sort');
            $sortOrder = $request->input('order');

            $validColumns = ['name'];
            if (in_array($sortColumn, $validColumns) && in_array(strtolower($sortOrder), ['asc', 'desc'])) {
                $medicationTypesQuery->orderBy($sortColumn, $sortOrder);
            }
        } else {
            $medicationTypesQuery->orderBy('created_at', 'desc');
        }

        $medicationTypes = $medicationTypesQuery
            ->paginate($request->has('perPage') ? $request->perPage : 10)
            ->withQueryString()
            ->through(fn (EcommerceMedicationType $item) => EcommerceMedicationTypeResource::make($item));

        return $this->returnJsonResponse(
            message: 'Medication types successfully fetched.',
            data: $medicationTypes
        );
    }

    /**
     * Store a new medication type for the authenticated user's business.
     *
     * @param  \App\Http\Requests\Admin\StoreEcommerceMedicationRequest  $request  Validated request instance.
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
     * Show an ecommerce medication type.
     */
    public function show(ShowEcommerceMedicationTypeRequest $request, EcommerceMedicationType $medication_type): JsonResponse
    {
        return $medication_type
            ? $this->returnJsonResponse(
                message: 'Medication type successfully fetched.',
                data: new EcommerceMedicationTypeResource($medication_type)
            )
            : $this->returnJsonResponse(
                message: 'Oops, can\'t view medication type at the moment. Please try again later.'
            );
    }

    /**
     * Update an existing medication type for the authenticated user's business.
     *
     * @param  \App\Http\Requests\Admin\UpdateEcommerceMedicationRequest
     * $request - Validated request instance.
     * @param  \App\Models\EcommerceMedicationType
     * $medication_type - The medication type to be updated.
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
     * @param  EcommerceMedicationType  $medication_type  The medication type to be deleted.
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

    /**
     * Show an ecommerce medication type.
     */
    public function getVariationsByMedicationType(Request $request, EcommerceMedicationType $medication_type): JsonResponse
    {
        $variations = $medication_type->variations()->get()
            ->map(function ($variation) {
                $presentation = $variation->presentation?->name;
                $measurement = $variation->measurement?->name;
                $strength = $variation->strength_value;
                $package_per_roll = $variation->package_per_roll;
                $weight = $variation->weight ? "$variation->weight".'KG' : '';

                return [
                    'label' => "$presentation $strength$measurement $package_per_roll $weight",
                    'value' => $variation->id,
                    'detail' => [
                        'presentation' => $presentation,
                        'strength' => $strength,
                        'measurement' => $measurement,
                        'package_per_roll' => $package_per_roll,
                        'weight' => $variation->weight,
                    ],
                ];
            });

        return $medication_type
            ? $this->returnJsonResponse(
                message: 'Medication variations successfully fetched.',
                data: $variations,
            )
            : $this->returnJsonResponse(
                message: 'Oops, can\'t view medication type at the moment. Please try again later.'
            );
    }
}
