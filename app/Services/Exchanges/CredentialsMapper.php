<?php

namespace App\Services\Exchanges;

class CredentialsMapper
{
    public static function fromUser(User $user, string $exchange, array $options = []): Credentials
    {
        switch (strtolower($exchange)) {
            case 'bybit':
                return new Credentials(
                    $user->bybit_accesskey,
                    $user->bybit_secretkey,
                    null,
                    $options
                );
            case 'bitget':
                return new Credentials(
                    $user->bitget_accesskey,
                    $user->bitget_secretkey,
                    $user->bitget_passphrase, // ← passphrase
                    $options
                );
            case 'kucoin':
                return new Credentials(
                    $user->kucoin_accesskey,
                    $user->kucoin_secretkey,
                    $user->kucoin_passphrase, // ← passphrase
                    $options
                );
            case 'mexc':
                return new Credentials(
                    $user->mexc_accesskey,
                    $user->mexc_secretkey,
                    null,
                    $options
                );
            default:
                throw new \InvalidArgumentException("Unsupported exchange: {$exchange}");
        }
    }
}