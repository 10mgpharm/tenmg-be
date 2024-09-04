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

        // List customers with pagination and filtering
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');

        // Create a new customer
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');

        // Get a specific customer's details
        Route::get('/customers/{id}', [CustomerController::class, 'show'])->name('customers.show');

        // Update a customer's details
        Route::put('/customers/{id}', [CustomerController::class, 'update'])->name('customers.update');

        // Soft delete a customer
        Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');

        // Enable or disable a customer
        Route::patch('/customers/{id}', [CustomerController::class, 'toggleActive'])->name('customers.toggleActive');

        // Export customers to Excel
        Route::get('/customers/export', [CustomerController::class, 'export'])->name('customers.export');

        // Import customers from Excel
        Route::post('/customers/import', [CustomerController::class, 'import'])->name('customers.import');
    });
});
