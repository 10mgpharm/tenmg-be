<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateInviteRequest;
use App\Http\Requests\GuestAcceptInviteRequest;
use App\Http\Requests\GuestRejectInviteRequest;
use App\Http\Requests\ListInvitesRequest;
use App\Http\Requests\ViewInviteGuestRequest;
use App\Http\Resources\BusinessUserResource;
use App\Http\Resources\InviteResource;
use App\Http\Resources\UserResource;
use App\Models\Invite;
use App\Services\InviteService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InviteController extends Controller
{
    public function __construct(private InviteService $inviteService) {}

    /**
     * Retrieve all invite for the authenticated user's business.
     *
     * @param  ListInvitesRequest  $request  Validated request instance.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ListInvitesRequest $request)
    {
        $user = $request->user();

        $invites = $user->ownerBusinessType?->invites ?? collect();

        return $this->returnJsonResponse(
            message: 'Invites successfully fetched.',
            data: InviteResource::collection($invites)
        );
    }

    /**
     * Retrieve all team members for the authenticated user's business.
     *
     * @param  ListInvitesRequest  $request  Validated request instance.
     * @return \Illuminate\Http\JsonResponse
     */
    public function members(ListInvitesRequest $request)
    {
        $user = $request->user();

        $businessUsers = $user->ownerBusinessType?->businessUsers ?? collect();

        return $this->returnJsonResponse(
            message: 'Team members successfully fetched.',
            data: BusinessUserResource::collection($businessUsers)
        );
    }

    /**
     * Retrieve and display invitation details for a guest user.
     *
     * This method processes the request to view an invitation, using the
     * invite service to fetch the relevant invitation details. It returns
     * a JSON response containing the invite information, such as role,
     * full name, email, and action URLs for accepting or rejecting the invitation.
     *
     * @param  ViewInviteGuestRequest  $request  The validated request object containing invite query parameters.
     * @return \Illuminate\Http\JsonResponse A JSON response with the invitation details.
     */
    public function view(ViewInviteGuestRequest $request)
    {
        $data = $this->inviteService->view();

        return $this->returnJsonResponse(
            message: 'Invites successfully fetched.',
            data: $data,
        );
    }

    /**
     * Add a new team member/invite to the authenticated user's business.
     *
     * This method validates the incoming request, creates a new team member/invite
     * using the validated data, and returns a JSON response with the team member's/invite's details.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateInviteRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        $invite = $this->inviteService->store($validated, $user);

        if (! $invite) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t add invite at the moment. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: 'Invite sent successfully.',
            data: new InviteResource($invite)
        );
    }

    /**
     * Accept an invitation and create a new user account.
     *
     * This method validates the invitation details, ensures the token and invite are valid, and creates
     * a new user account if the invitation is successfully accepted. The invite token must not be expired.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function accept(GuestAcceptInviteRequest $request)
    {
        $validated = $request->validated();
        $invite = Invite::find($request->query('inviteId'));

        $user = $this->inviteService->accept($validated, $invite);

        $tokenResult = $user->createToken('Full Access Token', ['full']);

        return (new UserResource($user))
            ->additional([
                'accessToken' => [
                    'token' => $tokenResult->accessToken,
                    'tokenType' => 'bearer',
                    'expiresAt' => $tokenResult->token->expires_at,
                ],
                'message' => 'Invitation accepted successfully. You can now log in.',
                'status' => 'success',
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Reject an invitation for a guest user.
     *
     * @param  RejectInviteRequest  $request  Validated request instance.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the rejection status.
     */
    public function reject(GuestRejectInviteRequest $request)
    {
        $invite = Invite::findOrFail($request->query('inviteId'));

        // Call the service method to reject the invite
        $this->inviteService->reject($invite);

        return $this->returnJsonResponse(
            message: 'Invitation has been rejected successfully.'
        );
    }
}
