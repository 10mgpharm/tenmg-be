# Introduction

10MG API provides comprehensive endpoints for e-commerce operations, user management, credit/BNPL services, and business integrations. The API supports role-based access control for Admin, Vendor, Supplier, Lender, and Storefront users.

<aside>
    <strong>Base URL</strong>: <code>http://localhost</code>
</aside>

This documentation provides comprehensive information about the 10MG API, including authentication, endpoints, request/response formats, and code examples.

## Authentication

Most endpoints require authentication using OAuth2 Bearer tokens via Laravel Passport. Authenticate by making a request to the authentication endpoint to receive an access token, then include it in the `Authorization` header for subsequent requests.

## API Versioning

This API is versioned and currently uses **v1**. All endpoints are prefixed with `/api/v1/`.

## Rate Limiting

Some endpoints may be rate-limited. Check response headers for rate limit information.

<aside>As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).</aside>

