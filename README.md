# 🛒 E-Commerce Backend API (Laravel)

Production-ready E-commerce Backend API built with Laravel.  
This API is designed to work with a React frontend and supports authentication, product management, cart system, order processing, role-based access control, and admin management.

---

# 🔗 Repository

GitHub: https://github.com/MinKhantMaw/backend

---

# 🚀 Tech Stack

- Framework: Laravel 10+
- Language: PHP 8.2+
- Database: MySQL
- Authentication: Laravel Passport (Token-Based)
- Authorization: Role-Based (Admin / Customer)
- API Type: RESTful API

---

---

# 🧩 Features

## 👤 Authentication
- User Registration
- Login
- Logout
- Token-Based Authentication
- Role-based Access (Admin / Customer)

## 🛍 Product Management
- Product CRUD (Admin)
- Category Management
- Product Filtering
- Pagination
- Search

## 🛒 Cart System
- Add to Cart
- Update Quantity
- Remove Item
- View Cart

## 📦 Order System
- Checkout Process
- Order Creation
- Inventory Deduction
- Order History
- Admin Order Management
- Order Status Update


---

# ⚙️ Installation Guide

## 1️⃣ Clone Repository

```bash
git clone https://github.com/MinKhantMaw/backend.git
cd backend

composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed


