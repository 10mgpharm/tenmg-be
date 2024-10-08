<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\DashboardRequest;
use App\Http\Resources\Supplier\DashboardResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Fetch the supplier's dashboard data.
     *
     * This method handles the retrieval of the authenticated supplier's dashboard details.
     * The dashboard is specific to users with the 'SUPPLIER' role, and the response 
     * contains all relevant information related to the supplier's account.
     *
     * @param  \App\Http\Requests\Supplier\DashboardRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(DashboardRequest $request): JsonResponse
    {
        $user = $request->user();

        return $this->returnJsonResponse(
            message: 'Dashboard successfully fetched.',
            data: new DashboardResource($user),
        );
    }
}
