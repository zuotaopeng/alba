<?php

namespace App\Services\Exchanges;

class MexcExchange extends BaseExchange
{
    protected function createClient()
    {
        $class = "\\ccxt\\mexc";
        return new $class([
            'apiKey'          => $this->creds->apiKey,
            'secret'          => $this->creds->secret,
            'enableRateLimit' => true,
            'timeout'         => 15000,
            'options'         => $this->creds->options,
        ]);
    }
}