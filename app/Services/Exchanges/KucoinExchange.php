<?php

namespace App\Services\Exchanges;

class KucoinExchange extends BaseExchange
{
    protected function createClient()
    {
        $class = "\\ccxt\\kucoin"; // 先物は \\ccxt\\kucoinfutures
        return new $class([
            'apiKey'          => $this->creds->apiKey,
            'secret'          => $this->creds->secret,
            'password'        => $this->creds->passphrase,
            'enableRateLimit' => true,
            'timeout'         => 15000,
            'options'         => $this->creds->options,
        ]);
    }
}