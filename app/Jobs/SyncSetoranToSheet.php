<?php

namespace App\Jobs;

use App\Services\GoogleSheetService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncSetoranToSheet implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = [10, 30, 60];

    protected $setoranId;

    public function __construct(int $setoranId)
    {
        $this->setoranId = $setoranId;
    }

    public function handle(GoogleSheetService $service)
    {
        $service->storeSetoran($this->setoranId);
    }

    public function failed(\Throwable $exception)
    {
        Log::error('SyncSetoranToSheet failed after retries', [
            'setoran_id' => $this->setoranId,
            'error' => $exception->getMessage(),
        ]);
    }
}
