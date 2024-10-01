<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Http\Requests\CreateInviteRequest;
use App\Http\Requests\VendorBusinessSettingListTeamMemberRequest;
use App\Http\Resources\InviteResource;
use App\Services\InviteService;

class InviteController extends Controller
{

    public function __construct(private InviteService $inviteService,) {}


    /**
     * Add a new team member/invite to the authenticated user's business.
     *
     * This method validates the incoming request, creates a new team member/invite
     * using the validated data, and returns a JSON response with the team member's/invite's details.
     *
     * @param CreateInviteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateInviteRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        $invite = $this->inviteService->store($validated, $user);

        return $this->returnJsonResponse(
            message: 'Invite sent successfully.',
            data: (new InviteResource($invite))
        );
    }
}
