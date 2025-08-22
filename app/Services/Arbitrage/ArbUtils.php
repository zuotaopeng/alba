<?php

namespace App\Services\Arbitrage;


use Illuminate\Support\Facades\DB;


class ArbUtils
{
    public static function weightedAvg(array $levels, float $amount): float
    {
        $remain = $amount; $sum = 0.0;
        foreach ($levels as $lvl) {
            [$price, $qty] = [(float)$lvl[0], (float)$lvl[1]];
            if ($qty <= 0) continue;
            $take = min($remain, $qty);
            $sum += $take * $price;
            $remain -= $take;
            if ($remain <= 1e-12) break;
        }
        if ($remain > 0) {
            throw new \RuntimeException('insufficient liquidity');
        }
        return $sum / $amount;
    }


    public static function calcAvgAskBid($ex, string $symbol, float $amount, int $depth = 200): array
    {
        $book = $ex->orderBook($symbol, $depth);
        $avgAsk = self::weightedAvg($book['asks'] ?? [], $amount);
        $avgBid = self::weightedAvg($book['bids'] ?? [], $amount);
        return [$avgAsk, $avgBid];
    }


    public static function iocParamsFor(string $exchange): array
    {
        return match (strtolower($exchange)) {
            'bybit' => ['timeInForce' => 'IOC'],
            'bitget' => ['timeInForce' => 'ioc'],
            'kucoin' => ['timeInForce' => 'IOC'],
            'mexc' => ['timeInForce' => 'IOC'],
            default => [],
        };
    }


    /** Wait order filled amount (polling) */
    public static function waitFilledAmount($ex, string $orderId, string $symbol, int $intervalMs, int $timeoutMs): float
    {
        $elapsed = 0; $filled = 0.0;
        while ($elapsed <= $timeoutMs) {
            $o = $ex->fetchOrder($orderId, $symbol);
            $filled = (float)($o['filled'] ?? 0.0);
            $status = $o['status'] ?? '';
            if ($status === 'closed' || $status === 'canceled') break;
            usleep($intervalMs * 1000);
            $elapsed += $intervalMs;
        }
        return $filled;
    }


    /** Persist trade record into order_* table (BTC/ETH/XRP share same schema) */
    public static function record(string $table, array $row): void
    {
        $now = now();
        DB::table($table)->insert(array_merge([
            'created_at' => $now,
            'updated_at' => $now,
            'trade_time' => $now,
        ], $row));
    }
}