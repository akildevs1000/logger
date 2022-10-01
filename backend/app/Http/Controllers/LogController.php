<?php

namespace App\Http\Controllers;

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

    public function export(Request $request)
    {
        $model = Log::query();
        $model->whereBetween("log_date", [$request->from, $request->to]);
        $model->when($request->user_id, function ($q) use ($request) {
            $q->where("user_id", $request->user_id);
        });
        return $model->get(["user_id","log_date","log_time","type"]);
    }

    public function getLastSerialIdFromDb()
    {
        return Log::orderByDesc("id")->pluck("serial_no")[0] ?? 0;
    }

    public function sync_from_mdb()
    {
        return Log::insert($this->mdb_log());
    }


    public function mdb_log()
    {
        $MASQL_DB_PATH = env("MASQL_DB_PATH");

        try {
            $db = new \PDO("odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)}; DBQ=$MASQL_DB_PATH;");

            $lastRecord = $this->getLastSerialIdFromDb();
            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $stmt = $db->prepare("select cr.PCode, cr.DataTime, cr.EquptID,cr.EquptName, cr.RecordSerialNumber, Equipment.EquptSN
            from CardRecord as cr INNER JOIN Equipment ON cr.EquptID=Equipment.EquptID where RecordSerialNumber > $lastRecord and cr.PersonnelID > 0  ORDER BY RecordSerialNumber asc");
            $stmt->execute();

            $stmt->setFetchMode(\PDO::FETCH_ASSOC);
            $records = $stmt->fetchAll();

            $arr = [];

            foreach ($records as $record) {
                $datetime = $this->convert_date($record["DataTime"]);

                $arr[] = [
                    "serial_no" => $record["RecordSerialNumber"],
                    "user_id" => $record["PCode"],
                    "type" => $record["EquptID"] == 1 ? "C/in" : "C/out",
                    "device_id" => $record["EquptName"],
                    "device_model" => substr($record["EquptSN"], 0, 7),
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
