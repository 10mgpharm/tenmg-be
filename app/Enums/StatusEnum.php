<?php

namespace App\Enums;

enum StatusEnum: string
{
    case DRAFT = 'DRAFT';            // Not yet ready for public view
    case PENDING = 'PENDING';        // Waiting for approval
    case APPROVED = 'APPROVED';      // Approved for use
    case ACTIVE = 'ACTIVE';      // Approved for use
    case REJECTED = 'REJECTED';      // Rejected, not usable
    case INACTIVE = 'INACTIVE';      // Manually inactive
    case SUSPENDED = 'SUSPENDED';    // Temporarily disabled due to issues
    case ARCHIVED = 'ARCHIVED';      // Archived, no longer active
    case FLAGGED = 'FLAGGED'; // Status indicating the item is flagged and no longer active.
}
