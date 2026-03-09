<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;

class QueueWorkerRunner
{
    /**
     * @return array{exit_code: int, output: string}
     */
    public function runOnce(string $connection, string $queue): array
    {
        $exitCode = Artisan::call('queue:work', [
            'connection' => $connection,
            '--queue' => $queue,
            '--once' => true,
            '--sleep' => 0,
            '--tries' => 1,
        ]);

        return [
            'exit_code' => $exitCode,
            'output' => trim(Artisan::output()),
        ];
    }
}
