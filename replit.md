# Prediction Bot - Laravel MLM Investment Platform

## Overview
This is a Laravel 11 application that provides an MLM (Multi-Level Marketing) investment platform with features including:
- User authentication with 2FA (Google Authenticator)
- Investment plans with tiered structures
- Referral system with commission tracking
- Admin dashboard for management
- KYC verification
- Support ticket system
- Push notifications

## Project Structure
- `app/` - Laravel application code (Controllers, Models, Services)
- `config/` - Configuration files
- `database/` - Migrations, seeders, and factories
- `public/` - Publicly accessible files and built assets
- `resources/` - Views, SCSS, JavaScript source files
- `routes/` - Route definitions

## Technology Stack
- **Backend**: PHP 8.2, Laravel 11
- **Database**: PostgreSQL (Replit native)
- **Frontend**: Blade templates, Bootstrap 5, SCSS, Vite
- **JS Libraries**: ApexCharts, Chart.js, Quill, SweetAlert2, Choices.js

## Environment Configuration
The application uses environment variables for configuration:
- `DATABASE_URL` - PostgreSQL connection string (automatically configured)
- `APP_KEY` - Laravel encryption key (configured)
- `DB_CONNECTION` - Set to 'pgsql'
- `PLISIO_SECRET_KEY` - Plisio API secret key for payment processing
- `PLISIO_CALLBACK_URL` - Webhook URL for Plisio payment notifications (set to your domain + `/api/webhooks/plisio`)

## Development Setup
- Server runs on port 5000
- Vite is configured to build frontend assets
- Assets are pre-built in `public/build/`

## Running the Application
The Laravel development server runs via the workflow:
```bash
php artisan serve --host=0.0.0.0 --port=5000
```

## Database Migrations
All migrations have been run and are PostgreSQL compatible. The project was originally designed for MySQL but migrations have been adapted for PostgreSQL.

## Payment Gateway
The application uses Plisio for cryptocurrency payment processing:
- **Configuration**: `config/payment.php`
- **Service**: `app/Services/PaymentGatewayService.php`
- **Webhook**: `POST /api/webhooks/plisio` - handles payment confirmations
- **Supported currencies**: BTC, ETH, LTC, DOGE, TRX, BNB, USDT (TRC20/ERC20/BEP20), and more

## Recent Changes
- **2025-12-25**: Replaced Coinments with Plisio payment gateway
  - Updated PaymentGatewayService for Plisio API
  - Implemented secure webhook handler with signature verification
  - Added database row locking for transaction safety
  - Uses POST requests with Api-Key header for API calls
- **2025-12-25**: Initial Replit setup
  - Configured PostgreSQL database connection
  - Fixed migrations for PostgreSQL compatibility (enum type handling)
  - Built Vite frontend assets
  - Configured workflow for port 5000
