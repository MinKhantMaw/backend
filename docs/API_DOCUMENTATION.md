# API Documentation (All Logic)

This document covers all implemented `/api/v1` routes and the current business logic in controllers/services/jobs.

## Base Information
- Base URL: `/api/v1`
- Auth scheme: `Authorization: Bearer <access_token>` (Laravel Passport `auth:api`)
- Content type: `application/json` unless file upload endpoint is used
- Standard response shape:

```json
{
  "message": "...",
  "data": {},
  "meta": {}
}
```

Error shape:

```json
{
  "message": "...",
  "data": null,
  "errors": {}
}
```

## Global Middleware, Authorization, and Limits
- `auth:api`: access-token authentication.
- `permission:*`, `role_or_permission:*` (Spatie): permission gates for admin RBAC module.
- `ensure_role:*`: role guard for ecommerce/customer/admin access.
- `verify_webhook_signature`: validates `Stripe-Signature` with `HMAC-SHA256` against `services.stripe.webhook_secret`.
- Rate limits:
  - `throttle:auth-ecommerce`: 10/min by IP + 30/min by email.
  - `throttle:checkout`: 20/min by user ID or IP.
  - `throttle:webhooks`: 120/min by IP.

## Route Coverage Matrix (55/55)

### Core Auth + Sidebar + RBAC
| Method | Endpoint | Auth | Middleware / Permission |
|---|---|---|---|
| POST | `/auth/register` | Public | `api` |
| POST | `/auth/login` | Public | `api` |
| POST | `/auth/logout` | Required | `auth:api` |
| GET | `/auth/me` | Required | `auth:api` |
| POST | `/auth/change-password` | Required | `auth:api` |
| GET | `/auth/sidebar` | Required | `auth:api`, `permission:auth.sidebar.view` |
| GET | `/users` | Required | `auth:api`, `role_or_permission:*users*`, `permission:users.view` |
| POST | `/users` | Required | `auth:api`, `role_or_permission:*users*`, `permission:users.create` |
| GET | `/users/{user}` | Required | `auth:api`, `role_or_permission:*users*`, `permission:users.view` |
| PUT | `/users/{user}` | Required | `auth:api`, `role_or_permission:*users*`, `permission:users.update` |
| DELETE | `/users/{user}` | Required | `auth:api`, `role_or_permission:*users*`, `permission:users.delete` |
| POST | `/users/{user}/roles` | Required | `auth:api`, `role_or_permission:*users*`, `permission:users.assignRoles` |
| GET | `/roles` | Required | `auth:api`, `role_or_permission:*roles*`, `permission:roles.view` |
| POST | `/roles` | Required | `auth:api`, `role_or_permission:*roles*`, `permission:roles.create` |
| GET | `/roles/{role}` | Required | `auth:api`, `role_or_permission:*roles*`, `permission:roles.view` |
| PUT | `/roles/{role}` | Required | `auth:api`, `role_or_permission:*roles*`, `permission:roles.update` |
| DELETE | `/roles/{role}` | Required | `auth:api`, `role_or_permission:*roles*`, `permission:roles.delete` |
| PUT | `/roles/{role}/permissions` | Required | `auth:api`, `role_or_permission:*roles*`, `permission:roles.assignPermissions` |
| GET | `/permissions` | Required | `auth:api`, `role_or_permission:*permissions*`, `permission:permissions.view` |
| POST | `/permissions` | Required | `auth:api`, `role_or_permission:*permissions*`, `permission:permissions.create` |
| GET | `/permissions/{permission}` | Required | `auth:api`, `role_or_permission:*permissions*`, `permission:permissions.view` |
| PUT | `/permissions/{permission}` | Required | `auth:api`, `role_or_permission:*permissions*`, `permission:permissions.update` |
| DELETE | `/permissions/{permission}` | Required | `auth:api`, `role_or_permission:*permissions*`, `permission:permissions.delete` |

### Ecommerce Auth
| Method | Endpoint | Auth | Middleware / Limit |
|---|---|---|---|
| POST | `/auth/customer/register` | Public | `throttle:auth-ecommerce` |
| POST | `/auth/customer/login` | Public | `throttle:auth-ecommerce` |
| POST | `/auth/admin/login` | Public | `throttle:auth-ecommerce` |
| POST | `/auth/refresh` | Public | `throttle:auth-ecommerce` |
| GET | `/auth/customer/profile` | Required | `auth:api`, `ensure_role:customer` |
| PUT | `/auth/customer/profile` | Required | `auth:api`, `ensure_role:customer` |
| POST | `/auth/customer/logout` | Required | `auth:api` |
| POST | `/auth/logout-all` | Required | `auth:api` |

### Ecommerce Catalog + Cart + Orders + Webhook
| Method | Endpoint | Auth | Middleware / Limit |
|---|---|---|---|
| GET | `/products` | Public | `api` |
| GET | `/products/{slug}` | Public | `api` |
| GET | `/categories` | Public | `api` |
| POST | `/categories` | Required | `auth:api`, `ensure_role:admin,Super Admin` |
| GET | `/cart` | Public/Optional | guest or authenticated cart resolution |
| POST | `/cart/items` | Public/Optional | guest or authenticated cart resolution |
| PATCH | `/cart/items/{item}` | Public/Optional | guest or authenticated cart resolution |
| DELETE | `/cart/items/{item}` | Public/Optional | guest or authenticated cart resolution |
| POST | `/cart/merge` | Required | `auth:api` |
| POST | `/checkout` | Required | `auth:api`, `ensure_role:customer`, `throttle:checkout` |
| POST | `/orders` | Required | `auth:api`, `ensure_role:customer`, `throttle:checkout` |
| GET | `/orders` | Required | `auth:api`, `ensure_role:customer` |
| GET | `/orders/{order}` | Required | `auth:api`, `ensure_role:customer` |
| POST | `/webhooks/payments/stripe` | Public (signed) | `throttle:webhooks`, `verify_webhook_signature` |

### Ecommerce Admin
| Method | Endpoint | Auth | Middleware |
|---|---|---|---|
| GET | `/admin/products` | Required | `auth:api`, `ensure_role:admin,Super Admin` |
| POST | `/admin/products` | Required | `auth:api`, `ensure_role:admin,Super Admin` |
| GET | `/admin/products/{product}` | Required | `auth:api`, `ensure_role:admin,Super Admin` |
| PUT | `/admin/products/{product}` | Required | `auth:api`, `ensure_role:admin,Super Admin` |
| DELETE | `/admin/products/{product}` | Required | `auth:api`, `ensure_role:admin,Super Admin` |
| POST | `/admin/products/{product}/images` | Required | `auth:api`, `ensure_role:admin,Super Admin` |
| GET | `/admin/orders` | Required | `auth:api`, `ensure_role:admin,Super Admin` |
| GET | `/admin/orders/{order}` | Required | `auth:api`, `ensure_role:admin,Super Admin` |
| PATCH | `/admin/orders/{order}/status` | Required | `auth:api`, `ensure_role:admin,Super Admin` |
| GET | `/admin/analytics/overview` | Required | `auth:api`, `ensure_role:admin,Super Admin` |

## Module Details and Business Logic

## 1) Core Auth (`AuthController`, `AuthService`)

### `POST /auth/register`
Request:
- `name` required string
- `email` required unique email
- `mobile_country_code` nullable string
- `mobile_number` nullable string
- `password` required string min 8, with `password_confirmation`

Behavior:
- Creates user with hashed password.
- Sets `status=ACTIVE` and `password_changed_at=now`.
- Returns `201` with `UserResource`.

### `POST /auth/login`
Request:
- `email` required email
- `password` required string

Behavior:
- Checks password hash and requires `status=ACTIVE`.
- Issues Passport access token (`Bearer`).
- Returns token info (`access_token`, `expires_at`, `token_type`) and user.

### `POST /auth/logout`
Behavior:
- Revokes current access token.

### `GET /auth/me`
Behavior:
- Returns authenticated user + roles.

### `POST /auth/change-password`
Request:
- `current_password` required
- `new_password` required, min 8, confirmed, different from current

Behavior:
- Validates current password.
- Updates hashed password, `password_changed_at`, `updated_by`.

## 2) Sidebar (`SidebarController`)

### `GET /auth/sidebar`
Behavior:
- Returns full sidebar list for `Super Admin`.
- For non-super-admin, filters by permission checks on each item.

## 3) User Management (`UserController`, `UserService`)

### `GET /users`
Query:
- `search` nullable string
- `per_page` nullable integer 1..100

Behavior:
- Policy + permission enforced.
- Paginates users via repository.
- Returns `UserResource[]` and pagination meta.

### `POST /users`
Request:
- `name`, `email`, `address`, `password`, `roles` required
- optional `mobile_*`, `status`
- `roles` accepts names and normalized forms from request pre-processing

Behavior:
- Hashes password.
- Sets audit fields: `created_by`, `updated_by`.
- Applies role-only auth model (`syncPermissions([])`).
- Non-`Super Admin` cannot assign `Super Admin` role.

### `GET /users/{user}`
Behavior:
- Returns single `UserResource` with roles.

### `PUT /users/{user}`
Request:
- `address` currently required by validator
- others optional: `name`, `email`, `mobile_*`, `password`, `status`, `roles`

Behavior:
- Updates user, optionally password and roles.
- Sets `updated_by`.
- Keeps role-only model (`syncPermissions([])`).

### `DELETE /users/{user}`
Behavior:
- Sets `deleted_by` and `updated_by`, then soft-deletes user.

### `POST /users/{user}/roles`
Request:
- `roles` nullable array of role names (also normalizes single `role` payload)

Behavior:
- Replaces user roles (`syncRoles`).
- Clears direct permissions.

## 4) Roles (`RoleController`, `RoleService`)

### `GET /roles`
Query:
- `search`, `per_page`

Behavior:
- Returns paginated roles with permissions.

### `POST /roles`
Request:
- `name` required unique per `guard_name=api`
- `guard_name` optional, must be `api`
- `permissions` optional array of existing permission names

Behavior:
- Creates role then syncs permissions when provided.

### `GET /roles/{role}`
Behavior:
- Returns role with permissions.

### `PUT /roles/{role}`
Request:
- optional `name`, `guard_name`, `permissions`

Behavior:
- Updates role fields.
- If `permissions` key exists, fully syncs permissions.

### `DELETE /roles/{role}`
Behavior:
- Deletes role.

### `PUT /roles/{role}/permissions`
Request:
- `permissions` required array

Behavior:
- Full replace sync of role permissions.

## 5) Permissions (`PermissionController`, `PermissionService`)

### `GET /permissions`
Query:
- `search`, `per_page`

Behavior:
- Returns paginated permissions.

### `POST /permissions`
Request:
- `name` required dot-notation regex: `segment.segment[...]`
- `guard_name` optional `api`

Behavior:
- Creates permission.

### `GET /permissions/{permission}`
Behavior:
- Returns permission resource.

### `PUT /permissions/{permission}`
Request:
- optional `name`, optional `guard_name=api`

Behavior:
- Updates permission.

### `DELETE /permissions/{permission}`
Behavior:
- Deletes permission.

## 6) Ecommerce Auth (`EcommerceAuthController`, `EcommerceAuthService`)

### `POST /auth/customer/register`
Request:
- `name`, `email`, `password`, `password_confirmation`, `address`
- `device_name` optional (default `storefront`)

Behavior:
- Creates user with `customer` role (creates role if missing).
- Issues access + refresh token pair.
- Stores refresh token as SHA-256 hash, 30-day expiry.

### `POST /auth/customer/login`
### `POST /auth/admin/login`
Request:
- `email`, `password`, optional `device_name`

Behavior:
- Verifies credentials and required role (`customer` or `admin`).
- Adds `ip_address` and `user_agent` metadata.
- Returns new access + refresh pair.

### `POST /auth/refresh`
Request:
- `refresh_token` required

Behavior:
- Refresh token rotation: old token revoked, new pair issued.
- Fails when token hash missing/revoked/expired.

### `GET /auth/customer/profile`
Behavior:
- Returns customer profile with roles.

### `PUT /auth/customer/profile`
Request:
- optional `name`, `email`, `address`, `mobile_*`, `profile_image`
- `profile_image` max 5MB

Behavior:
- Replaces old profile image from `public` disk when uploading new one.
- Writes `updated_by`.
- Returns unchanged profile if payload has no changes.

### `POST /auth/customer/logout`
Behavior:
- Revokes current access token.

### `POST /auth/logout-all`
Behavior:
- Revokes all access tokens and refresh tokens for user.

## 7) Catalog (`ProductController`, `ProductCatalogService`)

### `GET /products`
Query:
- `category_id`, `q`, `min_price`, `max_price`
- `sort` in `created_at,-created_at,price,-price,name,-name`
- `per_page` 1..100

Behavior:
- Returns only `is_active=true` products.
- Includes `category` and `images`.
- Default sort desc by `created_at`.

### `GET /products/{slug}`
Behavior:
- Fetches active product by slug.
- Includes `category` and `images`.

## 8) Categories (`CategoryController`)

### `GET /categories`
Behavior:
- Returns active categories ordered by name.

### `POST /categories`
Request:
- `name`, `slug` required
- optional `parent_id`, `is_active`

Behavior:
- Creates category (default `is_active=true`).
- Admin/Super Admin only.

## 9) Cart (`CartController`, `CartService`)

### Guest/auth cart resolution
- Authenticated: uses user cart (`user_id`).
- Guest: uses `guest_cart_token` cookie (created if missing).
- Cookie attributes: HttpOnly + Secure + SameSite=Strict.

### `GET /cart`
Behavior:
- Returns cart with `items.product`.

### `POST /cart/items`
Request:
- `product_id`, `quantity`

Behavior:
- Upsert cart line by `cart_id+product_id`.
- Stock is checked with DB row lock.

### `PATCH /cart/items/{item}`
Request:
- `quantity`

Behavior:
- Updates existing cart item quantity.

### `DELETE /cart/items/{item}`
Behavior:
- Removes item from current cart.

### `POST /cart/merge`
Behavior:
- Requires auth.
- Merges guest cart into user cart by summing quantities.
- Deletes guest cart and clears `guest_cart_token` cookie.

## 10) Checkout + Customer Orders (`CheckoutController`, `CheckoutService`, `OrderController`)

### `POST /checkout`
### `POST /orders` (alias to same checkout logic)
Request:
- supports two flows:
  - Cart-based checkout (no `items`): uses authenticated cart.
  - Payload-based checkout (`items[]`): each item accepts `product_id` or `productId` + `quantity`.
- address fields accepted as either:
  - `shipping_address.{line1,city,country,postal_code}`
  - or `address.{line1,city,country,zip}` (mapped internally)
- optional: `coupon_code`, `billing_address`, `paymentId`, `paymentMethod`, `providerRef`

Behavior:
- Creates order with status `pending_payment`.
- Creates payment row with initial status `requires_payment_method`.
- Generates payment intent placeholder (`pi_*`).
- If `paymentId` or `providerRef` is provided, marks payment as `succeeded`, order as `paid`, then triggers fulfillment.
- Coupon rules: active window, usage cap, min order, percent/fixed amount, max cap.

### `GET /orders`
Behavior:
- Customer-only list of own orders with `items`, `payment`, pagination.

### `GET /orders/{order}`
Behavior:
- Customer-only order detail scoped to owner.

## 11) Payment Webhook (`PaymentWebhookController`, `PaymentService`)

### `POST /webhooks/payments/stripe`
Headers:
- `Stripe-Signature` required

Behavior:
- Signature verified before controller logic.
- Webhook event deduplicated by `event_id` in `webhook_events`.
- Handles `payment_intent.succeeded`:
  - updates payment status to `succeeded`
  - marks order as `paid` and sets `placed_at`
  - dispatches `HandleOrderPaidJob`

## 12) Admin Products, Orders, Analytics

### Products
- `GET /admin/products`: paginated products with category + images.
- `POST /admin/products`: create product; supports either `status` (`active`/`inactive`) or `is_active` boolean.
- `GET /admin/products/{product}`: detail with category + images.
- `PUT /admin/products/{product}`: update, same `status`/`is_active` mapping.
- `DELETE /admin/products/{product}`: soft delete product.
- `POST /admin/products/{product}/images`:
  - multipart form-data with `image` (max 5MB), optional `is_primary`
  - uploads to `storage/app/public/products/{productId}`
  - dispatches `GenerateProductImageVariantsJob`

### Orders
- `GET /admin/orders`: filters `status`, `from`, `to`, `per_page`; includes `user`, `payment`.
- `GET /admin/orders/{order}`: full detail with `items`, `payment`, `user`.
- `PATCH /admin/orders/{order}/status`: updates to one of:
  - `paid`, `processing`, `shipped`, `delivered`, `cancelled`, `refunded`, `payment_failed`

### Analytics
- `GET /admin/analytics/overview`
- Returns cached 5 minutes:
  - `total_revenue`
  - last 30 days `daily_sales`
  - top 10 products by quantity/revenue

## 13) Async Jobs and Side Effects
- `HandleOrderPaidJob` -> calls `OrderFulfillmentService::finalizePaidOrder`.
- Fulfillment service:
  - decrements product stock with locks
  - moves order to `processing`
  - clears customer carts
  - dispatches `SendOrderConfirmationEmailJob` and `SendAdminNewOrderNotificationJob`
- `GenerateProductImageVariantsJob` currently logs placeholder pipeline execution.

## 14) Known Route Aliases and Behavior Notes
- `POST /checkout` and `POST /orders` are equivalent and point to the same controller method.
- Public cart endpoints still support authenticated users; cart identity resolves automatically.
- `PUT /users/{user}` currently requires `address` (validator-level requirement).

## 15) Related Source Files
- Routing: `routes/api.php`
- Unified response: `app/Support/ApiResponse.php`
- Core auth: `app/Http/Controllers/Api/V1/AuthController.php`, `app/Services/AuthService.php`
- RBAC: `app/Http/Controllers/Api/V1/UserController.php`, `app/Http/Controllers/Api/V1/RoleController.php`, `app/Http/Controllers/Api/V1/PermissionController.php`
- Ecommerce auth: `app/Http/Controllers/Api/V1/Ecommerce/EcommerceAuthController.php`, `app/Services/Ecommerce/Auth/EcommerceAuthService.php`
- Catalog/cart/checkout: `app/Http/Controllers/Api/V1/Ecommerce/*`, `app/Services/Ecommerce/*`
- Middleware: `app/Http/Middleware/EnsureUserHasRole.php`, `app/Http/Middleware/VerifyWebhookSignature.php`

