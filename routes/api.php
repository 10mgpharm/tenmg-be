<?php

use App\Http\Controllers\API\Credit\CustomerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['cors', 'json.response']], function () {

    // public routes

    // protected routes
    Route::middleware('auth:api')->group(function () {

        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        Route::prefix('/customers', function () {
            // List customers with pagination and filtering
            Route::get('/', [CustomerController::class, 'index'])->name('customers.index');

            // Create a new customer
            Route::post('/', [CustomerController::class, 'store'])->name('customers.store');

            // Get a specific customer's details
            Route::get('/{id}', [CustomerController::class, 'show'])->name('customers.show');

            // Update a customer's details
            Route::put('/{id}', [CustomerController::class, 'update'])->name('customers.update');

            // Soft delete a customer
            Route::delete('/{id}',
                [CustomerController::class, 'destroy']
            )->name('customers.destroy');

            // Enable or disable a customer
            Route::patch('/{id}', [CustomerController::class, 'toggleActive'])->name('customers.toggleActive');

            // Export customers to Excel
            Route::get('/export', [CustomerController::class, 'export'])->name('customers.export');

            // Import customers from Excel
            Route::post('/import', [CustomerController::class, 'import'])->name('customers.import');
        });
    });
});
