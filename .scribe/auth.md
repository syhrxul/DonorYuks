# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_BEARER_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Token Sanctum diperoleh dengan memanggil <code>POST /api/login</code> atau <code>POST /api/register</code>. Kirim token pada header <code>Authorization: Bearer {token}</code>.
