# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_AUTH_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

This API uses Laravel Passport (OAuth2) for authentication. Obtain your access token by logging in through the authentication endpoint. Include the token in the Authorization header as: <code>Authorization: Bearer {token}</code>
