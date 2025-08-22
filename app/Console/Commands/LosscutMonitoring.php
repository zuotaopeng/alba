<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Exchanges\Credentials;
use App\Services\Exchanges\ExchangeFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class LosscutMonitoring extends Command
{
    protected $signature = 'losscut:monitoring';
    protected $description = '損切りラインを監視し、条件を満たしたら全取引所で対象通貨を成行で全量売却';

    /** 監視対象 */
    private array $symbols = [
        'btc' => 'BTC/USDT',
        'eth' => 'ETH/USDT',
        'xrp' => 'XRP/USDT',
    ];

    /** 価格参照に使う取引所（公開APIでOK） */
    private string $priceExchange = 'bybit';

    public function handle()
    {
        $users = User::all();

        foreach ($users as $user) {
            foreach ($this->symbols as $asset => $symbol) {
                // 通貨ごとの設定を users から読む
                $losscutFlag = $asset === 'btc' ? $user->losscut       : $user->{'losscut_'.$asset};
                $baseline    = $asset === 'btc' ? $user->baseline      : $user->{'baseline_'.$asset};
                $line        = $asset === 'btc' ? $user->losscut_line  : $user->{'losscut_line_'.$asset};

                if ($losscutFlag !== 'on' || $baseline <= 0) {
                    continue;
                }

                // 現在価格（Rateテーブルを使うならここを差し替え）
                $price = $this->getCurrentMarketPrice($symbol);
                if ($price === null) {
                    continue;
                }

                $threshold = $baseline * (1 - ($line / 100));
                if ($price > $threshold) {
                    continue; // まだ損切り条件未満
                }

                Log::warning("Losscut trigger user={$user->id} asset={$asset} price={$price} baseline={$baseline} line={$line}% threshold={$threshold}");

                // 全取引所で該当通貨の free を成行売り
                $this->sellAllExchanges($user, $asset, $symbol);
            }
        }

        return CommandAlias::SUCCESS;
    }

    /** Bybit 公開APIで現在価格を取得（必要なら Rate 参照に差し替え可） */
    private function getCurrentMarketPrice(string $symbol): ?float
    {
        try {
            $ex = ExchangeFactory::make($this->priceExchange, new Credentials(null, null));
            $t  = $ex->ticker($symbol);
            // last が無いこともあるため保険で bid/ask の中間などにフォールバックしてもOK
            if (isset($t['last'])) return (float)$t['last'];
            if (isset($t['bid']) && isset($t['ask'])) return ((float)$t['bid'] + (float)$t['ask']) / 2.0;
            return null;
        } catch (\Throwable $e) {
            Log::error("getCurrentMarketPrice error: ".$e->getMessage());
            return null;
        }
    }

    /** 全取引所で該当通貨の free 分を成行で全量売却 */
    private function sellAllExchanges(User $user, string $asset, string $symbol): void
    {
        $assetCode = strtoupper($asset); // 'BTC' / 'ETH' / 'XRP'

        foreach (['bybit','bitget','kucoin','mexc'] as $exName) {
            $apiKey = $user->{$exName.'_accesskey'} ?? null;
            $secret = $user->{$exName.'_secretkey'} ?? null;
            $pass   = $user->{$exName.'_passphrase'} ?? null; // bitget/kucoin だけ存在

            if (!$apiKey || !$secret) continue;

            try {
                $ex = ExchangeFactory::make($exName, new Credentials($apiKey, $secret, $pass));

                // 残高（free）取得
                $bal = $ex->balance(); // ccxtのfetch_balance()の戻り
                $freeAmount = (float)($bal['free'][$assetCode] ?? 0);
                if ($freeAmount <= 0) {
                    Log::info("user={$user->id} {$exName} {$assetCode} free=0 -> skip");
                    continue;
                }

                // 取引所の最小数量に合わせて丸め（BaseExchange 内の amount_to_precision を利用）
                $freeAmount = (float)$ex->amountPrecision($symbol, $freeAmount); // publicにしている前提。protectedなら丸め関数をBaseにpublic追加してください

                if ($freeAmount <= 0) {
                    Log::info("user={$user->id} {$exName} {$assetCode} rounded to 0 -> skip");
                    continue;
                }

                // 成行売り（正しいメソッドは marketSell）
                $order = $ex->marketSell($symbol, $freeAmount);
                Log::info("Losscut SELL user={$user->id} ex={$exName} {$symbol} amount={$freeAmount} order_id=" . ($order['id'] ?? 'n/a'));
            } catch (\Throwable $e) {
                Log::error("Losscut SELL failed user={$user->id} ex={$exName} {$symbol}: ".$e->getMessage());
            }
        }
    }
}
