# SIKEU Laravel Payment

Laravel package untuk integrasi SIKEU Payment Gateway. Package ini hanya menyediakan service layer; controller, route, dan callback handler dibuat di aplikasi Laravel Anda.

## Requirements

- PHP 8.0+
- Laravel 9.x sampai 13.x
- Guzzle HTTP Client 7.x

## Instalasi

```bash
composer require sikeu/laravel-payment
php artisan vendor:publish --tag=sikeu-config
```

Tambahkan ke `.env`:

```env
SIKEU_API_BASE_URL=https://api.sikeu.id
SIKEU_API_KEY=your-api-key
SIKEU_SHARED_SECRET=your-shared-secret
SIKEU_SOURCE_APP=YOUR_APP_NAME
SIKEU_DEFAULT_PROVIDER=BRI
SIKEU_DEFAULT_QRIS_PROVIDER=BRI_QRIS
```

Lalu refresh config:

```bash
php artisan config:clear
```

## Alur Yang Direkomendasikan

1. Ambil daftar `service_category` lewat `getAvailableServices()`.
2. Ambil daftar `revenue_account_code` lewat `getRevenueAccountCodes()`.
3. Simpan atau tampilkan dua daftar itu di aplikasi Anda.
4. Saat membuat payment request, kirim hanya nilai yang berasal dari dua method tersebut.
5. Cek status dengan `checkPaymentRequest()` atau proses notifikasi callback dari SIKEU.

> `service_category` dan `revenue_account_code` tidak boleh di-hardcode berdasarkan asumsi. Dua field ini harus mengikuti master data dari SIKEU.

## Implementasi Minimal

Controller paling sederhana yang siap dipakai:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Sikeu\LaravelPayment\Exceptions\SikeuPaymentException;
use Sikeu\LaravelPayment\Services\SikeuPaymentService;

class PaymentController extends Controller
{
    public function __construct(
        private SikeuPaymentService $payment
    ) {}

    public function serviceCategories()
    {
        return response()->json($this->payment->getAvailableServices());
    }

    public function revenueAccountCodes()
    {
        return response()->json($this->payment->getRevenueAccountCodes());
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'service_category' => 'required|string',
            'customer_no' => 'required|string',
            'customer_name' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string',
            'revenue_account_code' => 'required|string',
            'provider' => 'nullable|string',
            'attributes' => 'nullable|array',
        ]);

        try {
            return response()->json($this->payment->createPaymentRequest($validated));
        } catch (SikeuPaymentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->getResponseData(),
            ], 400);
        }
    }

    public function show(string $paymentRequestId)
    {
        try {
            return response()->json($this->payment->checkPaymentRequest($paymentRequestId));
        } catch (SikeuPaymentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->getResponseData(),
            ], 400);
        }
    }
}
```

Route yang direkomendasikan:

```php
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')->group(function () {
    Route::get('/service-categories', [PaymentController::class, 'serviceCategories']);
    Route::get('/revenue-account-codes', [PaymentController::class, 'revenueAccountCodes']);
    Route::post('/', [PaymentController::class, 'create']);
    Route::get('/{paymentRequestId}', [PaymentController::class, 'show']);
});
```

Contoh request:

```json
{
    "service_category": "UKT",
    "customer_no": "2024000001",
    "customer_name": "John Doe",
    "amount": 5000000,
    "description": "Pembayaran UKT",
    "revenue_account_code": "411100"
}
```

Nilai `UKT` dan `411100` di atas hanya contoh. Ambil nilai validnya dari SIKEU melalui `getAvailableServices()` dan `getRevenueAccountCodes()`.

## QRIS

Jika ingin membuat payment QRIS, gunakan method khusus berikut:

```php
$result = $payment->createQrisPaymentRequest([
    'service_category' => 'UKT',
    'customer_no' => '2024000001',
    'customer_name' => 'John Doe',
    'amount' => 5000000,
    'description' => 'Pembayaran UKT',
    'revenue_account_code' => '411100',
    'provider' => 'BRI_QRIS', // opsional, default dari config
]);
```

Response QRIS akan mengandung data seperti `paymentRequestId`, `qrId`, `qrContent`, dan `expiryDate`/`qrExpiryDate`.

## Callback

Setelah pembayaran berhasil, SIKEU dapat mengirim callback ke aplikasi Anda. Rekomendasi implementasi:

- Sediakan endpoint publik, misalnya `POST /api/sikeu/callback`.
- Validasi header `X-Signature` menggunakan `SIKEU_SHARED_SECRET`.
- Terapkan idempotency check sebelum update status pembayaran.
- Kembalikan `200 OK` secepat mungkin, lalu proses berat lewat queue bila perlu.

Package ini tidak memaksa bentuk callback handler; implementasinya mengikuti kebutuhan aplikasi Anda.

## Method Yang Tersedia

### `createPaymentRequest(array $data): array`

Membuat payment request Virtual Account atau provider lain yang Anda kirim lewat field `provider`.

Field utama:

- `service_category`
- `customer_no`
- `customer_name`
- `amount`
- `description`
- `revenue_account_code`
- `provider` opsional
- `attributes` opsional

### `checkPaymentRequest(string $paymentRequestId): array`

Cek status payment request.

### `getPaymentRequest(string $paymentRequestId): array`

Ambil detail payment request.

### `cancelPaymentRequest(string $paymentRequestId): array`

Batalkan payment request.

### `getAvailableServices(): array`

Ambil daftar `service_category` yang valid dari SIKEU. Gunakan nilai `code` dari response sebagai input `service_category`.

### `getRevenueAccountCodes(): array`

Ambil daftar `revenue_account_code` yang valid dari SIKEU. Gunakan nilai `code` dari response sebagai input `revenue_account_code`.

### `createQrisPaymentRequest(array $data): array`

Membuat payment request QRIS. Jika `provider` tidak dikirim, package akan memakai `SIKEU_DEFAULT_QRIS_PROVIDER`.

### `checkQrisPaymentStatus(string $paymentRequestId): array`

Cek status pembayaran QRIS.

## Catatan Penting

- `service_category` wajib berasal dari `getAvailableServices()`.
- `revenue_account_code` wajib berasal dari `getRevenueAccountCodes()`.
- `amount` akan dikirim sebagai integer.
- Default provider diambil dari `config/sikeu.php`.
- Logging request/response aktif melalui config package.

## License

MIT
