<?php

namespace App\Console\Commands;

use App\Models\Rate;
use App\Services\Exchanges\Credentials;
use App\Services\Exchanges\ExchangeFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class RateMonitoring extends Command
{
    protected $signature = 'rate:batch {--delay=0 : Schedulerからの遅延秒}';
    protected $description = '全取引所から BTC/USDT, ETH/USDT, XRP/USDT の Ticker(ask/bid) を取得し rates に保存（1回実行）';

    /** 取得するシンボル（固定） */
    private array $symbols   = ['BTC/USDT', 'ETH/USDT', 'XRP/USDT'];
    /** 取得する取引所（固定） */
    private array $exchanges = ['bybit','bitget','kucoin','mexc'];

    public function handle()
    {
        // Scheduler から渡された秒だけ待機（3秒刻み用）
        $delay = (int)$this->option('delay');
        if ($delay > 0) {
            sleep($delay);
        }

        // 鍵なしの公開APIクライアントを用意
        $clients = [];
        foreach ($this->exchanges as $ex) {
            try {
                $creds = new Credentials(null, null); // 公開API用（鍵なし）
                $clients[$ex] = ExchangeFactory::make($ex, $creds);
            } catch (\Throwable $e) {
                $this->warn("init client failed: {$ex} => " . $e->getMessage());
            }
        }
        if (empty($clients)) {
            $this->error('no exchange client initialized');
            return CommandAlias::FAILURE;
        }

        $now = now();

        foreach ($clients as $exName => $ex) {
            foreach ($this->symbols as $symbol) {
                try {
                    $t   = $ex->ticker($symbol);
                    $ask = $t['ask'] ?? null;
                    $bid = $t['bid'] ?? null;
                    if ($ask === null || $bid === null) {
                        $this->warn("{$exName} missing ask/bid for {$symbol}");
                        continue;
                    }

                    $rate = new Rate();
                    $rate->exchange = $exName;   // bybit / bitget / kucoin / mexc
                    $rate->coin     = $symbol;   // BTC/USDT / ETH/USDT / XRP/USDT
                    $rate->ask      = (string)$ask;
                    $rate->bid      = (string)$bid;
                    $rate->save();

                    $this->line($now->toDateTimeString() . " {$exName} {$symbol} ask={$ask} bid={$bid}");
                } catch (\Throwable $e) {
                    Log::warning("rate:batch error ex={$exName} symbol={$symbol} : ".$e->getMessage());
                }
            }
        }

        return CommandAlias::SUCCESS;
    }
}