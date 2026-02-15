# eCommerce API Implementation Blueprint (`/api/v1`)

## Auth
- `POST /api/v1/auth/customer/register`
  - Controller: `App\Http\Controllers\Api\V1\Ecommerce\EcommerceAuthController@registerCustomer`
  - Contract: `App\Contracts\Http\Controllers\Api\V1\Ecommerce\EcommerceAuthControllerContract`
  - Service: `App\Services\Ecommerce\Auth\EcommerceAuthService::registerCustomer`
- `POST /api/v1/auth/customer/login`
  - Controller: `...@loginCustomer`
  - Service: `EcommerceAuthService::loginCustomer`
- `POST /api/v1/auth/admin/login`
  - Controller: `...@loginAdmin`
  - Service: `EcommerceAuthService::loginAdmin`
- `POST /api/v1/auth/refresh`
  - Controller: `...@refresh`
  - Service: `EcommerceAuthService::refresh` (rotation + revocation)
- `POST /api/v1/auth/customer/logout`
  - Middleware: `auth:api`
  - Service: `EcommerceAuthService::logout`
- `POST /api/v1/auth/logout-all`
  - Middleware: `auth:api`
  - Service: `EcommerceAuthService::logoutAll`

## Catalog
- `GET /api/v1/products`
  - Controller: `ProductController@index`
  - Service: `ProductCatalogService::paginate`
- `GET /api/v1/products/{slug}`
  - Controller: `ProductController@show`
  - Service: `ProductCatalogService::findBySlug`

## Cart
- `GET /api/v1/cart`
  - Controller: `CartController@show`
  - Service: `CartService::getForUser|getForGuest`
- `POST /api/v1/cart/items`
  - Controller: `CartController@store`
  - Service: `CartService::addOrUpdateItem`
- `PATCH /api/v1/cart/items/{item}`
  - Controller: `CartController@update`
  - Service: `CartService::addOrUpdateItem`
- `DELETE /api/v1/cart/items/{item}`
  - Controller: `CartController@destroy`
  - Service: `CartService::removeItem`
- `POST /api/v1/cart/merge`
  - Middleware: `auth:api`
  - Controller: `CartController@mergeGuest`
  - Service: `CartService::mergeGuestIntoUser`

## Checkout + Orders
- `POST /api/v1/checkout`
  - Middleware: `auth:api`, `ensure_role:customer`
  - Controller: `CheckoutController@store`
  - Service: `CheckoutService::checkout`
- `GET /api/v1/orders`
  - Middleware: `auth:api`, `ensure_role:customer`
  - Controller: `OrderController@index`
- `GET /api/v1/orders/{order}`
  - Middleware: `auth:api`, `ensure_role:customer`
  - Controller: `OrderController@show`

## Payments
- `POST /api/v1/webhooks/payments/stripe`
  - Middleware: `verify_webhook_signature`
  - Controller: `PaymentWebhookController@handleStripe`
  - Service: `PaymentService::processWebhook`
  - Job: `HandleOrderPaidJob`
  - Fulfillment: `OrderFulfillmentService::finalizePaidOrder`

## Admin
- `GET /api/v1/admin/products`
- `POST /api/v1/admin/products`
- `GET /api/v1/admin/products/{product}`
- `PUT /api/v1/admin/products/{product}`
- `DELETE /api/v1/admin/products/{product}`
- `POST /api/v1/admin/products/{product}/images`
  - Middleware: `auth:api`, `ensure_role:admin`
  - Controller: `AdminProductController`
  - Service: `AdminProductService`
  - Job: `GenerateProductImageVariantsJob`

- `GET /api/v1/admin/orders`
- `GET /api/v1/admin/orders/{order}`
- `PATCH /api/v1/admin/orders/{order}/status`
  - Controller: `AdminOrderController`

- `GET /api/v1/admin/analytics/overview`
  - Controller: `AdminAnalyticsController`
  - Service: `AnalyticsService::overview`

## Jobs
- `App\Jobs\Ecommerce\HandleOrderPaidJob`
- `App\Jobs\Ecommerce\SendOrderConfirmationEmailJob`
- `App\Jobs\Ecommerce\SendAdminNewOrderNotificationJob`
- `App\Jobs\Ecommerce\GenerateProductImageVariantsJob`

## Migrations
- `2026_02_15_000100_create_ecommerce_catalog_tables.php`
- `2026_02_15_000200_create_ecommerce_ordering_tables.php`
- `2026_02_15_000300_create_api_refresh_tokens_table.php`

## Middleware
- `ensure_role` => `App\Http\Middleware\EnsureUserHasRole`
- `verify_webhook_signature` => `App\Http\Middleware\VerifyWebhookSignature`
