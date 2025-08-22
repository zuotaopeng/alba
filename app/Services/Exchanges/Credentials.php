<?php

namespace App\Services\Exchanges;

class Credentials
{
    public $apiKey;
    public $secret;
    public $passphrase; // Bitget / KuCoin 用
    public $options;

    public function __construct(?string $apiKey, ?string $secret, ?string $passphrase = null, array $options = [])
    {
        $this->apiKey = $apiKey;
        $this->secret = $secret;
        $this->passphrase = $passphrase;
        $this->options = $options;
    }

}