<?php

use App\Http\Controllers\LogController;
use Illuminate\Support\Facades\Route;


use App\Models\Log;


Route::apiResource("log", LogController::class);
Route::get("range", [LogController::class, "range"]);
Route::get("export", [LogController::class, "export"]);



Route::get('/', function () {
    return getData();
});

Route::get('/clone_db', function () {
    return clone_db();
});
Route::post('/sync', function () {
    return Log::insert(getData());
});

function clone_db()
{
    if (copy(env("MASQL_DB_PATH"), env("APP_PATH"))) {
        $str = "DB clone has been created from this location ( " .  env("MASQL_DB_PATH") . " ) to this location ( " .  env("APP_PATH") . " )";
        return $str;
    }
    return "Failed";
}

function getLastSerialIdFromDb()
{
    return Log::orderByDesc("id")->pluck("serial_no")[0] ?? 0;
}

function getData()
{
    if (env("APP_ENV") == "production") {
        clone_db();
    }


    $APP_PATH = env("APP_PATH");
    $db = new PDO("odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)}; DBQ=$APP_PATH;");


    if (!$db) {
        echo "Can't connect";
        exit;
    }
    try {
        $lastRecord = getLastSerialIdFromDb();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $db->prepare("select cr.PersonnelID, cr.DataTime, cr.EquptID, cr.RecordSerialNumber, Equipment.EquptSN
            from CardRecord as cr INNER JOIN Equipment ON cr.EquptID=Equipment.EquptID where RecordSerialNumber > $lastRecord and cr.PersonnelID > 0  ORDER BY RecordSerialNumber asc");
        $stmt->execute();

        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $records = $stmt->fetchAll();

        $arr = [];

        foreach ($records as $record) {
            $datetime = convert_date($record["DataTime"]);

            $arr[] = [
                "serial_no" => $record["RecordSerialNumber"],
                "user_id" => $record["PersonnelID"],
                "type" => $record["EquptID"] == 1 ? "C/in" : "C/out",
                "device_id" => $record["EquptSN"],
                "device_model" => substr($record["EquptSN"], 0, 7),
                "log_date" => $datetime[0] ?? "",
                "log_time" => explode(".", $datetime[1])[0] ?? "",
            ];
        }
        return ($arr);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

function convert_date($oaDate)
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

    $baseDate = new DateTimeImmutable('1899-12-30 00:00:00');

    $date = $baseDate->add(new DateInterval(sprintf('P%sDT%sH%sM%sS', $days, $hours, $mins, $secs)))->format('Y-m-d H:i:s');

    $final_date = (array) new DateTime($date);

    $datetime = explode(" ", $final_date["date"]);

    return $datetime;
}
