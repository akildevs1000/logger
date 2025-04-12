<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\Log;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log as LaravelLogger;
use Illuminate\Support\Facades\Storage;

class CSVTODATABASE extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:csv_to_database {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Record logs from a CSV file to the database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->argument("date") ?? date("d-m-Y");

        $result = $this->getDataFromCsv($date);

        if (isset($result['error'])) {
            LaravelLogger::error($result['error']);
            $this->error($result['error']);
            return 1;
        }

        if (empty($result['data'])) {
            $this->info("No data found in the CSV file.");
            return 1;
        }

        // echo json_encode($result['data'], JSON_PRETTY_PRINT);

        Log::insert($result['data']);
        Storage::put("logs-count-" . $result['date'] . ".txt", $result['totalLines']);
        LaravelLogger::info("Successfully imported CSV data");
        $this->info("CSV data has been successfully inserted into the database.");
        return 0;
    }

    public function getDataFromCsv($date)
    {
        $csvPath = "app/logs-$date.csv"; // The path to the file relative to the "Storage" folder

        $fullPath = storage_path($csvPath);

        if (!file_exists($fullPath)) {
            return ["error" => 'File doest not exist.'];
        }

        $file = fopen($fullPath, 'r');

        $data = file($fullPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (!count($data)) {
            return ["error" => true, "message" => 'File is empty.'];
        }

        $previoulyAddedLineNumbers = Storage::get("logs-count-$date.txt") ?? 0;

        // return $this->getMeta("Sync Attenance Logs", $previoulyAddedLineNumbers . "\n");

        $totalLines = count($data);

        $currentLength = 0;

        if ($previoulyAddedLineNumbers == $totalLines) {
            return ["error" => 'No new data found.'];
        } else if ($previoulyAddedLineNumbers > 0 && $totalLines > 0) {
            $currentLength = $previoulyAddedLineNumbers;
        }

        fclose($file);

        $data = array_slice($data, $currentLength);

        $records = [];

        $deviceDbCIn = Device::pluck('c_in')->toArray();
        $deviceDbCOut = Device::pluck('c_out')->toArray();

        foreach ($data as $row) {
            $columns = explode(',', $row);

            $log_date = isset($columns[2]) ? date('Y-m-d', strtotime($columns[2])) : null;
            $log_time = isset($columns[2]) ? date('H:i:s', strtotime($columns[2])) : null;

            $records[] = [
                "user_id" => $columns[0] ?? null,
                "device_id" => $columns[1] ?? null,
                "serial_no" => $columns[3] ?? null,
                "type" => $this->getType($columns[1] ?? null, $deviceDbCIn, $deviceDbCOut),
                "device_model" => $columns[1] ?? null,
                'log_date'      => $log_date,
                'log_time'      => $log_time,
            ];
        }

        return [
            "date" => $date,
            "totalLines" => $totalLines,
            "data" => $records
        ];
    }

    /**
     * Determines the log type based on equipment name.
     */
    private function getType(string $equipmentName, array $inList, array $outList): string
    {
        if (in_array($equipmentName, $inList)) {
            return "C/In";
        } else if (in_array($equipmentName, $outList)) {
            return "C/Out";
        }
        return "Unknown";
    }
}
