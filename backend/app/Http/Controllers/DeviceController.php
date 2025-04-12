<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DeviceController extends Controller
{
    public function index()
    {
        return Device::get(["c_in","c_out"]);
    }

    public function store(Request $request)
    {
        Schema::dropIfExists('devices');

        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string("c_in");
            $table->string("c_out");
            $table->timestamps();
        });

        try {
            if (count($request->all()) > 0) {
                Device::insert($request->all());
                return response()->json(["message" => "Devices inserted successfully", "status" => true]);
            }
            return response()->json(["message" => "Cannot insert empty list", "status" => false]);

        } catch (\Exception $e) {
            Log::channel("custom")->error($e->getMessage());
            return response()->json(["message" => $e->getMessage(), "status" => false]);
        }
    }
}
