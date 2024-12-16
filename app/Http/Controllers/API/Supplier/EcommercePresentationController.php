<?php

namespace App\Http\Controllers\API\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\DeleteEcommercePresentationRequest;
use App\Http\Requests\Supplier\ListEcommercePresentationRequest;
use App\Http\Requests\Supplier\ShowEcommercePresentationRequest;
use App\Http\Requests\Supplier\StoreEcommercePresentationRequest;
use App\Http\Requests\Supplier\UpdateEcommercePresentationRequest;
use App\Http\Resources\EcommercePresentationResource;
use App\Models\EcommercePresentation;
use App\Services\Admin\EcommercePresentationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class EcommercePresentationController extends Controller
{
    public function __construct(private EcommercePresentationService $presentationService) {}

    /**
     * List all ecommerce presentations.
     *
     * @param ListEcommercePresentationRequest $request
     * @return JsonResponse
     */
    public function index(ListEcommercePresentationRequest $request): JsonResponse
    {
        $Presentations = EcommercePresentation::latest()->paginate();

        return $this->returnJsonResponse(
            message: 'Presentations successfully fetched.',
            data: EcommercePresentationResource::collection($Presentations)->response()->getData(true)
        );
    }

    /**
     * Store a new ecommerce Presentation.
     *
     * @param StoreEcommercePresentationRequest $request
     * @return JsonResponse
     */
    public function store(StoreEcommercePresentationRequest $request): JsonResponse
    {
        $Presentation = $this->presentationService->store(
            $request->validated(),
            $request->user()
        );

        return $Presentation
            ? $this->returnJsonResponse(
                message: 'Presentation successfully created.',
                data: new EcommercePresentationResource($Presentation)
            )
            : $this->returnJsonResponse(
                message: 'Oops, cannot create presentation at the moment. Please try again later.'
            );
    }

    /**
     * Show an ecommerce presentation.
     *
     * @param ShowEcommercePresentationRequest $request
     * @return JsonResponse
     */
    public function show(ShowEcommercePresentationRequest $request, EcommercePresentation $presentation): JsonResponse
    {
        return $presentation
            ? $this->returnJsonResponse(
                message: 'Presentation successfully fetched.',
                data: new EcommercePresentationResource($presentation)
            )
            : $this->returnJsonResponse(
                message: 'Oops, cannot fetch presentation at the moment. Please try again later.'
            );
    }

    /**
     * Update an existing ecommerce presentation.
     *
     * @param UpdateEcommercePresentationRequest $request
     * @param EcommercePresentation $presentation
     * @return JsonResponse
     */
    public function update(UpdateEcommercePresentationRequest $request, EcommercePresentation $Presentation): JsonResponse
    {
        $isUpdated = $this->presentationService->update(
            $request->validated(),
            $request->user(),
            $Presentation
        );

        return $isUpdated
            ? $this->returnJsonResponse(
                message: 'Presentation successfully updated.',
                data: new EcommercePresentationResource($Presentation->refresh())
            )
            : $this->returnJsonResponse(
                message: 'Oops, cannot update presentation at the moment. Please try again later.'
            );
    }

    /**
     * Delete an ecommerce presentation.
     *
     * @param DeleteEcommercePresentationRequest $request
     * @param EcommercePresentation $presentation
     * @return JsonResponse
     */
    public function destroy(DeleteEcommercePresentationRequest $request, EcommercePresentation $Presentation): JsonResponse
    {
        $isDeleted = $this->presentationService->delete($Presentation);

        return $isDeleted
            ? $this->returnJsonResponse(
                message: 'Presentation successfully deleted.',
                statusCode: Response::HTTP_OK
            )
            : $this->returnJsonResponse(
                message: 'Cannot delete this presentation because it has associated products.',
                statusCode: Response::HTTP_BAD_REQUEST
            );
    }
}
