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

    public static function actives(): array
    {
        return [
            self::ACTIVE->value,
            self::APPROVED->value,
        ];
    }

    public static function inactives(): array
    {
        return [
            self::INACTIVE->value,
            self::ARCHIVED->value,
            self::DRAFT->value,
        ];
    }

    public static function pending(): array
    {
        return [
            self::PENDING->value,
        ];
    }

    public static function flagged(): array
    {
        return [
            self::REJECTED->value,
            self::SUSPENDED->value,
            self::FLAGGED->value
        ];
    }

    /**
     * Maps a status string to its corresponding group or a default value.
     *
     * @param string $status The status string to map.
     * @return string|array The mapped value(s).
     */
    public static function mapper(string $status): string|array
    {
        $status = strtoupper($status);

        return match ($status) {
            'ACTIVE', 'APPROVED' => static::actives(),
            'PENDING' => static::pending(),
            'REJECTED', 'SUSPENDED', 'FLAGGED' => static::flagged(),
            'INACTIVE', 'ARCHIVED', 'DRAFT' => static::inactives(),
            default => [self::DRAFT->value],
        };
    }
}
