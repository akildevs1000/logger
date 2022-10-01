<?php

use App\Http\Controllers\LogController;
use Illuminate\Support\Facades\Route;

Route::apiResource("log", LogController::class);
Route::get("/", [LogController::class, "mdb_log"]);
Route::get("mdb_log", [LogController::class, "mdb_log"]);
Route::post("sync_from_mdb", [LogController::class, "sync_from_mdb"]);

Route::get("export", [LogController::class, "export"]);
