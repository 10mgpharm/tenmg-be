<?php

namespace App\Http\Controllers\API\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\VendorBusinessSettingAddTeamMemberRequest;
use App\Http\Requests\VendorBusinessSettingListTeamMemberRequest;
use App\Http\Resources\TeamMemberResource;
use Illuminate\Http\Request;

class VendorBusinessSettingController extends Controller
{
    /**
     * Retrieve all team members for the authenticated user's business.
     *
     * @param VendorBusinessSettingListTeamMemberRequest $request Validated request instance.
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



    /**
     * Add a new team member to the authenticated user's business.
     *
     * This method validates the incoming request, creates a new team member
     * using the validated data, and returns a JSON response with the team member's details.
     *
     * @param VendorBusinessSettingAddTeamMemberRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addTeamMember(VendorBusinessSettingAddTeamMemberRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        // Ensure that the business_id is set to the authenticated user's business.
        $validated['business_id'] = $user->ownerBusinessType->id;

        // Create the team member.
        $team_member = $user->teamMembers()->create($validated);

        return $this->returnJsonResponse(
            message: 'Team member successfully added.',
            data: (new TeamMemberResource($team_member))
        );
    }
}
