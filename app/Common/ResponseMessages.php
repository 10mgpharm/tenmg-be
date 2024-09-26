<?php

namespace App\Common;

class ResponseMessages
{
    public const METHOD_NOT_IMPLEMENTED = 'This method is not implemented.';

    public const INVALID_PARAMS = 'Invalid parameters supplied.';

    public const INVALID_LOGIN_DETAILS = 'Invalid login details supplied.';

    public const UNAUTHORIZED = 'You are not authorized to access this resource.';

    public const UNAUTHENTICATED = 'Unauthenticated.';

    public const USERS_NOT_FOUND = 'No users were found.';

    public const INVALID_USER = 'User not found.';

    public const USER_UPDATE_ERROR = 'Error occurred whilst updating user. Please try again.';

    public const USER_CREATION_ERROR = 'Failed to create user. Please try again later.';

    public const NO_REPORT_GENERATED = 'Failed to generate reports.';

    public const ERROR_PROCESSING_REQUEST = 'There was a system error while processing request.';

    public const DEPRECATED_ENDPOINT = 'This endpoint has been deprecated.';

    public const OTP_ALREADY_VERIFIED = 'Phone number is already verified.';

    public const INVALID_OTP = 'OTP is invalid';

    public const LOGOUT_FAILED = 'Failed to logout user.';

    public const INVALID_PHONE = 'Invalid phone number.';

    public const PHONE_DOES_NOT_EXIST = 'Phone number does not exist on our record.';

    public const EMAIL_ALREADY_VERIFIED = 'Email address already verified';

    public const DUPLICATE_ACCOUNT_ENTRY = 'Duplicate Entry! % account already exists';

    public const SUSPENDED_ACCOUNT = 'Your Account is suspended, please contact Admin.';

    public const NOT_FOUND_HTTP_EXCEPTION = 'Specified model or identifier does not exist!.';
}
