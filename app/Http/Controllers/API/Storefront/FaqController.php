<?php

namespace App\Http\Controllers\API\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\FaqResource;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $faqs = Faq::latest();

        return $this->returnJsonResponse(
            message: 'Faqs successfully fetched.',
            data: FaqResource::collection($faqs)
        );
    }
}
