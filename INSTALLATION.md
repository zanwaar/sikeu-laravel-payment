# Instalasi SIKEU Payment Laravel Package

## Demo App (Sudah Terinstall)

Demo app ini sudah memiliki package SIKEU Payment yang terinstall dan terkonfigurasi. Package sudah siap digunakan dengan konfigurasi berikut:

-   **Package**: `sikeu/laravel-payment` (versi 1.0.0)
-   **Repository**: Local path `./sikeu`
-   **Config**: Sudah tersedia di `config/sikeu.php`
-   **Environment**: Sudah dikonfigurasi di `.env`

### Konfigurasi yang Sudah Ada:

```env
# SIKEU Payment API Configuration
SIKEU_API_BASE_URL=http://localhost:8080
SIKEU_API_KEY=siakad_api_key_12345
SIKEU_SHARED_SECRET=siakad_shared_secret_67890
SIKEU_SOURCE_APP=SIAKAD
SIKEU_DEFAULT_PROVIDER=BRI
SIKEU_LOGGING_ENABLED=true
SIKEU_LOG_CHANNEL=stack
```

---

## Instalasi Fresh (Proyek Baru)

### Cara 1: Install via Composer (Local Package)

### 1. Tambahkan repository ke `composer.json` proyek Laravel Anda:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./sikeu"
        }
    ]
}
```

### 2. Install package:

```bash
composer require sikeu/laravel-payment
```

### 3. Publish config dan file-file:

```bash
# Publish config
php artisan vendor:publish --tag=sikeu-config

# Publish controllers (optional)
php artisan vendor:publish --tag=sikeu-controllers

# Publish requests (optional)
php artisan vendor:publish --tag=sikeu-requests

# Publish exceptions (optional)
php artisan vendor:publish --tag=sikeu-exceptions

# Publish jobs (optional)
php artisan vendor:publish --tag=sikeu-jobs

# Atau publish semua sekaligus
php artisan vendor:publish --provider="Sikeu\LaravelPayment\SikeuPaymentServiceProvider"
```

### 4. Update `.env`:

```env
SIKEU_API_BASE_URL=http://localhost:8080
SIKEU_API_KEY=your-api-key
SIKEU_SHARED_SECRET=your-shared-secret
SIKEU_SOURCE_APP=YOUR_APP_NAME
SIKEU_DEFAULT_PROVIDER=BRI
SIKEU_LOGGING_ENABLED=true
SIKEU_LOG_CHANNEL=stack
```

### 5. Clear cache:

```bash
php artisan config:clear
php artisan route:clear
```

---

## Cara 2: Install via GitHub/GitLab

### 1. Push package ke repository Git Anda

### 2. Tambahkan ke `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/username/sikeu-laravel-payment"
        }
    ],
    "require": {
        "sikeu/laravel-payment": "dev-main"
    }
}
```

### 3. Install:

```bash
composer require sikeu/laravel-payment
```

---

## Cara 3: Install via Packagist (Public)

### 1. Daftar package di https://packagist.org

### 2. Install langsung:

```bash
composer require sikeu/laravel-payment
```

---

## Quick Start (Demo App)

Demo app sudah siap digunakan. Untuk menjalankan:

```bash
# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env  # (jika belum ada)
php artisan key:generate

# Run development server
composer run dev
# atau
php artisan serve
```

Akses demo di: `http://localhost:8000`

---

## Usage

```php
use Sikeu\LaravelPayment\Services\SikeuPaymentService;

$payment = app(SikeuPaymentService::class);

$result = $payment->createPaymentRequest([
    'service_category' => 'UKT',
    'customer_no' => '2024000001',
    'customer_name' => 'John Doe',
    'amount' => 5000000,
    'description' => 'Tuition Fee',
]);
```

### Contoh Penggunaan di Controller:

```php
// app/Http/Controllers/PaymentController.php
public function createPayment(CreatePaymentRequest $request)
{
    $paymentService = app(SikeuPaymentService::class);

    try {
        $result = $paymentService->createPaymentRequest($request->validated());
        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
```

---

## Testing

```bash
# Run all tests
php artisan test

# Run specific SIKEU tests
php artisan test --filter Sikeu

# Run with coverage
php artisan test --coverage
```

---

## Development Scripts

Package ini sudah dilengkapi dengan development scripts di `composer.json`:

```bash
# Setup lengkap (install + migrate + build)
composer run setup

# Development mode (server + queue + logs + vite)
composer run dev

# Run tests
composer run test
```
