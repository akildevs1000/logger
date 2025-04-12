<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\ExternalConnection;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log as CustomLog;


class LogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $model = Log::query();

        $model->when($request->from && $request->to, function ($q) use ($request) {
            $q->whereBetween("log_date", [$request->from, $request->to]);
        });

        $model->when($request->user_id, function ($q) use ($request) {
            $q->where("user_id", $request->user_id);
        });

        return $model->paginate($request->per_page ?? 10);
    }

    public function exportCsv($data)
    {
        $fileName = 'logs.txt';

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            foreach ($data as $col) {
                fputcsv($file, [
                    $col['user_id'],
                    preg_replace("/\$\d+,\d+/", "", $col['log_date'] . "-" . $col['log_time']),
                    $col['type'],
                    $col['device_id']
                ], "	");
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function export(Request $request)
    {
        $model = Log::query();
        $model->whereBetween("log_date", [$request->from, $request->to]);
        $model->when((int) $request->user_id > 0, function ($q) use ($request) {
            $q->where("user_id", $request->user_id);
        });

        $data = $model->get(["user_id", "log_date", "log_time", "type", "device_id"]);

        return $this->exportCsv($data);
    }

    public function getLastSerialIdFromDb()
    {
        return Log::orderByDesc("id")->pluck("serial_no")[0] ?? 0;
    }

    public function sync_from_mdb()
    {
        //return $this->mdb_log();
        Log::truncate();
        return Log::insert($this->mdb_log());
    }


    public function mdb_log()
    {
        $external_connection = ExternalConnection::first(["path", "database_name"]);

        $path = $external_connection->path;
        $database_name = $external_connection->database_name;

        $connection_string = $path . "\\" . $database_name . ".mdb";

        $device_db_c_in = Device::pluck("c_in")->toArray();
        $device_db_c_out = Device::pluck("c_out")->toArray();


        try {
            $db = new \PDO("odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)}; DBQ=$connection_string;");

            $lastRecord = $this->getLastSerialIdFromDb();
            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $stmt = $db->prepare("select cr.PCode, cr.DataTime, cr.EquptID,cr.EquptName, cr.RecordSerialNumber, Equipment.EquptSN
            from CardRecord as cr INNER JOIN Equipment ON cr.EquptID=Equipment.EquptID ORDER BY RecordSerialNumber asc");
            $stmt->execute();

            $stmt->setFetchMode(\PDO::FETCH_ASSOC);
            $records = $stmt->fetchAll();

            $arr = [];

            foreach ($records as $record) {
                $datetime = $this->convert_date($record["DataTime"]);

                $arr[] = [
                    "serial_no" => $record["RecordSerialNumber"],
                    "user_id" => $record["PCode"],
                    "type" => $this->getType($record["EquptName"], $device_db_c_in, $device_db_c_out),
                    "device_id" => $record["EquptName"],
                    "device_model" => ($record["EquptSN"]),
                    "log_date" => $datetime[0] ?? "",
                    "log_time" => explode(".", $datetime[1])[0] ?? "",
                ];
            }
            return ($arr);
        } catch (\PDOException $e) {
            CustomLog::channel("custom")->error($e->getMessage());
            return $e->getMessage();
        }
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getType($device, $device_db_c_in, $device_db_c_out)
    {
        if (in_array($device, $device_db_c_in)) {
            return "C/In";
        } else if (in_array($device, $device_db_c_out)) {
            return "C/Out";
        }
        return "Unknown";
    }
    public function store(Request $request)
    {
        $file = base_path() . "/logs/logs.csv";

        if (!file_exists($file)) {
            return [
                'status' => false,
                'message' => 'No new data found',
            ];
        }

        $first = true;
        $data = [];
        $punch = "";

        if (($handle = fopen(base_path() . "/logs/logs.csv", 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {

                if ($first) {
                    $first = false;
                } else {
                    $datetime = explode(" ", $row[2]);

                    $arr = [
                        "user_id" => $row[0],
                        "log_date" => date("Y-m-d", strtotime($datetime[0])),
                        "log_time" => $datetime[1],
                        "device_id" => $row[1],
                        "device_model" => substr($row[2], 0, 7),
                        "serial_no" => $row[3],
                    ];

                    if ($row[1] == env("DEVICE_IN") || $row[1] == env("DEVICE_OUT")) {
                        if ($row[1] == env("DEVICE_IN")) {
                            $arr["type"] = "C/in";
                        } else if ($row[1] == env("DEVICE_OUT")) {
                            $arr["type"] = "C/out";
                        }

                        $data[] = $arr;
                    }
                }
            }
            fclose($handle);
        }
        try {
            $created = Log::insert($data);
            $created ? unlink(base_path() . "/logs/logs.csv") : 0;
            return $created ?? 0;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function convert_date($oaDate)
    {

        $days = floor($oaDate);
        $msecsFloat = ($oaDate - $days) * 86400000;
        $msecs = floor($msecsFloat);
        $hours = floor($msecs / 3600000);
        $msecs %= 3600000;
        $mins = floor($msecs / 60000);
        $msecs %= 60000;
        $secs = floor($msecs / 1000);
        $msecs %= 1000;

        $baseDate = new \DateTimeImmutable('1899-12-30 00:00:00');

        $date = $baseDate->add(new \DateInterval(sprintf('P%sDT%sH%sM%sS', $days, $hours, $mins, $secs)))->format('Y-m-d H:i:s');

        $final_date = (array) new \DateTime($date);

        $datetime = explode(" ", $final_date["date"]);

        return $datetime;
    }
}
