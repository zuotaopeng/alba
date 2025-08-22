<?php

namespace App\Services\Exchanges;

use App\Models\User;

class ExchangeFactory
{
    public static function make(string $exchange, Credentials $creds): BaseExchange
    {
        return match (strtolower($exchange)) {
            'bybit'  => new BybitExchange($creds),
            'bitget' => new BitgetExchange($creds),
            'kucoin' => new KucoinExchange($creds),
            'mexc'   => new MexcExchange($creds),
            default  => throw new \InvalidArgumentException("Unsupported exchange: {$exchange}"),
        };
    }
}