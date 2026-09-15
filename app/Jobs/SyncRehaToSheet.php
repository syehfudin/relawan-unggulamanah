<?php

namespace App\Jobs;

use App\Services\GoogleSheetService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncRehaToSheet implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = [10, 30, 60];

    protected $rehaId;

    public function __construct(int $rehaId)
    {
        $this->rehaId = $rehaId;
    }

    public function handle(GoogleSheetService $service)
    {
        $service->storeReha($this->rehaId);
    }

    public function failed(\Throwable $exception)
    {
        Log::error('SyncRehaToSheet failed after retries', [
            'reha_id' => $this->rehaId,
            'error' => $exception->getMessage(),
        ]);
    }
}
