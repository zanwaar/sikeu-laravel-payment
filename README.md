# SIKEU Laravel Payment

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-9%2B%7C10%2B%7C11%2B%7C12%2B%7C13%2B-red.svg)](https://laravel.com)

Laravel package untuk integrasi SIKEU Payment Gateway yang mendukung multiple payment providers (BRI, BNI, BSI) dengan Virtual Account dan QRIS.

## 🎯 Features

- ✅ **Multi Provider Support**: BRI, BNI, BSI
- ✅ **Payment Methods**: Virtual Account & QRIS
- ✅ **Auto-discovery**: Laravel package auto-discovery
- ✅ **Service Layer**: Clean service class untuk payment operations
- ✅ **Exception Handling**: Custom exception handling
- ✅ **Easy Integration**: Inject service ke controller/job Anda
- ✅ **Flexible**: Implementasi controller dan routes sesuai kebutuhan

## 📋 Requirements

- PHP 8.0 or higher
- Laravel 9.x, 10.x, 11.x, 12.x, or 13.x
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

Package ini menyediakan service layer untuk SIKEU Payment Gateway. Anda perlu membuat controller dan routes sendiri sesuai kebutuhan aplikasi.

### 1. Buat Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Sikeu\LaravelPayment\Services\SikeuPaymentService;
use Sikeu\LaravelPayment\Exceptions\SikeuPaymentException;

class PaymentController extends Controller
{
    public function __construct(
        private SikeuPaymentService $sikeuPayment
    ) {}

    public function create(Request $request)
    {
        $validated = $request->validate([
            'service_category' => 'required|string',
            'customer_no' => 'required|string',
            'customer_name' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string',
        ]);

        try {
            $result = $this->sikeuPayment->createPaymentRequest($validated);

            return response()->json([
                'success' => true,
                'data' => $result['data'],
            ]);
        } catch (SikeuPaymentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function show($paymentRequestId)
    {
        try {
            $result = $this->sikeuPayment->checkPaymentRequest($paymentRequestId);

            return response()->json([
                'success' => true,
                'data' => $result['data'],
            ]);
        } catch (SikeuPaymentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function cancel($paymentRequestId)
    {
        try {
            $result = $this->sikeuPayment->cancelPaymentRequest($paymentRequestId);

            return response()->json([
                'success' => true,
                'message' => 'Payment cancelled successfully',
            ]);
        } catch (SikeuPaymentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
```

### 2. Daftarkan Routes

`routes/api.php`:

```php
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')->group(function () {
    Route::post('/', [PaymentController::class, 'create']);
    Route::get('/{paymentRequestId}', [PaymentController::class, 'show']);
    Route::delete('/{paymentRequestId}', [PaymentController::class, 'cancel']);
});
```

### 3. Test API

```bash
# Create payment
curl -X POST http://your-app.test/api/payments \
  -H "Content-Type: application/json" \
  -d '{
    "service_category": "UKT",
    "customer_no": "2024000001",
    "customer_name": "John Doe",
    "amount": 5000000,
    "description": "Tuition Fee"
  }'

# Check status
curl http://your-app.test/api/payments/PAY-123456

# Cancel payment
curl -X DELETE http://your-app.test/api/payments/PAY-123456
```

### 4. Menggunakan Job (Queue)

Buat job untuk async processing:

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Sikeu\LaravelPayment\Services\SikeuPaymentService;
use Sikeu\LaravelPayment\Exceptions\SikeuPaymentException;

class CreatePaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        private array $paymentData
    ) {}

    public function handle(SikeuPaymentService $sikeuPayment)
    {
        try {
            $result = $sikeuPayment->createPaymentRequest($this->paymentData);

            // Save to database or notify user
            \Log::info('Payment created', $result);
        } catch (SikeuPaymentException $e) {
            \Log::error('Payment failed: ' . $e->getMessage());
            $this->fail($e);
        }
    }
}
```

Dispatch job:

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

### 5. Direct Service Usage

Gunakan service langsung tanpa controller:

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

### 6. Menggunakan Attributes (Metadata Tambahan)

Package ini mendukung penambahan metadata custom melalui field `attributes`. Sangat berguna untuk menyimpan informasi tambahan seperti prodi, tahun, semester, NIM, dll.

#### Contoh dengan Attributes:

```php
use Sikeu\LaravelPayment\Services\SikeuPaymentService;

$payment = app(SikeuPaymentService::class);

$result = $payment->createPaymentRequest([
    'service_category' => 'UKT',
    'customer_no' => '2024000001',
    'customer_name' => 'John Doe',
    'amount' => 5000000,
    'description' => 'Pembayaran UKT Semester Genap 2024',
    'revenue_account_code' => '411100',
    'provider' => 'BRI', // optional, default dari config

    // Additional metadata
    'attributes' => [
        'nim' => '2024000001',
        'prodi' => 'Teknik Informatika',
        'fakultas' => 'Fakultas Teknik',
        'tahun_akademik' => '2024/2025',
        'semester' => 'Genap',
        'angkatan' => '2024',
        'jenis_pembayaran' => 'UKT',
        'periode' => '2024-2',
        // Tambahkan metadata lain sesuai kebutuhan
    ]
]);
```

#### Attributes di Controller:

```php
public function create(Request $request)
{
    $validated = $request->validate([
        'service_category' => 'required|string',
        'customer_no' => 'required|string',
        'customer_name' => 'required|string',
        'amount' => 'required|numeric|min:1',
        'description' => 'required|string',
        'revenue_account_code' => 'required|string',

        // Validation untuk attributes
        'nim' => 'nullable|string',
        'prodi' => 'nullable|string',
        'fakultas' => 'nullable|string',
        'tahun_akademik' => 'nullable|string',
        'semester' => 'nullable|string',
        'angkatan' => 'nullable|string',
    ]);

    try {
        // Build attributes dari request
        $attributes = [];
        if ($request->has('nim')) $attributes['nim'] = $request->nim;
        if ($request->has('prodi')) $attributes['prodi'] = $request->prodi;
        if ($request->has('fakultas')) $attributes['fakultas'] = $request->fakultas;
        if ($request->has('tahun_akademik')) $attributes['tahun_akademik'] = $request->tahun_akademik;
        if ($request->has('semester')) $attributes['semester'] = $request->semester;
        if ($request->has('angkatan')) $attributes['angkatan'] = $request->angkatan;

        $paymentData = [
            'service_category' => $validated['service_category'],
            'customer_no' => $validated['customer_no'],
            'customer_name' => $validated['customer_name'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'revenue_account_code' => $validated['revenue_account_code'],
        ];

        // Add attributes jika ada
        if (!empty($attributes)) {
            $paymentData['attributes'] = $attributes;
        }

        $result = $this->sikeuPayment->createPaymentRequest($paymentData);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    } catch (SikeuPaymentException $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 400);
    }
}
```

#### Contoh Request dengan Attributes:

```bash
curl -X POST http://your-app.test/api/payments \
  -H "Content-Type: application/json" \
  -d '{
    "service_category": "UKT",
    "customer_no": "2024000001",
    "customer_name": "John Doe",
    "amount": 5000000,
    "description": "Pembayaran UKT Semester Genap 2024",
    "revenue_account_code": "411100",
    "nim": "2024000001",
    "prodi": "Teknik Informatika",
    "fakultas": "Fakultas Teknik",
    "tahun_akademik": "2024/2025",
    "semester": "Genap",
    "angkatan": "2024"
  }'
```

**Catatan**: Attributes bersifat opsional dan flexible. Anda bisa menambahkan field apapun sesuai kebutuhan aplikasi Anda.

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
sikeu/laravel-payment/
├── config/
│   └── sikeu.php                      # Configuration file
├── src/
│   ├── Services/
│   │   ├── SikeuPaymentService.php    # Main service class
│   │   └── PaymentResponse.php        # Response wrapper
│   ├── Exceptions/
│   │   └── SikeuPaymentException.php  # Custom exception
│   └── SikeuPaymentServiceProvider.php # Service provider
├── tests/
│   └── Feature/
│       └── SikeuPaymentServiceTest.php
├── composer.json
├── README.md
└── LICENSE
```

**Package ini hanya menyediakan service layer.** Controller, routes, dan job dibuat sendiri oleh user di Laravel project mereka.

## 🔍 Available Methods

### SikeuPaymentService

#### `createPaymentRequest(array $data): array`

Membuat payment request baru.

**Parameters:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `service_category` | string | Yes | Kategori layanan (e.g., UKT, SPP, dll) |
| `customer_no` | string | Yes | Nomor customer/mahasiswa |
| `customer_name` | string | Yes | Nama customer/mahasiswa |
| `amount` | int/float | Yes | Jumlah pembayaran |
| `description` | string | Yes | Deskripsi pembayaran |
| `revenue_account_code` | string | Yes | Kode akun pendapatan |
| `provider` | string | No | Provider gateway (BRI, BNI, BSI). Default dari config |
| `attributes` | array | No | Metadata tambahan (prodi, tahun, dll) |

**Example:**

```php
$payment->createPaymentRequest([
    'service_category' => 'UKT',
    'customer_no' => '2024000001',
    'customer_name' => 'John Doe',
    'amount' => 5000000,
    'description' => 'Pembayaran UKT',
    'revenue_account_code' => '411100',
    'provider' => 'BRI',
    'attributes' => [
        'nim' => '2024000001',
        'prodi' => 'Teknik Informatika',
        'tahun_akademik' => '2024/2025'
    ]
]);
```

**Returns:**

```php
[
    'status' => 'success',
    'message' => 'Payment request created successfully',
    'data' => [
        'paymentRequestId' => 'PAY-123456',
        'virtualAccountNo' => '8808012345678901',
        'amount' => 5000000,
        'status' => 'PENDING',
        // ... other fields
    ]
]
```

#### `checkPaymentRequest(string $paymentRequestId): array`

Mengecek status pembayaran.

**Parameters:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `paymentRequestId` | string | Yes | ID payment request |

**Example:**

```php
$payment->checkPaymentRequest('PAY-123456');
```

**Returns:**

```php
[
    'status' => 'success',
    'data' => [
        'paymentRequestId' => 'PAY-123456',
        'status' => 'PAID',
        'paidAt' => '2024-03-26T10:00:00Z',
        // ... other fields
    ]
]
```

#### `cancelPaymentRequest(string $paymentRequestId): array`

Membatalkan payment request.

**Parameters:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `paymentRequestId` | string | Yes | ID payment request |

**Example:**

```php
$payment->cancelPaymentRequest('PAY-123456');
```

**Returns:**

```php
[
    'status' => 'success',
    'message' => 'Payment request cancelled successfully'
]
```

#### `getPaymentRequest(string $paymentRequestId): array`

Mendapatkan detail payment request.

**Parameters:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `paymentRequestId` | string | Yes | ID payment request |

**Example:**

```php
$payment->getPaymentRequest('PAY-123456');
```

#### `getAvailableServices(): array`

Mendapatkan list service categories yang tersedia.

**Example:**

```php
$payment->getAvailableServices();
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
