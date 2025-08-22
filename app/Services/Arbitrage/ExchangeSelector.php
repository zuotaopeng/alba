<?php

namespace App\Services\Arbitrage;


use App\Models\User;
use App\Services\Exchanges\{Credentials, ExchangeFactory};


class ExchangeSelector
{
    /** Map e.g. 'btc' => column suffixes */
    private const COLS = [
        'bybit' => ['auto' => '%s_bybit_auto', 'key' => 'bybit_accesskey', 'sec' => 'bybit_secretkey', 'pp' => null],
        'bitget' => ['auto' => '%s_bitget_auto', 'key' => 'bitget_accesskey', 'sec' => 'bitget_secretkey', 'pp' => 'bitget_passphrase'],
        'kucoin' => ['auto' => '%s_kucoin_auto', 'key' => 'kucoin_accesskey', 'sec' => 'kucoin_secretkey', 'pp' => 'kucoin_passphrase'],
        'mexc' => ['auto' => '%s_mexc_auto', 'key' => 'mexc_accesskey', 'sec' => 'mexc_secretkey', 'pp' => null],
    ];


    /**
     * @param User $user
     * @param string $base 'btc'|'eth'|'xrp'
     * @param array $options ccxt options (e.g. ['defaultType'=>'spot'])
     * @return array [name => BaseExchange]
     */
    public static function build(User $user, string $base, array $options = []): array
    {
        $base = strtolower($base);
        $list = [];
        foreach (self::COLS as $name => $cfg) {
            $autoCol = sprintf($cfg['auto'], $base);
            if (($user->$autoCol ?? 0) != 1) continue;
            $apiKey = $user->{$cfg['key']} ?? null;
            $secret = $user->{$cfg['sec']} ?? null;
            $pp = $cfg['pp'] ? ($user->{$cfg['pp']} ?? null) : null;
            if (!$apiKey || !$secret) continue;
            $creds = new Credentials($apiKey, $secret, $pp, $options);
            $list[$name] = ExchangeFactory::make($name, $creds);
        }
        return $list;
    }
}