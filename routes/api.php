<?php

use App\Http\Controllers\UploadController;

Route::middleware('auth:sanctum')->post('/upload/pictures', UploadController::class);