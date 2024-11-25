<?php

namespace App\Http\Controllers\API\Admin;

use App\Models\Faq;
use App\Services\Admin\FaqService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\FaqResource;
use App\Http\Requests\Admin\DeleteFaqRequest;
use App\Http\Requests\Admin\ListFaqRequest;
use App\Http\Requests\Admin\StoreFaqRequest;
use App\Http\Requests\Admin\UpdateFaqRequest;
use Illuminate\Http\JsonResponse;

class FaqController extends Controller
{
    public function __construct(private FaqService $faqService) {}

    /**
     * Display a listing of FAQs.
     *
     * @param ListFaqRequest $request The request object containing filters for FAQs.
     * 
     * @return JsonResponse Paginated list of FAQs with success message.
     */
    public function index(ListFaqRequest $request): JsonResponse
    {
        $faqs = $this->faqService->index($request);

        return $this->returnJsonResponse(
            message: 'FAQs successfully fetched.',
            data: FaqResource::collection($faqs)->response()->getData(true)
        );
    }

    /**
     * Store a newly created FAQ.
     *
     * @param StoreFaqRequest $request The request object containing FAQ data.
     * 
     * @return JsonResponse JSON response confirming creation or an error message.
     */
    public function store(StoreFaqRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $faq = $this->faqService->store($validated, $user);

        if (!$faq) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t create FAQ at the moment. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: 'FAQ successfully created.',
            data: new FaqResource($faq)
        );
    }

    /**
     * Update the specified FAQ.
     *
     * @param UpdateFaqRequest $request The request object containing updated FAQ data.
     * @param Faq $faq The FAQ instance to update.
     * 
     * @return JsonResponse JSON response confirming the update or an error message.
     */
    public function update(UpdateFaqRequest $request, Faq $faq): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $isUpdated = $this->faqService->update($validated, $user, $faq);

        if (!$isUpdated) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t update FAQ at the moment. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: 'FAQ successfully updated.',
            data: new FaqResource($faq->refresh())
        );
    }

    /**
     * Remove the specified FAQ.
     *
     * @param DeleteFaqRequest $request The request object for deleting the FAQ.
     * @param Faq $faq The FAQ instance to delete.
     * 
     * @return JsonResponse JSON response confirming deletion.
     */
    public function destroy(DeleteFaqRequest $request, Faq $faq): JsonResponse
    {
        $faq->delete();

        return $this->returnJsonResponse(
            message: 'FAQ successfully deleted.'
        );
    }
}
