<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Http\Request;

class LogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Log::paginate($request->per_page ?? 10);
    }

    public function range(Request $request)
    {
        $model = Log::query();
        $model->whereBetween("log_date", [$request->from, $request->to]);
        $model->when($request->user_id, function ($q) use ($request) {
            $q->where("user_id", $request->user_id);
        });

        return $model->paginate($request->per_page ?? 10);
    }

    public function export(Request $request)
    {
        $model = Log::query();
        $model->whereBetween("log_date", [$request->from, $request->to]);
        $model->orWhere("user_id");
        return $model->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $file = base_path() . "/logs/OXSAI_timedata_DX.csv";

        if (!file_exists($file)) {
            return [
                'status' => false,
                'message' => 'No new data found',
            ];
        }

        $first = true;
        $data = [];

        if (($handle = fopen(base_path() . "/logs/OXSAI_timedata_DX.csv", 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {

                if ($first) {
                    $first = false;
                } else {
                    $datetime = explode(" ", $row[1]);
                    $data[] = [
                        "user_id" => $row[0],
                        "log_date" => date("Y-m-d", strtotime($datetime[0])),
                        "log_time" => $datetime[1],
                        "device_id" => $row[2],
                        "device_model" => substr($row[2], 0, 7),
                        "serial_no" => $row[3],
                        "type" => $row[2] == "OX-8662021010065" ? "C/in" : "C/out"
                    ];
                }
            }
            fclose($handle);
        }
        try {
            $created = Log::insert($data);
            // $created ? unlink(base_path() . "/logs/OXSAI_timedata_DX.csv") : 0;
            return $created ?? 0;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}