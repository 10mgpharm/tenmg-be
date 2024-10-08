<?php

namespace App\Http\Controllers\API\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\VendorBusinessSettingListTeamMemberRequest;
use App\Http\Resources\TeamMemberResource;
use Illuminate\Http\Request;

class VendorBusinessSettingController extends Controller
{
    /**
     * Retrieve all team members for the authenticated user's business.
     *
     * @param  VendorBusinessSettingListTeamMemberRequest  $request  Validated request instance.
     * @return \Illuminate\Http\JsonResponse
     */
    public function teamMembers(VendorBusinessSettingListTeamMemberRequest $request)
    {
        $user = $request->user();

        $team_members = $user->ownerBusinessType?->teamMembers ?? collect();

        return $this->returnJsonResponse(
            message: 'Team members successfully fetched.',
            data: TeamMemberResource::collection($team_members)
        );
    }
}
