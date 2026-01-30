<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CsvUploadController;

Route::get('/', function () {
    return view('welcome');
});



Route::get('/', [CsvUploadController::class, 'showForm']);
Route::post('/upload-csv', [CsvUploadController::class, 'upload'])->name('upload.csv');
