<?php

namespace App\Jobs;

use App\Services\GoogleSheetService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncTransaksiToSheet implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = [10, 30, 60];

    protected $transaksiId;
    protected $donasi;

    public function __construct(int $transaksiId, array $donasi)
    {
        $this->transaksiId = $transaksiId;
        $this->donasi = $donasi;
    }

    public function handle(GoogleSheetService $service)
    {
        $service->storeTransaksi($this->transaksiId, $this->donasi);
    }

    public function failed(\Throwable $exception)
    {
        Log::error('SyncTransaksiToSheet failed after retries', [
            'transaksi_id' => $this->transaksiId,
            'error' => $exception->getMessage(),
        ]);
    }
}
