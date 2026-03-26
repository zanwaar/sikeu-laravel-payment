# SIKEU Laravel Payment

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10%2B%7C11%2B%7C12%2B-red.svg)](https://laravel.com)

Laravel package untuk integrasi SIKEU Payment Gateway yang mendukung multiple payment providers (BRI, BNI, BSI) dengan Virtual Account dan QRIS.

## 🎯 Features

- ✅ **Multi Provider Support**: BRI, BNI, BSI
- ✅ **Payment Methods**: Virtual Account & QRIS
- ✅ **Auto-discovery**: Laravel package auto-discovery
- ✅ **Service Provider**: Seamless Laravel integration
- ✅ **Request Validation**: Built-in form request validation
- ✅ **Exception Handling**: Custom exception handling
- ✅ **Queue Support**: Async payment processing with jobs
- ✅ **Testing**: Feature tests included
- ✅ **Publishable Assets**: Config, controllers, services, and more

## 📋 Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x, or 12.x
- Guzzle HTTP Client 7.x

## 🚀 Installation

Install via Composer:

```bash
composer require sikeu/laravel-payment
```

### Publish Configuration

Publish config file:

```bash
php artisan vendor:publish --tag=sikeu-config
```

### Publish Optional Assets

```bash
# Publish controllers
php artisan vendor:publish --tag=sikeu-controllers

# Publish form requests
php artisan vendor:publish --tag=sikeu-requests

# Publish exceptions
php artisan vendor:publish --tag=sikeu-exceptions

# Publish jobs
php artisan vendor:publish --tag=sikeu-jobs
```

### Environment Configuration

Add to your `.env`:

```env
SIKEU_API_BASE_URL=https://api.sikeu.id
SIKEU_API_KEY=your-api-key-here
SIKEU_SHARED_SECRET=your-shared-secret-here
SIKEU_SOURCE_APP=YOUR_APP_NAME
SIKEU_DEFAULT_PROVIDER=BRI
```

Clear config cache:

```bash
php artisan config:clear
```

## 📖 Usage

### Basic Usage

```php
use Sikeu\LaravelPayment\Services\SikeuPaymentService;

$payment = app(SikeuPaymentService::class);

// Create payment request
$result = $payment->createPaymentRequest([
    'service_category' => 'UKT',
    'customer_no' => '2024000001',
    'customer_name' => 'John Doe',
    'amount' => 5000000,
    'description' => 'Tuition Fee',
]);

// Get payment info
$paymentId = $result['data']['paymentRequestId'];
$vaNumber = $result['data']['virtualAccountNo'];

// Check payment status
$status = $payment->checkPaymentRequest($paymentId);

// Cancel payment
$cancel = $payment->cancelPaymentRequest($paymentId);
```

### Using Published Controller

After publishing controllers, use in routes:

```php
use App\Http\Controllers\PaymentController;

Route::post('/payments', [PaymentController::class, 'create']);
```

Make request:

```bash
curl -X POST http://your-app.test/api/payments \
  -H "Content-Type: application/json" \
  -d '{
    "service_category": "UKT",
    "customer_no": "2024000001",
    "customer_name": "John Doe",
    "amount": 5000000,
    "description": "Tuition Fee"
  }'
```

### Using Jobs (Queue)

```php
use App\Jobs\CreatePaymentJob;

CreatePaymentJob::dispatch([
    'service_category' => 'SPP',
    'customer_no' => '2024000001',
    'customer_name' => 'Jane Doe',
    'amount' => 3000000,
    'description' => 'Monthly Fee',
]);
```

## 🔧 Configuration

Edit `config/sikeu.php`:

```php
return [
    'api' => [
        'base_url' => env('SIKEU_API_BASE_URL', 'http://localhost:8080'),
        'timeout' => env('SIKEU_API_TIMEOUT', 30),
    ],
    'auth' => [
        'api_key' => env('SIKEU_API_KEY'),
        'shared_secret' => env('SIKEU_SHARED_SECRET'),
        'source_app' => env('SIKEU_SOURCE_APP', 'LARAVEL_APP'),
    ],
    'payment' => [
        'default_provider' => env('SIKEU_DEFAULT_PROVIDER', 'BRI'),
    ],
];
```

## 🧪 Testing

Run tests:

```bash
php artisan test --filter SikeuPayment
```

Manual testing with Tinker:

```bash
php artisan tinker

>>> $payment = app(\Sikeu\LaravelPayment\Services\SikeuPaymentService::class);
>>> $result = $payment->createPaymentRequest([
    'service_category' => 'TEST',
    'customer_no' => '001',
    'customer_name' => 'Test',
    'amount' => 50000,
    'description' => 'Test payment',
]);
>>> dd($result);
```

## 📦 Package Structure

```
.
├── config/
│   └── sikeu.php                  # Configuration file
├── src/
│   ├── Services/
│   │   ├── SikeuPaymentService.php
│   │   └── PaymentResponse.php
│   ├── Exceptions/
│   │   └── SikeuPaymentException.php
│   └── SikeuPaymentServiceProvider.php
├── app/                           # Publishable assets
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── PaymentController.php
│   │   └── Requests/
│   │       └── CreatePaymentRequest.php
│   ├── Jobs/
│   │   └── CreatePaymentJob.php
│   └── Exceptions/
│       └── SikeuPaymentException.php
├── routes/
│   └── api.php
├── tests/
│   └── Feature/
│       ├── SikeuPaymentServiceTest.php
│       └── PaymentControllerTest.php
└── composer.json
```

## 🔍 Available Methods

### SikeuPaymentService

```php
// Create payment request
createPaymentRequest(array $data): array

// Check payment status
checkPaymentRequest(string $paymentRequestId): array

// Cancel payment
cancelPaymentRequest(string $paymentRequestId): array
```

## ⚠️ Important Notes

- Pastikan kolom `status` di database bertipe `STRING/VARCHAR` (bukan ENUM) karena status dari gateway beragam
- Credentials (API Key & Shared Secret) didapat dari SIKEU admin
- Gunakan queue untuk payment creation di production
- Implementasikan webhook handler untuk real-time status update

## 🐛 Troubleshooting

### Class not found

```bash
composer dump-autoload
```

### Config not found

```bash
php artisan config:clear
php artisan config:cache
```

### Invalid signature error

Verify credentials:

```bash
php artisan tinker
>>> config('sikeu.auth.api_key')
>>> config('sikeu.auth.shared_secret')
```

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📞 Support

For issues and questions, please visit [SIKEU Documentation](https://docs.sikeu.id)
