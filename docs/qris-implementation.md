# Panduan Implementasi QRIS — sikeu/laravel-payment

**QRIS (Quick Response Code Indonesian Standard)** adalah standar kode QR nasional Indonesia yang ditetapkan Bank Indonesia. Package ini mendukung QRIS MPM Dynamic (Merchant Presented Mode) melalui gateway BRI dan BSI, terintegrasi dengan sistem SIKEU.

---

## Daftar Isi

1. [Persyaratan](#persyaratan)
2. [Konfigurasi](#konfigurasi)
3. [Flow QRIS](#flow-qris)
4. [Implementasi Dasar](#implementasi-dasar)
5. [Render QR ke Gambar](#render-qr-ke-gambar)
6. [Cek Status Pembayaran](#cek-status-pembayaran)
7. [Polling Status (JavaScript)](#polling-status-javascript)
8. [Contoh Controller Lengkap](#contoh-controller-lengkap)
9. [Contoh Blade View](#contoh-blade-view)
10. [Webhook (Opsional)](#webhook-opsional)
11. [Troubleshooting](#troubleshooting)

---

## Persyaratan

- Package `sikeu/laravel-payment` sudah terinstall
- SIKEU API Key dan Shared Secret sudah dikonfigurasi
- Provider QRIS di SIKEU (BRI_QRIS atau BSI_QRIS) sudah aktif di sistem SIKEU

---

## Konfigurasi

### 1. Environment Variables

Tambahkan ke file `.env`:

```env
SIKEU_API_BASE_URL=https://api.sikeu.id
SIKEU_API_KEY=your-api-key
SIKEU_SHARED_SECRET=your-shared-secret
SIKEU_SOURCE_APP=SIAKAD

# Provider QRIS default: BRI_QRIS atau BSI_QRIS
SIKEU_DEFAULT_QRIS_PROVIDER=BRI_QRIS
```

### 2. Publish dan Edit Config

```bash
php artisan vendor:publish --tag=sikeu-config
```

Edit `config/sikeu.php`:

```php
'payment' => [
    'default_provider'      => env('SIKEU_DEFAULT_PROVIDER', 'BRI'),
    'default_qris_provider' => env('SIKEU_DEFAULT_QRIS_PROVIDER', 'BRI_QRIS'),
    'default_currency'      => 'IDR',
],
```

---

## Flow QRIS

```
[Aplikasi Anda]  ──POST createQrisPaymentRequest──►  [SIKEU API]
                                                           │
                                                           ▼
                                                    [BRI/BSI QRIS API]
                                                    Generate QR Code
                                                           │
[Aplikasi Anda]  ◄──── qrContent (string QRIS) ──────────┘
      │
      │  Tampilkan QR ke mahasiswa
      ▼
[Mahasiswa] ──scan QR── [Mobile Banking]
                               │
                               │  Bayar
                               ▼
                        [BRI/BSI memproses]
                               │
                               │  Webhook notify ke SIKEU
                               ▼
                        [SIKEU memperbarui status]
                               │
[Aplikasi Anda]  ◄── polling checkQrisPaymentStatus ──────┘
```

---

## Implementasi Dasar

### Buat QRIS Payment Request

```php
use Sikeu\LaravelPayment\Services\SikeuPaymentService;
use Sikeu\LaravelPayment\Exceptions\SikeuPaymentException;

$payment = app(SikeuPaymentService::class);

try {
    $result = $payment->createQrisPaymentRequest([
        'service_category'    => 'UKT',
        'customer_no'         => '2024000001',
        'customer_name'       => 'Budi Santoso',
        'amount'              => 5000000,
        'description'         => 'Pembayaran UKT Semester Genap 2024/2025',
        'revenue_account_code'=> '411100',
        // Opsional: tentukan provider secara eksplisit
        // 'provider'         => 'BRI_QRIS',  // atau 'BSI_QRIS'
        // Opsional: metadata tambahan
        'attributes' => [
            'nim'            => '2024000001',
            'prodi'          => 'Teknik Informatika',
            'tahun_akademik' => '2024/2025',
            'semester'       => 'Genap',
        ],
    ]);

    $paymentRequestId = $result['data']['paymentRequestId']; // simpan di DB
    $qrContent        = $result['data']['qrContent'];        // string QR untuk ditampilkan
    $qrId             = $result['data']['qrId'];             // ID QR dari gateway
    $expiryDate       = $result['data']['qrExpiryDate'] ?? $result['data']['expiryDate'] ?? null;

} catch (SikeuPaymentException $e) {
    // Tangani error
    logger()->error('QRIS payment failed', ['error' => $e->getMessage()]);
}
```

**Struktur response `$result`:**

```php
[
    'status'  => 'success',
    'message' => 'Payment request created successfully',
    'data'    => [
        'paymentRequestId' => 'PAY-20240331-001',
        'qrContent'        => '00020101021226...',   // string QRIS — render jadi gambar
        'qrId'             => 'QR20240331093045001',
        'qrExpiryDate'     => '2024-03-31T10:30:00+07:00',
        'amount'           => 5000000,
        'status'           => 'PENDING',
        // ...
    ]
]
```

---

## Render QR ke Gambar

`qrContent` adalah string QRIS standar. Untuk menampilkannya sebagai gambar, gunakan salah satu library berikut.

### Opsi A: endroid/qr-code (direkomendasikan)

**Install:**
```bash
composer require endroid/qr-code
```

**Render ke PNG (controller):**
```php
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

public function showQris(string $paymentRequestId)
{
    $payment = app(SikeuPaymentService::class);
    $result  = $payment->checkQrisPaymentStatus($paymentRequestId);
    $qrContent = $result['data']['qrContent'];

    $qrCode = QrCode::create($qrContent)
        ->setSize(300)
        ->setMargin(10);

    $writer   = new PngWriter();
    $rendered = $writer->write($qrCode);

    return response($rendered->getString(), 200)
        ->header('Content-Type', $rendered->getMimeType());
}
```

**Tampilkan di Blade sebagai base64:**
```php
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$qrCode   = QrCode::create($qrContent)->setSize(300)->setMargin(10);
$rendered = (new PngWriter())->write($qrCode);
$qrBase64 = $rendered->getDataUri(); // "data:image/png;base64,..."
```

```html
<img src="{{ $qrBase64 }}" alt="QR Code Pembayaran" width="300">
```

---

### Opsi B: simplesoftwareio/simple-qrcode (untuk Blade)

**Install:**
```bash
composer require simplesoftwareio/simple-qrcode
```

**Di Blade:**
```blade
{!! QrCode::size(250)->generate($qrContent) !!}
```

---

### Opsi C: Tanpa library (img tag via Google Charts API — dev only)

> Hanya untuk development/testing. Jangan kirim data sensitif ke API eksternal di production.

```blade
<img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl={{ urlencode($qrContent) }}"
     alt="QR Code">
```

---

## Cek Status Pembayaran

```php
$payment = app(SikeuPaymentService::class);
$result  = $payment->checkQrisPaymentStatus('PAY-20240331-001');

$status = $result['data']['status'];
// Status yang mungkin: PENDING | PAID | EXPIRED | CANCELLED | FAILED

if ($status === 'PAID') {
    // Lanjut proses: aktifkan akses mahasiswa, kirim notifikasi, dsb.
}
```

---

## Polling Status (JavaScript)

Tampilkan QR dan polling status setiap beberapa detik:

```javascript
const paymentRequestId = '{{ $paymentRequestId }}';
let pollInterval;

function checkStatus() {
    fetch(`/api/payments/qris/${paymentRequestId}/status`)
        .then(res => res.json())
        .then(data => {
            const status = data.data?.status;

            if (status === 'PAID') {
                clearInterval(pollInterval);
                document.getElementById('qr-container').innerHTML =
                    '<p class="text-green-600 font-bold">Pembayaran Berhasil!</p>';
                // Redirect atau perbarui UI
                setTimeout(() => window.location.href = '/payment/success', 2000);

            } else if (['EXPIRED', 'CANCELLED', 'FAILED'].includes(status)) {
                clearInterval(pollInterval);
                document.getElementById('qr-container').innerHTML =
                    '<p class="text-red-600">QR kedaluwarsa. Silakan buat pembayaran baru.</p>';
            }
        })
        .catch(err => console.error('Polling error:', err));
}

// Mulai polling setiap 3 detik
pollInterval = setInterval(checkStatus, 3000);

// Hentikan polling setelah 10 menit (QR biasanya expire)
setTimeout(() => clearInterval(pollInterval), 10 * 60 * 1000);
```

---

## Contoh Controller Lengkap

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Sikeu\LaravelPayment\Services\SikeuPaymentService;
use Sikeu\LaravelPayment\Exceptions\SikeuPaymentException;

class QrisPaymentController extends Controller
{
    public function __construct(
        private SikeuPaymentService $sikeuPayment
    ) {}

    /**
     * Tampilkan halaman QRIS dan buat QR baru.
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'service_category'     => 'required|string',
            'customer_no'          => 'required|string',
            'customer_name'        => 'required|string',
            'amount'               => 'required|integer|min:1000',
            'description'          => 'required|string',
            'revenue_account_code' => 'required|string',
        ]);

        try {
            $result = $this->sikeuPayment->createQrisPaymentRequest($validated);
            $data   = $result['data'];

            return view('payments.qris', [
                'paymentRequestId' => $data['paymentRequestId'],
                'qrContent'        => $data['qrContent'],
                'amount'           => $data['amount'],
                'expiryDate'       => $data['qrExpiryDate'] ?? $data['expiryDate'] ?? null,
            ]);

        } catch (SikeuPaymentException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }
    }

    /**
     * API endpoint untuk polling status (dipanggil dari JavaScript).
     */
    public function status(string $paymentRequestId)
    {
        try {
            $result = $this->sikeuPayment->checkQrisPaymentStatus($paymentRequestId);

            return response()->json([
                'success' => true,
                'data'    => $result['data'],
            ]);

        } catch (SikeuPaymentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Halaman konfirmasi setelah pembayaran berhasil.
     */
    public function success(string $paymentRequestId)
    {
        try {
            $result = $this->sikeuPayment->checkQrisPaymentStatus($paymentRequestId);
            $data   = $result['data'];

            if ($data['status'] !== 'PAID') {
                return redirect()->route('payments.qris.show', $paymentRequestId)
                    ->withErrors(['payment' => 'Pembayaran belum dikonfirmasi.']);
            }

            return view('payments.success', ['payment' => $data]);

        } catch (SikeuPaymentException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }
    }
}
```

### Routes

`routes/web.php`:

```php
use App\Http\Controllers\QrisPaymentController;

Route::middleware(['auth'])->group(function () {
    Route::post('/payments/qris',                    [QrisPaymentController::class, 'create'])->name('payments.qris.create');
    Route::get('/payments/qris/{id}/success',        [QrisPaymentController::class, 'success'])->name('payments.qris.success');
});

// API route untuk polling (bisa di api.php juga)
Route::get('/api/payments/qris/{id}/status',         [QrisPaymentController::class, 'status']);
```

---

## Contoh Blade View

`resources/views/payments/qris.blade.php`:

```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pembayaran QRIS</title>
</head>
<body>
<div style="text-align:center; max-width:400px; margin:40px auto;">

    <h2>Scan QR untuk Membayar</h2>

    <p>Jumlah: <strong>Rp {{ number_format($amount, 0, ',', '.') }}</strong></p>

    @if($expiryDate)
        <p>QR berlaku hingga: <strong>{{ \Carbon\Carbon::parse($expiryDate)->format('H:i, d M Y') }}</strong></p>
    @endif

    {{-- Render QR dengan endroid/qr-code --}}
    @php
        use Endroid\QrCode\QrCode;
        use Endroid\QrCode\Writer\PngWriter;
        $rendered = (new PngWriter())->write(QrCode::create($qrContent)->setSize(280)->setMargin(10));
        $qrBase64 = $rendered->getDataUri();
    @endphp
    <img src="{{ $qrBase64 }}" alt="QR Code" width="280">

    {{-- Atau dengan simple-qrcode: --}}
    {{-- {!! QrCode::size(280)->generate($qrContent) !!} --}}

    <div id="status-message" style="margin-top:20px; font-size:16px;">
        <span id="status-text">Menunggu pembayaran...</span>
    </div>

    <p style="font-size:12px; color:#888; margin-top:10px;">
        Buka aplikasi mobile banking Anda dan scan kode QR di atas.
    </p>
</div>

<script>
const paymentRequestId = '{{ $paymentRequestId }}';
let pollInterval;

function checkStatus() {
    fetch(`/api/payments/qris/${paymentRequestId}/status`)
        .then(res => res.json())
        .then(data => {
            const status = data.data?.status;
            if (status === 'PAID') {
                clearInterval(pollInterval);
                document.getElementById('status-text').textContent = 'Pembayaran Berhasil!';
                document.getElementById('status-text').style.color = 'green';
                setTimeout(() => {
                    window.location.href = `/payments/qris/${paymentRequestId}/success`;
                }, 2000);
            } else if (['EXPIRED', 'CANCELLED', 'FAILED'].includes(status)) {
                clearInterval(pollInterval);
                document.getElementById('status-text').textContent = 'QR kedaluwarsa. Silakan buat pembayaran baru.';
                document.getElementById('status-text').style.color = 'red';
            }
        });
}

pollInterval = setInterval(checkStatus, 3000);
setTimeout(() => clearInterval(pollInterval), 10 * 60 * 1000);
</script>
</body>
</html>
```

---

## Webhook (Opsional)

SIKEU memproses notifikasi webhook dari BRI/BSI secara internal. Status pembayaran di SIKEU akan otomatis diperbarui saat pembayaran berhasil. Aplikasi Anda cukup melakukan **polling** via `checkQrisPaymentStatus()`.

Jika Anda ingin menerima notifikasi real-time dari SIKEU ke aplikasi Anda (misalnya untuk memperbarui database aplikasi tanpa polling), hubungi administrator SIKEU untuk mendaftarkan webhook URL aplikasi Anda.

---

## Troubleshooting

| Masalah | Kemungkinan Penyebab | Solusi |
|---------|---------------------|--------|
| `qrContent` null atau kosong | Provider QRIS belum aktif di SIKEU | Hubungi admin SIKEU, pastikan `BRI_QRIS` atau `BSI_QRIS` aktif |
| Status tetap `PENDING` | Webhook BRI/BSI ke SIKEU tidak masuk | Periksa log SIKEU; bisa juga polling langsung ke SIKEU berulang |
| `SikeuPaymentException: System not configured` | `SIKEU_API_KEY` atau `SIKEU_SHARED_SECRET` kosong | Periksa `.env` dan jalankan `php artisan config:clear` |
| QR tidak bisa di-scan | `qrContent` tidak valid atau library QR render salah | Pastikan `qrContent` di-pass langsung tanpa encoding tambahan |
| `provider` tidak dikenali | Nilai provider bukan `BRI_QRIS` / `BSI_QRIS` | Periksa nilai `SIKEU_DEFAULT_QRIS_PROVIDER` di `.env` |

### Debug cepat dengan Tinker

```bash
php artisan tinker

# Test koneksi dan buat QRIS
>>> $p = app(\Sikeu\LaravelPayment\Services\SikeuPaymentService::class);
>>> $r = $p->createQrisPaymentRequest([
    'service_category'     => 'UKT',
    'customer_no'          => '001',
    'customer_name'        => 'Test',
    'amount'               => 50000,
    'description'          => 'Test QRIS',
    'revenue_account_code' => '411100',
]);
>>> dd($r['data']['qrContent']); // harus berisi string QRIS panjang

# Cek status
>>> $s = $p->checkQrisPaymentStatus($r['data']['paymentRequestId']);
>>> dd($s['data']['status']); // PENDING / PAID / EXPIRED
```
