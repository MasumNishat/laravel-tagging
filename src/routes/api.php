<?php

use Illuminate\Support\Facades\Route;
use Masum\Tagging\Http\Controllers\TagConfigController;
use Masum\Tagging\Http\Controllers\TagController;

/*
|--------------------------------------------------------------------------
| Tagging API Routes
|--------------------------------------------------------------------------
|
| These routes are automatically registered when you enable API routes
| in the tagging configuration file.
|
*/

Route::prefix(config('tagging.routes.prefix', 'api/tag-configs'))
    ->middleware(config('tagging.routes.middleware', ['api']))
    ->group(function () {

        // Helper endpoints (must be before resource routes with parameters)
        Route::get('/meta/number-formats', [TagConfigController::class, 'numberFormats'])->name('tagging.meta.formats');
        Route::get('/meta/available-models', [TagConfigController::class, 'availableModels'])->name('tagging.meta.models');

        // Tag Configurations CRUD
        Route::get('/', [TagConfigController::class, 'index'])->name('tagging.configs.index');
        Route::post('/', [TagConfigController::class, 'store'])->name('tagging.configs.store');
        Route::get('/{tagConfig}', [TagConfigController::class, 'show'])->name('tagging.configs.show');
        Route::put('/{tagConfig}', [TagConfigController::class, 'update'])->name('tagging.configs.update');
        Route::delete('/{tagConfig}', [TagConfigController::class, 'destroy'])->name('tagging.configs.destroy');
    });

// Tags Management Routes
Route::prefix(config('tagging.routes.tags_prefix', 'api/tags'))
    ->middleware(config('tagging.routes.middleware', ['api']))
    ->group(function () {

        // Helper endpoints (must be before parameterized routes)
        Route::get('/meta/barcode-types', [TagController::class, 'barcodeTypes'])->name('tagging.tags.barcode-types');
        Route::post('/batch-barcodes', [TagController::class, 'batchBarcodes'])->name('tagging.tags.batch-barcodes');
        Route::get('/print/labels', [TagController::class, 'printLabels'])->name('tagging.tags.print-labels');

        // Bulk operations
        Route::post('/bulk/regenerate', [TagController::class, 'bulkRegenerate'])->name('tagging.tags.bulk-regenerate');
        Route::post('/bulk/delete', [TagController::class, 'bulkDelete'])->name('tagging.tags.bulk-delete');

        // Tag CRUD and listing
        Route::get('/', [TagController::class, 'index'])->name('tagging.tags.index');
        Route::get('/{tag}', [TagController::class, 'show'])->name('tagging.tags.show');

        // Barcode Generation for single tag
        Route::get('/{tag}/barcode', [TagController::class, 'barcode'])->name('tagging.tags.barcode');
    });