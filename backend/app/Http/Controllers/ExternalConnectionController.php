<?php

namespace App\Http\Controllers;

use App\Models\ExternalConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExternalConnectionController extends Controller
{
    public function setExternalConnection(Request $request)
    {
        try {
            ExternalConnection::where("id", 1)->update([
                "path" => $request->path,
                "database_name" => $request->database_name
            ]);
        } catch (\Exception $e) {
            Log::channel("custom")->error($e->getMessage());
        }

        Log::channel("custom")->info("Connection setting udpated");
        return $this->getExternalConnection();
    }
    public function getExternalConnection()
    {
        return ExternalConnection::first();
    }
}
