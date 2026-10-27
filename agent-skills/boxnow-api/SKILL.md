---
name: boxnow-api
description: BoxNow HTTP API client patterns in this plugin — auth, delivery requests, parcels, logging. Use when changing API calls or error handling.
---

# BoxNow API services

## Layout

```
includes/services/api/
├── class-authentication-service.php   # OAuth / token cache
├── class-delivery-request-service.php # POST delivery-requests
├── class-parcel-service.php           # cancel, label PDF
└── trait-api-client.php               # base URL, bearer token
```

Registered in `APIServiceProvider` as `delivery_service`, `parcel_service`, `auth_service`.

## Endpoints (relative to configured API host)

| Operation | Method | Path |
|-----------|--------|------|
| Create delivery | POST | `/api/v1/delivery-requests` |
| Cancel parcel | POST | `/api/v1/parcels/{id}:cancel` |
| Label PDF | GET | `/api/v1/parcels/{id}/label.pdf` |

Base URL: `https://{api_url}` from settings (`Settings_Service` / WooCommerce options).

## Delivery request payload

Built in `Delivery_Request_Service::prepare_delivery_data()`:

- `orderNumber` = WooCommerce order ID (string)
- `paymentMode` / `amountToBeCollected` from COD vs other gateways
- `destination.locationId` = locker ID meta
- `destination.easyAccess` = true
- `showRecipientInformation` from admin checkbox (manual) or default true
- `notifyOnAccepted` when voucher option is `email`
- `items[]` with optional `compartmentSize` 1|2|3 for manual vouchers

## Errors

- HTTP failures: `API_Exception`, `Error_Handler::validate_http_response()`
- Business codes in JSON body (for example `P410` duplicate parcels) handled in `Order_AJAX_Handler`

## Logging

Services using `Logging_Trait` write to WooCommerce logs (`codesoup-boxnow-*`).

## Rules

- URL-encode parcel IDs in paths (`rawurlencode`)
- Do not log client secrets or full bearer tokens
- Timeouts: 15–20s on remote requests; handle `WP_Error`
