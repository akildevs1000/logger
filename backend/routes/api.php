<?php

use App\Http\Controllers\DeviceController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\ExternalConnectionController;
use Illuminate\Support\Facades\Route;

Route::apiResource("log", LogController::class);

Route::apiResource("devices", DeviceController::class);

Route::get("/", function () {
    return "Services are ready to be used.";
});

Route::get("cleanLogs", [LogController::class, "cleanLogs"]);

Route::get("check_internal_database_connection", [LogController::class, "check_internal_database_connection"]);
Route::get("check_external_database_connection", [LogController::class, "check_external_database_connection"]);
Route::get("mdb_log", [LogController::class, "mdb_log"]);
Route::post("sync_from_mdb", [LogController::class, "sync_from_mdb"]);

Route::post("setExternalConnection", [ExternalConnectionController::class, "setExternalConnection"]);
Route::get("getExternalConnection", [ExternalConnectionController::class, "getExternalConnection"]);

Route::get("export", [LogController::class, "export"]);
Route::get("exportCsv", [LogController::class, "exportCsv"]);
