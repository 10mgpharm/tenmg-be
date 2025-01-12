<?php

namespace App\Http\Controllers\API\Admin;

use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteEcommercePresentationRequest;
use App\Http\Requests\Admin\ListEcommercePresentationRequest;
use App\Http\Requests\Admin\ShowEcommercePresentationRequest;
use App\Http\Requests\Admin\StoreEcommercePresentationRequest;
use App\Http\Requests\Admin\UpdateEcommercePresentationRequest;
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
     */
    public function index(ListEcommercePresentationRequest $request): JsonResponse
    {
        $presentations = EcommercePresentation::where('active', 1)->whereIn('status', [StatusEnum::ACTIVE->value, StatusEnum::APPROVED->value])->get();

        return $this->returnJsonResponse(
            message: 'Presentation successfully fetched.',
            data: EcommercePresentationResource::collection($presentations)
        );
    }

    /**
     * Store a new ecommerce Presentation.
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
     * @param  EcommercePresentation  $presentation
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
     * @param  EcommercePresentation  $presentation
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


    /**
     * Search and filter EcommercePresentations based on the provided criteria.
     *
     * @param ListEcommercePresentationRequest $request The incoming request containing search, filter, and pagination parameters.
     * @return JsonResponse A JSON response with the paginated list of presentations.
     */
    public function search(ListEcommercePresentationRequest $request): JsonResponse
    {
        $query = EcommercePresentation::query()
            ->when($request->input('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', strtoupper($status));
            })
            ->when($request->input('active'), function ($query, $active) {
                $query->where('active', '=', $active == 'active' ? 1 : 0);
            });

        if ($request->has('sort') && $request->has('order')) {
            $sortColumn = $request->input('sort');
            $sortOrder = $request->input('order');

            $validColumns = ['name'];
            if (in_array($sortColumn, $validColumns) && in_array(strtolower($sortOrder), ['asc', 'desc'])) {
                $query->orderBy($sortColumn, $sortOrder);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $presentations = $query
            ->paginate($request->has('perPage') ? $request->perPage : 10)
            ->withQueryString()
            ->through(fn (EcommercePresentation $item) => EcommercePresentationResource::make($item));

        return $this->returnJsonResponse(
            message: 'Presentations successfully fetched.',
            data: $presentations
        );
    }

}
