# Laravel API Starter Kit

## Stack
- Laravel 12
- Laravel Passport (OAuth2 access tokens)
- Spatie Laravel Permission
- MySQL

## Folder Structure
- `app/Http/Controllers/Api/V1`: Versioned API controllers.
- `app/Http/Requests/V1`: Form Request validation classes.
- `app/Http/Resources/V1`: API resource transformers.
- `app/Services`: Business logic layer (service pattern).
- `app/Repositories`: Data-access abstraction (repository pattern).
- `app/Policies`: Policy-based authorization rules.
- `app/Support/ApiResponse.php`: Standard API response helper.
- `database/migrations`: Schema and audit fields.
- `database/seeders`: Roles, permissions, and default admin bootstrap.
- `routes/api.php`: Protected and permission-aware API routing.

## API Response Format
All APIs return:

```json
{
  "message": "Logged in successfully",
  "data": {}
}
```

Validation errors:

```json
{
  "message": "Validation failed.",
  "data": null,
  "errors": {
    "email": ["The email field is required."]
  }
}
```

## Implemented Modules
- Authentication: login, logout (token revoke), `auth/me`, password change.
- Users: create, update, show, soft delete, assign roles.
- Roles: CRUD and sync permissions.
- Permissions: CRUD with policy authorization.
- Super Admin bypass via `Gate::before`.

## User Audit Fields
- `created_by`
- `updated_by`
- `deleted_by`
- `status` (`ACTIVE|INACTIVE|SUSPENDED`)
- `password_changed_at`
- `deleted_at` (soft delete)

## Sample Routes
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `POST /api/v1/auth/change-password`
- `GET /api/v1/auth/me`
- `GET /api/v1/users`
- `POST /api/v1/users`
- `PUT /api/v1/users/{user}`
- `DELETE /api/v1/users/{user}`
- `POST /api/v1/users/{user}/roles`
- `GET /api/v1/roles`
- `POST /api/v1/roles`
- `PUT /api/v1/roles/{role}/permissions`
- `GET /api/v1/permissions`
- `POST /api/v1/permissions`

## Bootstrap
1. `php artisan migrate`
2. `php artisan db:seed`
3. `php artisan permission:cache-reset`

Default seed data:
- `Super Admin` role
- baseline permission set
- `admin@example.com` user with `Super Admin` role

