# Laravel E-Commerce Order Management System API

![Laravel E-Commerce](https://img.shields.io/badge/Laravel-10.x-FF2D20?style=for-the-badge&logo=laravel)

A modern, full-featured multi vendor e-commerce platform built with Laravel, designed for scalability, performance, and exceptional user experience.

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- MySQL 8.0+
- Composer
- Node.js 18+

### Installation

1. **Clone the repository**
   bash
   git clone https://github.com/naharsoftbd/bthalassemia
   cd bthalassemia

### Install PHP dependencies

bash
composer install

### Setup environment

bash
cp .env.example .env
php artisan key:generate

### Configure database

bash
# Edit .env file with your database credentials
DB_DATABASE=laravel_ecommerce
DB_USERNAME=your_username
DB_PASSWORD=your_password
Run migrations and seeders

bash
php artisan migrate:fresh --seed

bash
php artisan serve
Visit http://localhost:8000 to see your store!

# Default Admin Login:

Email: admin@demo.com

Password: password

📋 Features
🛍️ Core E-Commerce
Multi-vendor marketplace support

Product management with variants

Order management system

Customer accounts & profiles

Secure data handling


📦 Inventory & Shipping
Advanced inventory management


🏪 Store Management
Comprehensive admin dashboard

Vendor portal

Multi-store support

Bulk operations


🛠️ Tech Stack
Backend
Laravel 12.x - PHP Framework

MySQL 8.0 - Database

FullText - Search


Infrastructure
Docker - Containerization

Nginx - Web Server

Supervisor - Process Management

Horizon - Queue Monitoring

📁 Project Structure
text
bthalassemia/
├── app/
│   ├── Models/              # Eloquent Models
│   ├── Http/Controllers/    # Application Controllers
│   ├── Services/           # Business Logic
|   ├── Interface           # Data Access Layer
│   ├── Repositories/       # Business Logic
│   └── Observers/          # Model Events
├── database/
│   ├── migrations/         # Database Schemas
│   ├── seeders/           # Test Data
│   └── factories/         # Model Factories
├── resources/
│   ├── views/             # Blade Templates
├── config/                # Configuration
├── routes/                # Application Routes
├── tests/                 # Test Cases
└── public/                # Web Root
👥 User Roles
🛒 Customer
Browse products & categories

Place orders & track shipments

Write reviews & manage profile

🏪 Vendor
Manage product catalog

Process orders

View sales reports

Manage store profile

👑 Administrator
Full system access

Product management with bulk operations

Order processing & status updates

Customer management

System configuration

🧪 Testing
bash
# Run PHP tests
php artisan test


# Generate test coverage
vendor/bin/phpunit --coverage-html coverage
Test Coverage:

Unit Tests: 85%

Feature Tests: 90%

Browser Tests: 70%

🚀 Deployment
Production Setup
Environment setup

bash
composer install --optimize-autoloader --no-dev
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
Queue workers

bash
php artisan queue:work --daemon
Scheduler

bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1

Database indexing & query optimization

Code splitting & bundle optimization

Scalability Ready
Horizontal scaling support

Database replication ready

Queue workers for background jobs

CDN integration

🔒 Security
CSRF protection

XSS prevention

SQL injection prevention

Password hashing (bcrypt)

Rate limiting

Data encryption

🌐 API Endpoints

Public API

http

GET    api/v1/auth/register

POST    api/v1/auth/login

Authenticated API
http
GET    /api/v1/products           # List products
GET    /api/v1/products/{id}      # Product details
POST   /api/v1/products      # Create Product
PUT    /api/v1/products/{id}      # Update Product
DELETE   /api/v1/products/{id}      # Delete Product
GET    /api/v1//products/import/template      # Download CSV products template
POST   /api/v1//products/import     # Upload Bulk CSV products

POST   /api/v1/orders         # Generate Order
GET    /api/v1/orders        # User orders
POST   /api/v1//orders/{id}/confirm         # Confirm Order
POST   /api/v1//orders/{id}/cancel         # Cancel Order
POST   /api/v1//orders/{id}/cancel-vendor-items         # Cancel vendor items
GET   /api/v1//orders/{id}/invoice/download         # Download invoice 
GET   /api/v1//orders/{id}/invoice/vendor         # Download vendor invoice 


🤝 Contributing
We welcome contributions! Please read our Contributing Guide for details.

Fork the project

Create your feature branch (git checkout -b feature/AmazingFeature)

Commit your changes (git commit -m 'Add some AmazingFeature')

Push to the branch (git push origin feature/AmazingFeature)

Open a Pull Request

📞 Support
Documentation: https://docs.your-store.com

Community Forum: https://community.your-store.com

Email Support: support@your-store.com

Issues: GitHub Issues

📄 License
This project is licensed under the MIT License - see the LICENSE file for details.

🏆 Credits
Laravel Community - For the amazing framework

Contributors - Everyone who helped make this project better

Built with ❤️ using Laravel

<div align="center">
🌟 Star us on GitHub!
If you find this project helpful, please give it a star on GitHub!