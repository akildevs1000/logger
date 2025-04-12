<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class AllBackgroundService extends Command
{
    protected $signature = 'all_background_services';
    protected $description = 'Run php artisan serve, schedule:work, queue:work, and frontend server together with logs';

    public function handle()
    {
        $this->info('Starting Laravel server, scheduler, queue worker, and frontend server...');

        // Laravel services
        $serve = new Process(['php', 'artisan', 'serve']);
        $schedule = new Process(['php', 'artisan', 'schedule:work']);
        $queue = new Process(['php', 'artisan', 'queue:work']);

        // Start all processes
        $serve->start();
        $schedule->start();
        $queue->start();

        while (
            $serve->isRunning() ||
            $schedule->isRunning() ||
            $queue->isRunning()
        ) {
            // Laravel serve output
            if ($serve->isRunning()) {
                $output = $serve->getIncrementalOutput();
                $error = $serve->getIncrementalErrorOutput();
                if (!empty($output)) $this->line("<fg=green>[serve]</> " . $output);
                if (!empty($error)) $this->error("[serve-error] " . $error);
            }

            // Scheduler output
            if ($schedule->isRunning()) {
                $output = $schedule->getIncrementalOutput();
                $error = $schedule->getIncrementalErrorOutput();
                if (!empty($output)) $this->line("<fg=cyan>[schedule]</> " . $output);
                if (!empty($error)) $this->error("[schedule-error] " . $error);
            }

            // Queue worker output
            if ($queue->isRunning()) {
                $output = $queue->getIncrementalOutput();
                $error = $queue->getIncrementalErrorOutput();
                if (!empty($output)) $this->line("<fg=yellow>[queue]</> " . $output);
                if (!empty($error)) $this->error("[queue-error] " . $error);
            }

            usleep(100000); // 0.1 second
        }

        $this->info('All processes have stopped.');
        return Command::SUCCESS;
    }
}
