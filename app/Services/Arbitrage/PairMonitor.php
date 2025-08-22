<?php

namespace App\Services\Arbitrage;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PairMonitor
{
    public function __construct(
        private string $symbol,              // 'BTC/USDT', 'ETH/USDT', 'XRP/USDT'
        private string $base,                // 'btc'|'eth'|'xrp'
        private int    $orderbookDepth = 200,
        private int    $pollIntervalMs = 700,
        private int    $pollTimeoutMs  = 15000,
    ) {}

    public function runForUser(User $user, float $amount, float $threshold, array $options = []): void
    {
        $clients = ExchangeSelector::build($user, $this->base, $options);
        if (count($clients) < 2) return;

        $ask = $bid = [];
        foreach ($clients as $name => $ex) {
            try {
                [$avgAsk, $avgBid] = ArbUtils::calcAvgAskBid($ex, $this->symbol, $amount, $this->orderbookDepth);
                $ask[$name] = $avgAsk;
                $bid[$name] = $avgBid;
            } catch (\Throwable $e) {
                Log::warning("user:{$user->id} {$name} avg error: " . $e->getMessage());
            }
        }
        if (count($ask) < 2 || count($bid) < 2) return;

        $bestAskValue = min($ask);
        $bestAsk = array_search($bestAskValue, $ask, true);
        $bestBidValue = max($bid);
        $bestBid = array_search($bestBidValue, $bid, true);
        $diff = $bestBidValue - $bestAskValue;
        Log::info("{$this->base} user:{$user->id} diff={$diff} ({$bestAsk}=>{$bestBid})");
        if ($bestAskValue <= 0 || $diff <= $threshold) return;

        // ---- balances ----
        $buyEx = $clients[$bestAsk];
        $sellEx = $clients[$bestBid];
        $buyBal = $buyEx->balance();
        $sellBal = $sellEx->balance();
        $usdtFree = (float)($buyBal['free']['USDT'] ?? 0);
        $baseFree = (float)($sellBal['free'][strtoupper($this->base)] ?? 0);
        $needUsdt = $bestAskValue * $amount * 1.001;
        if ($usdtFree < $needUsdt || $baseFree < $amount) return;

        // ---- place buy (IOC if possible) ----
        $buyParams = ArbUtils::iocParamsFor($bestAsk);
        $limit = $bestAskValue * 1.0005;
        $bo = $buyEx->limitBuy($this->symbol, $amount, $limit, $buyParams);
        $filled = ArbUtils::waitFilledAmount($buyEx, $bo['id'], $this->symbol, $this->pollIntervalMs, $this->pollTimeoutMs);
        if ($filled <= 0) {
            try {
                $buyEx->cancelOrder($bo['id'], $this->symbol);
            } catch (\Throwable) {
            }
            ArbUtils::record("order_{$this->base}s", [
                'user_id' => $user->id,
                'buy_exchange' => $bestAsk,
                'sell_exchange' => $bestBid,
                'buy_amount' => 0,
                'sell_amount' => 0,
                'status' => 'not_filled',
                'meta' => json_encode(['diff' => $diff, 'avg_ask' => $bestAskValue, 'avg_bid' => $bestBidValue]),
            ]);
            return;
        }

        // ---- sell market ----
        $so = $sellEx->marketSell($this->symbol, $filled);
        $sold = ArbUtils::waitFilledAmount($sellEx, $so['id'], $this->symbol, $this->pollIntervalMs, $this->pollTimeoutMs);

        ArbUtils::record("order_{$this->base}s", [
            'user_id' => $user->id,
            'buy_exchange' => $bestAsk,
            'sell_exchange' => $bestBid,
            'buy_amount' => $filled,
            'sell_amount' => $sold,
            'status' => $sold > 0 ? 'done' : 'sell_partial_or_fail',
            'meta' => json_encode([
                'diff' => $diff, 'avg_ask' => $bestAskValue, 'avg_bid' => $bestBidValue,
                'buy_order' => $bo, 'sell_order' => $so,
            ]),
        ]);
    }
}