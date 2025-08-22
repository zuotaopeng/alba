<?php

namespace App\Services\Exchanges;
class BitgetExchange extends BaseExchange
{
    protected function createClient()
    {
        $class = "\\ccxt\\bitget";
        return new $class([
            'apiKey'          => $this->creds->apiKey,
            'secret'          => $this->creds->secret,
            'password'        => $this->creds->passphrase, // passphraseはpasswordへ
            'enableRateLimit' => true,
            'timeout'         => 15000,
            'options'         => $this->creds->options,
        ]);
    }

}