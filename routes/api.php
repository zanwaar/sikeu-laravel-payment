<?php
/*
|--------------------------------------------------------------------------
| SIKEU Payment API Routes
|--------------------------------------------------------------------------
|
| Routes for SIKEU Payment integration
|
*/

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/payments')->group(function () {
    // Create payment request
    Route::post('/', [PaymentController::class, 'create'])->name('payments.create');

    // Get payment status
    Route::get('/{paymentRequestId}', [PaymentController::class, 'show'])->name('payments.show');

    // Cancel payment request
    Route::delete('/{paymentRequestId}', [PaymentController::class, 'cancel'])->name('payments.cancel');
});
