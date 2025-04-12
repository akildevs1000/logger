<?php

namespace App\Http\Controllers;

use App\Models\ExternalConnection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function check_internal_database_connection()
    {

        try {
            DB::connection();

            $msg = "Internal database connected successfully";

            Log::channel("custom")->info($msg);

            return response()->json(["message" => $msg, "status" => true]);

        } catch (\Exception $e) {
            $msg = "Could not connect to internal database.  Please check your configuration";

            Log::channel("custom")->error($e->getMessage());

            return response()->json(["message" => $msg, "status" => false], 500);
        }
    }

    public function check_external_database_connection()
    {
        $external_connection = ExternalConnection::first(["path","database_name"]);

        $path = $external_connection->path;
        $database_name = $external_connection->database_name;

        $connection_string = $path . "\\" . $database_name . ".mdb";

        try {

            new \PDO("odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)}; DBQ=$connection_string;");

            $msg = "External database connected successfully";

            Log::channel("custom")->info($msg);

            return response()->json(["message" => $msg, "status" => true]);
        } catch (\PDOException $e) {
            return $e;
            $msg = "Could not connect to external database.  Please check your configuration";

            Log::channel("custom")->error($msg);

            return response()->json(["message" => $msg, "status" => false], 500);
        }
    }
}
