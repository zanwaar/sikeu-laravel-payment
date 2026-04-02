# SIKEU Laravel Payment

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-9%2B%7C10%2B%7C11%2B%7C12%2B%7C13%2B-red.svg)](https://laravel.com)

Laravel package untuk integrasi SIKEU Payment Gateway yang mendukung multiple payment providers (BRI, BSI) dengan Virtual Account dan QRIS.

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

> **💡 Penting:**
> - List `service_category` yang valid harus diambil dari method `getAvailableServices()`
> - List `revenue_account_code` yang valid harus diambil dari method `getRevenueAccountCodes()`
>
> Kedua method ini mengembalikan daftar yang tersedia di sistem SIKEU. Lihat contoh penggunaan di bawah.

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

    public function getServiceCategories()
    {
        try {
            $result = $this->sikeuPayment->getAvailableServices();

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

    public function getRevenueAccountCodes()
    {
        try {
            $result = $this->sikeuPayment->getRevenueAccountCodes();

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
}
```

### 2. Daftarkan Routes

`routes/api.php`:

```php
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')->group(function () {
    Route::get('/service-categories', [PaymentController::class, 'getServiceCategories']);
    Route::get('/revenue-account-codes', [PaymentController::class, 'getRevenueAccountCodes']);
    Route::post('/', [PaymentController::class, 'create']);
    Route::get('/{paymentRequestId}', [PaymentController::class, 'show']);
    Route::delete('/{paymentRequestId}', [PaymentController::class, 'cancel']);
});
```

### 3. Test API

```bash
# Get available service categories (lakukan ini terlebih dahulu)
curl http://your-app.test/api/payments/service-categories

# Get revenue account codes
curl http://your-app.test/api/payments/revenue-account-codes

# Create payment
curl -X POST http://your-app.test/api/payments \
  -H "Content-Type: application/json" \
  -d '{
    "service_category": "UKT",
    "customer_no": "2024000001",
    "customer_name": "John Doe",
    "amount": 5000000,
    "description": "Tuition Fee",
    "revenue_account_code": "411100"
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

// Get available service categories
$services = $payment->getAvailableServices();
// Returns: ['status' => 'success', 'data' => [['code' => 'UKT', ...], ...]]

// Get revenue account codes
$accountCodes = $payment->getRevenueAccountCodes();
// Returns: ['status' => 'success', 'data' => [['code' => '411100', ...], ...]]

// Create payment request
$result = $payment->createPaymentRequest([
    'service_category' => 'UKT',
    'customer_no' => '2024000001',
    'customer_name' => 'John Doe',
    'amount' => 5000000,
    'description' => 'Tuition Fee',
    'revenue_account_code' => '411100',
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

### 7. Handle Callback / Webhook Notifikasi Pembayaran VA

Setelah mahasiswa membayar, SIKEU akan mengirim HTTP POST ke `SIKEU_CALLBACK_URL` yang Anda daftarkan di `.env`.

#### Alur Lengkap Virtual Account

```
Aplikasi Anda (SIAKAD/LMS)
  │
  │  1. createPaymentRequest()  ──►  SIKEU Payment Center
  │                                        │
  │  Response: virtualAccountNo ◄──────────┘
  │
  │  2. Tampilkan VA ke mahasiswa
  │     Mahasiswa bayar via ATM / BRImo / Teller
  │
  ▼
BRI memproses pembayaran
  │
  │  3. BRI → SIKEU: Payment Notification (otomatis)
  │     SIKEU update status → PAID
  │
  │  4. SIKEU → Aplikasi Anda: HTTP POST ke SIKEU_CALLBACK_URL
  ▼
Aplikasi Anda: handle callback, update status mahasiswa

  5. (Opsional) Polling status manual
     GET /api/payments/{paymentRequestId}
```

#### Tambahkan `.env`

```env
# URL yang akan dipanggil SIKEU setelah pembayaran berhasil
# Harus dapat diakses publik (bukan localhost)
SIKEU_CALLBACK_URL=https://siakad.unpatti.ac.id/api/sikeu/callback
```

#### Buat Middleware Validasi Signature HMAC

SIKEU mengirim header `X-Signature` (HMAC-SHA256) di setiap callback. Middleware ini memastikan request benar-benar dari SIKEU:

```php
<?php
// app/Http/Middleware/ValidateSikeuSignature.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateSikeuSignature
{
    public function handle(Request $request, Closure $next)
    {
        $timestamp    = $request->header('X-Timestamp');
        $signature    = $request->header('X-Signature');
        $sharedSecret = config('sikeu.auth.shared_secret');

        if (!$timestamp || !$signature) {
            return response()->json(['error' => 'Missing signature headers'], 401);
        }

        // Tolak jika timestamp lebih dari 5 menit
        if (abs(time() - strtotime($timestamp)) > 300) {
            return response()->json(['error' => 'Timestamp expired'], 401);
        }

        // Hitung HMAC-SHA256
        $body         = $request->getContent();
        $stringToSign = $timestamp . ':' . $body;
        $expected     = hash_hmac('sha256', $stringToSign, $sharedSecret);

        if (!hash_equals($expected, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
```

Daftarkan di `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // ...
    'sikeu.signature' => \App\Http\Middleware\ValidateSikeuSignature::class,
];
```

#### Buat Controller Callback

```php
<?php
// app/Http/Controllers/SikeuCallbackController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SikeuCallbackController extends Controller
{
    /**
     * Payload yang dikirim SIKEU ke endpoint callback Anda:
     *
     * {
     *   "paymentRequestId": "PAY-123456",
     *   "virtualAccountNo": "88881234567890001",
     *   "customerNo":       "2021-56-001",
     *   "amount":           5000000,
     *   "paidAmount":       5000000,
     *   "status":           "PAID",
     *   "paidAt":           "2026-04-02T14:30:00+07:00",
     *   "transactionId":    "TXN-BRI-20260402-001",
     *   "sourceApp":        "SIAKAD"
     * }
     */
    public function handle(Request $request)
    {
        $data = $request->json()->all();

        Log::info('SIKEU Callback diterima', $data);

        $paymentRequestId = $data['paymentRequestId'] ?? null;

        if (!$paymentRequestId) {
            return response()->json(['status' => 'ignored'], 200);
        }

        // Cari data di database lokal Anda
        $payment = \DB::table('payments')
            ->where('payment_request_id', $paymentRequestId)
            ->first();

        if (!$payment) {
            Log::warning('SIKEU Callback: paymentRequestId tidak ditemukan', $data);
            // Tetap return 200 agar SIKEU tidak retry terus-menerus
            return response()->json(['status' => 'ignored'], 200);
        }

        // Hindari double processing
        if ($payment->status === 'PAID') {
            return response()->json(['status' => 'already_processed'], 200);
        }

        // Update status di database lokal
        \DB::table('payments')
            ->where('payment_request_id', $paymentRequestId)
            ->update([
                'status'         => $data['status'],
                'paid_amount'    => $data['paidAmount'] ?? null,
                'transaction_id' => $data['transactionId'] ?? null,
                'paid_at'        => $data['paidAt'] ?? null,
                'updated_at'     => now(),
            ]);

        // Proses lanjutan setelah PAID
        if (($data['status'] ?? '') === 'PAID') {
            // Contoh: aktifkan akses, kirim notifikasi, dispatch job, dll
            // UpdateStatusAkademikJob::dispatch($data['customerNo'], $paymentRequestId);
            Log::info('Pembayaran berhasil', [
                'nim'              => $data['customerNo'],
                'paymentRequestId' => $paymentRequestId,
                'amount'           => $data['paidAmount'],
            ]);
        }

        // WAJIB response 200 dalam < 10 detik
        return response()->json(['status' => 'ok'], 200);
    }
}
```

#### Daftarkan Route Callback

```php
// routes/api.php

use App\Http\Controllers\SikeuCallbackController;

// Route callback dari SIKEU — tanpa auth, pakai signature validation
Route::post('/sikeu/callback', [SikeuCallbackController::class, 'handle'])
    ->middleware('sikeu.signature');
```

Exclude dari CSRF protection di `app/Http/Middleware/VerifyCsrfToken.php`:

```php
protected $except = [
    'api/sikeu/callback',
];
```

#### Status Lifecycle Pembayaran

| Status | Arti | Tindakan |
|--------|------|----------|
| `PENDING` | VA sudah dibuat, belum dibayar | Tampilkan nomor VA ke user |
| `PAID` | Pembayaran berhasil diterima | Aktifkan akses / update status akademik |
| `TIMEOUT` | Callback tidak masuk dalam 10 detik | Rekonsiliasi otomatis H+1 oleh SIKEU |
| `RECONCILED` | TIMEOUT → berhasil dicocokkan H+1 | Status akhir berhasil (via rekonsiliasi) |
| `TRULY_FAILED` | Tidak dapat direkonsiliasi > 7 hari | Hubungi admin SIKEU |
| `CANCELLED` | Dibatalkan manual | Buat payment request baru jika perlu |

#### Poin Penting Callback

> - **Response harus `200 OK` dalam < 10 detik.** Jika > 10 detik, SIKEU tandai sebagai TIMEOUT dan rekonsiliasi H+1.
> - **Selalu cek idempotency** — pastikan tidak proses double jika callback dikirim ulang.
> - **Jangan return error 4xx/5xx** untuk kasus data tidak ditemukan. Cukup return `200` dengan body `{"status": "ignored"}`.
> - **`SIKEU_CALLBACK_URL` harus dapat diakses dari internet** (bukan `localhost`). Gunakan tool seperti [ngrok](https://ngrok.com) saat development.

#### Test Callback Lokal (Development)

Gunakan ngrok untuk expose localhost:

```bash
ngrok http 8000
# Salin URL ngrok, contoh: https://abc123.ngrok.io
```

Set di `.env`:

```env
SIKEU_CALLBACK_URL=https://abc123.ngrok.io/api/sikeu/callback
```

Simulasi callback manual via curl:

```bash
TIMESTAMP=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
BODY='{"paymentRequestId":"PAY-123456","virtualAccountNo":"88881234567890001","customerNo":"2021-56-001","amount":5000000,"paidAmount":5000000,"status":"PAID","paidAt":"2026-04-02T14:30:00+07:00","transactionId":"TXN-BRI-TEST-001","sourceApp":"SIAKAD"}'
SECRET="your-shared-secret-here"
SIGNATURE=$(echo -n "${TIMESTAMP}:${BODY}" | openssl dgst -sha256 -hmac "${SECRET}" | awk '{print $2}')

curl -X POST https://your-app.test/api/sikeu/callback \
  -H "Content-Type: application/json" \
  -H "X-Timestamp: ${TIMESTAMP}" \
  -H "X-Signature: ${SIGNATURE}" \
  -d "${BODY}"
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

# Get available service categories
>>> $services = $payment->getAvailableServices();
>>> dd($services);

# Get revenue account codes
>>> $accountCodes = $payment->getRevenueAccountCodes();
>>> dd($accountCodes);

# Create payment request
>>> $result = $payment->createPaymentRequest([
    'service_category' => 'UKT',
    'customer_no' => '001',
    'customer_name' => 'Test',
    'amount' => 50000,
    'description' => 'Test payment',
    'revenue_account_code' => '411100',
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
| `service_category` | string | Yes | Kategori layanan. **List kategori yang tersedia bisa didapat dari method `getAvailableServices()`** |
| `customer_no` | string | Yes | Nomor customer/mahasiswa |
| `customer_name` | string | Yes | Nama customer/mahasiswa |
| `amount` | int/float | Yes | Jumlah pembayaran |
| `description` | string | Yes | Deskripsi pembayaran |
| `revenue_account_code` | string | Yes | Kode akun pendapatan. **List kode akun yang tersedia bisa didapat dari method `getRevenueAccountCodes()`** |
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

Mendapatkan daftar service categories yang tersedia dari sistem SIKEU. **Method ini harus digunakan untuk mendapatkan list valid `service_category` yang dapat digunakan saat membuat payment request.**

**Example:**

```php
$services = $payment->getAvailableServices();

// Returns:
// [
//     'status' => 'success',
//     'data' => [
//         ['code' => 'UKT', 'name' => 'Uang Kuliah Tunggal', 'description' => '...'],
//         ['code' => 'SPP', 'name' => 'Sumbangan Pembinaan Pendidikan', 'description' => '...'],
//         ['code' => 'WISUDA', 'name' => 'Biaya Wisuda', 'description' => '...'],
//         // ... dll
//     ]
// ]

// Gunakan 'code' sebagai value untuk 'service_category'
foreach ($services['data'] as $service) {
    echo $service['code']; // UKT, SPP, WISUDA, etc.
}
```

**Response Structure:**

```php
[
    'status' => 'success',
    'data' => [
        [
            'code' => 'UKT',           // Gunakan ini untuk service_category
            'name' => 'Uang Kuliah Tunggal',
            'description' => 'Pembayaran UKT mahasiswa',
            'isActive' => true
        ],
        // ... kategori lainnya
    ]
]
```

#### `getRevenueAccountCodes(): array`

Mendapatkan daftar kode akun pendapatan (revenue account codes) yang tersedia dari sistem SIKEU. **Method ini harus digunakan untuk mendapatkan list valid `revenue_account_code` yang dapat digunakan saat membuat payment request.**

**Example:**

```php
$accountCodes = $payment->getRevenueAccountCodes();

// Returns:
// [
//     'status' => 'success',
//     'data' => [
//         ['code' => '411100', 'name' => 'Pendapatan UKT', 'description' => '...'],
//         ['code' => '411200', 'name' => 'Pendapatan SPP', 'description' => '...'],
//         ['code' => '411300', 'name' => 'Pendapatan Wisuda', 'description' => '...'],
//         // ... dll
//     ]
// ]

// Gunakan 'code' sebagai value untuk 'revenue_account_code'
foreach ($accountCodes['data'] as $account) {
    echo $account['code']; // 411100, 411200, 411300, etc.
}
```

**Response Structure:**

```php
[
    'status' => 'success',
    'data' => [
        [
            'code' => '411100',        // Gunakan ini untuk revenue_account_code
            'name' => 'Pendapatan UKT',
            'description' => 'Akun pendapatan untuk UKT mahasiswa',
            'isActive' => true
        ],
        // ... kode akun lainnya
    ]
]
```

## ⚠️ Important Notes

- Pastikan kolom `status` di database bertipe `STRING/VARCHAR` (bukan ENUM) karena status dari gateway beragam (`PENDING`, `PAID`, `TIMEOUT`, `RECONCILED`, `TRULY_FAILED`, `CANCELLED`)
- Credentials (`API Key` & `Shared Secret`) didapat dari admin SIKEU — jangan hardcode di kode
- Gunakan queue untuk payment creation di production agar tidak blocking request
- `SIKEU_CALLBACK_URL` harus dapat diakses dari internet, bukan `localhost`
- Response callback **wajib `200 OK` dalam < 10 detik** — proses berat taruh di queue/job
- Selalu implementasi **idempotency check** sebelum proses callback (cek apakah sudah `PAID`)
- Middleware `ValidateSikeuSignature` **wajib dipasang** di route callback untuk keamanan

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
