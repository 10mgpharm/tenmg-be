### Tenmg Credit – Vendor Integration Guide

This README explains how vendors can integrate with **Tenmg credit** so their own customers (for example, healthcare providers and patients) can access Tenmg financing from within the vendor’s system.

---

## 1. Overview

Tenmg credit lets you offer financing inside your own product.

As a **vendor**, you integrate with Tenmg by:

- Sending a **JSON payload** that describes the transaction and customer.
- Receiving a **`request_id`** and **`checkout_url`** from Tenmg.
- Opening the **Tenmg SDK URL** (`checkout_url`) in your frontend for the customer to complete the credit journey.
- Optionally querying and using **`request_id`** later (for example, lender match, reconciliation).

Vendors can initiate Tenmg credit requests:

- Directly from the **frontend** (browser) using their **Public Key**, or  
- From the **backend** using the same Public Key, or  
- Using a **Tenmg SDK** (where available), which wraps the same HTTP API.

---

## 2. Prerequisites

Before you start, you need:

- **Public API Key (required)**
  - Format example: `pk_live_xxxxx...` for production, `pk_test_xxxxx...` for sandbox.
  - Used in the `Public-Key` HTTP header.
  - Safe to use in frontend code (similar to Stripe’s publishable key).
- **Secret API Key (server-only)**
  - Format example: `sk_live_xxxxx...`, `sk_test_xxxxx...`.
  - **Never expose** this key in frontend or mobile apps.
  - Not required to call the Tenmg credit initiation endpoint.
- **Base URLs** (you will receive the exact values from Tenmg)
  - Sandbox (test): for example `https://sandbox-api.tenmg.ai`
  - Production (live): for example `https://api.tenmg.ai`
- **Network access** from your environment to call Tenmg APIs over HTTPS.

> **Important**: Requests **must** include a valid `Public-Key` header. Requests without a valid Public Key will be rejected.

---

## 3. Integration Options

You can integrate Tenmg credit in three main ways. All of them use the same initiation endpoint under the hood.

- **Option A – Frontend → Tenmg API (with Public Key)**
  - Your browser app calls Tenmg directly.
  - Sends `Public-Key` in headers and a JSON body with transaction details.
  - Gets back `request_id` and `checkout_url` and redirects the user.

- **Option B – Backend → Tenmg API (with Public Key)**
  - Your backend calls Tenmg.
  - Frontend talks only to your backend.
  - Backend adds the `Public-Key` header and forwards the JSON payload to Tenmg.

- **Option C – Frontend → Your backend → Tenmg API**
  - Frontend posts JSON to your backend (no Tenmg details exposed to the browser).
  - Backend validates and then calls Tenmg as in Option B.
  - Best if you want to centralise all Tenmg logic on the server.

In **all** options, the initiation endpoint requires a **valid Public Key** via the `Public-Key` header. The `Secret-Key` is not required for this endpoint and must stay on your backend.

---

## 4. Initiate Tenmg Credit (HTTP API)

This is the core endpoint that creates a Tenmg credit request.

### 4.1 Endpoint

```text
POST <BASE_URL>/api/v1/client/credit/tenmg/initiate
```

- `<BASE_URL>` is your environment base (sandbox or production).

### 4.2 Headers

- `Content-Type: application/json`
- `Accept: application/json`
- `Public-Key: <YOUR_PUBLIC_KEY>`

Example:

```text
Public-Key: pk_test_1234567890abcdef
```

### 4.3 Request Body

- The body must be a **valid, non-empty JSON object**.
- Tenmg does **not enforce a fixed schema** for this endpoint.
- In the current integration, the payload typically uses the following fields.

**Typical fields (based on current usage):**

- `amount`: number (for example `70000`)
- `businessname`: string – the name of the vendor business
- `borrower_reference`: string – unique identifier for the end customer in your system
- `transaction_history`: array (usually an empty array `[]` or a list of past transactions)
- `product_items`: array (usually an empty array `[]` or a list of items/services)
- `callback_url`: URL where Tenmg can send asynchronous updates

Tenmg will **store the JSON exactly as received**, filter out internal business-identifying fields if needed, and associate it with a `request_id`.

**Example payload (current live format):**

```json
{
  "amount": 70000,
  "businessname": "Test Business",
  "borrower_reference": "USER_1267",
  "transaction_history": [],
  "product_items": [],
  "callback_url": "https://vendor-app.example.com/callback"
}
```

### 4.4 Example – Frontend (browser) calling Tenmg directly

```javascript
async function initiateTenmgCredit() {
  const response = await fetch(
    "<BASE_URL>/api/v1/client/credit/tenmg/initiate",
    {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "Public-Key": "pk_test_1234567890abcdef" // PUBLIC key only
      },
      body: JSON.stringify({
        amount: 70000,
        businessname: "Test Business",
        borrower_reference: "USER_1267",
        transaction_history: [],
        product_items: [],
        callback_url: "https://vendor-app.example.com/callback"
      })
    }
  );

  const json = await response.json();
  if (!response.ok || !json.success) {
    console.error("Failed to initiate Tenmg credit", json);
    return;
  }

  const { request_id, checkout_url } = json.data;

  // Redirect customer to Tenmg SDK
  window.location.href = checkout_url;

  return { request_id, checkout_url };
}
```

### 4.5 Example – Backend (Node.js) calling Tenmg

```javascript
import fetch from "node-fetch";

async function initiateTenmgCreditFromBackend(payload) {
  const response = await fetch(
    "<BASE_URL>/api/v1/client/credit/tenmg/initiate",
    {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "Public-Key": process.env.TENMG_PUBLIC_KEY
      },
      body: JSON.stringify(payload)
    }
  );

  const json = await response.json();
  if (!response.ok || !json.success) {
    throw new Error("Failed to initiate Tenmg credit: " + JSON.stringify(json));
  }

  return json.data; // { request_id, checkout_url }
}
```

### 4.6 Successful Response

```json
{
  "success": true,
  "message": "Tenmg credit request initiated",
  "data": {
    "request_id": "TENMGREQ-AB12CD34EF56",
    "checkout_url": "https://sdk.tenmg.ai/tenmg-credit?request_id=TENMGREQ-AB12CD34EF56"
  }
}
```

- **`request_id`**: Tenmg’s unique ID for this credit request. Store this.
- **`checkout_url`**: Tenmg SDK URL to open for the user.

### 4.7 Error Responses (examples)

- **Empty or invalid body**:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "payload": [
      "Request body cannot be empty. Please provide a valid JSON payload."
    ]
  }
}
```

- **Missing or invalid `Public-Key`**:

```json
{
  "success": false,
  "message": "You are not authorised to call this endpoint"
}
```

---

## 5. Initiate via SDK (Optional)

Tenmg may provide official SDKs (for example, JavaScript/Node or PHP) to simplify integration. SDKs internally call the same initiation endpoint and return `request_id` and `checkout_url`.

> The package names and method names below are **examples**. Use the actual SDK names and methods provided by Tenmg.

### 5.1 JavaScript / Node.js SDK Example

```javascript
import { TenmgClient } from "@tenmg/sdk"; // example name

const client = new TenmgClient({
  publicKey: "pk_test_1234567890abcdef",
  baseUrl: "<BASE_URL>"
});

async function initiateTenmgCreditWithSdk() {
  const response = await client.credit.initiate({
    amount: 70000,
    businessname: "Test Business",
    borrower_reference: "USER_1267",
    transaction_history: [],
    product_items: [],
    callback_url: "https://vendor-app.example.com/callback"
  });

  const { request_id, checkout_url } = response.data;
  return { request_id, checkout_url };
}
```

### 5.2 PHP SDK Example

```php
use Tenmg\Sdk\Client; // example namespace

$client = new Client([
    'public_key' => 'pk_test_1234567890abcdef',
    'base_url'   => '<BASE_URL>',
]);

$response = $client->credit()->initiate([
    'amount'             => 70000,
    'businessname'       => 'Test Business',
    'borrower_reference' => 'USER_1267',
    'transaction_history'=> [],
    'product_items'      => [],
    'callback_url'       => 'https://vendor-app.example.com/callback',
]);

$requestId   = $response['data']['request_id'];
$checkoutUrl = $response['data']['checkout_url'];
```

---

## 6. Using `checkout_url` in Your Frontend

Once you have `checkout_url`, you need to present the Tenmg SDK UI to your user.

### 6.1 Full-page redirect

```javascript
window.location.href = checkout_url;
```

### 6.2 Popup window

```javascript
const popup = window.open(
  checkout_url,
  "tenmg-credit",
  "width=500,height=700"
);

// Optionally listen for postMessage events if Tenmg SDK supports that.
```

The SDK URL (`/tenmg-credit?request_id=...`) uses `request_id` to retrieve the stored payload and guide the user through the credit process.

---

## 7. Retrieve Stored Request by `request_id`

You can query Tenmg later to see what was stored or to debug a specific `request_id`.

### 7.1 Endpoint

```text
GET <BASE_URL>/api/v1/client/credit/tenmg/requests/{request_id}
```

### 7.2 Headers

- `Accept: application/json`
- `Public-Key: <YOUR_PUBLIC_KEY>`



If `request_id` does not exist, the API will return a 404 with a “not found” style message.

---

## 8. What Happens After Redirecting to the SDK

Once you redirect the user to the Tenmg SDK using `checkout_url`, the rest of the flow happens **inside Tenmg**:

- Tenmg uses the `request_id` to fetch the stored payload.
- Tenmg runs its own internal logic (including matching, approvals, etc.).
- Tenmg updates the status of the request and, if you provided a `callback_url`, can notify your system.

As a **vendor**, you usually do **not** need to call any extra “match” endpoint yourself. Redirecting to the SDK with `checkout_url` is enough; the SDK talks to Tenmg’s internal APIs on your behalf.

---

## 9. Best Practices

- **Have your Public Key ready**
  - Always include `Public-Key: <YOUR_PUBLIC_KEY>` when calling Tenmg vendor APIs.
- **Never expose your Secret Key in frontend**
  - Keep `sk_...` keys strictly on your backend or secure server environment.
- **Store `request_id`**
  - Save `request_id` alongside your `order_reference` and `borrower_reference`.
  - Use it later for status checks, matching, and reconciliation.
- **Use `callback_url` for async updates**
  - Provide a secure HTTPS endpoint to receive updates from Tenmg about the credit status.
- **Log key events**
  - Log:
    - Incoming payloads (minus sensitive data),
    - Tenmg responses (`request_id`, `checkout_url`),
    - Errors and status changes.
- **Start in sandbox**
  - Integrate and test using **test keys** (`pk_test_...`) and the sandbox base URL.
  - Switch to **live keys** only after successful testing and Tenmg’s approval.

