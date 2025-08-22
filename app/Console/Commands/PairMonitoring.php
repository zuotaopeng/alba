<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Arbitrage\PairMonitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class PairMonitoring extends Command
{
    protected $signature = 'arb:monitor {--symbol=BTC/USDT} {--delay=}';
    protected $description = 'Arbitrage monitor for BTC/ETH/XRP';

    public function handle()
    {
        if ($d = (int)$this->option('delay')) sleep($d);
        $symbol = strtoupper($this->option('symbol'));
        [$base] = explode('/', $symbol, 2); $base = strtolower($base); // btc/eth/xrp

        $amountCol    = $base . '_amount';
        $thresholdCol = $base . '_threshold';
        $approvedCol  = 'approved_' . $base; // e.g. approved_btc = 'yes'

        $users = User::where($approvedCol, '=', 'yes')
            ->where($thresholdCol, '>', 0)
            ->where($amountCol, '>', 0)
            ->where(DB::raw("( {$base}_bybit_auto + {$base}_bitget_auto + {$base}_kucoin_auto + {$base}_mexc_auto )"), '>', 1)
            ->orderBy('id')
            ->offset((config('consts.server_id') - 1) * 10)
            ->limit(10)
            ->get();

        Log::info("arb monitor start base={$base} users=".count($users));

        $monitor = new PairMonitor(symbol: strtoupper($base).'/USDT', base: $base);

        foreach ($users as $u) {
            try {
                $monitor->runForUser($u, (float)$u->$amountCol, (float)$u->$thresholdCol);
            } catch (\Throwable $e) {
                Log::warning("{$base} user:{$u->id} error: ".$e->getMessage());
            }
        }

        Log::info("arb monitor end base={$base}");
        return CommandAlias::SUCCESS;
    }
}
