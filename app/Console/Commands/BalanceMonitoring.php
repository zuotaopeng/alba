<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Exchanges\CredentialsMapper;
use App\Services\Exchanges\ExchangeFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BalanceMonitoring extends Command
{
    protected $signature = 'balance:monitoring 
        {--ex= : 取引所をカンマ区切りで指定 (bybit,bitget,kucoin,mexc)}';

    protected $description = '全ユーザーの取引所残高（freeのみ）をusersテーブルに保存';

    /** 保存対象アセット */
    private array $assets = ['USDT', 'BTC', 'ETH', 'XRP'];

    /** サポート取引所 */
    private array $exchanges = ['bybit','bitget','kucoin','mexc'];

    public function handle()
    {
        $targetEx = $this->option('ex')
            ? array_values(array_intersect(
                array_map('strtolower', array_map('trim', explode(',', $this->option('ex')))),
                $this->exchanges
            ))
            : $this->exchanges;

        $users = User::where('approved', 'yes')
            ->where(function ($query) use ($targetEx) {
                foreach ($targetEx as $ex) {
                    $query->orWhere(function ($q) use ($ex) {
                        $q->where("{$ex}_accesskey", '!=', '')
                            ->where("{$ex}_secretkey", '!=', '');
                    });
                }
            })
            ->orderBy('id')
            ->get();
        $this->info("対象ユーザー数: ".count($users)." / 取引所: ".implode(',', $targetEx));

        foreach ($users as $user) {
            foreach ($targetEx as $ex) {
                try {
                    $creds = CredentialsMapper::fromUser($user, $ex);
                    if (!$creds->apiKey || !$creds->secret) {
                        continue; // APIキー未設定はスキップ
                    }
                    $exchange = ExchangeFactory::make($ex, $creds);
                    $balance  = $exchange->getClient()->fetch_balance();
                    $free    = (array)($balance['free'] ?? []);
                    $dirty = false;
                    foreach ($this->assets as $asset) {
                        // 通貨ごとに approved_xxx を確認
                        $approvedColumn = 'approved_' . strtolower($asset);
                        if (isset($user->$approvedColumn) && $user->$approvedColumn === 'no') {
                            continue; // 承認されていないアセットはスキップ
                        }
                        $col = "{$ex}_".strtolower($asset); // 例: bybit_usdt
                        $val = isset($free[$asset]) ? (string)$free[$asset] : '0';
                        if ($user->$col !== $val) {
                            $user->$col = $val;
                            $dirty = true;
                        }
                    }
                    if ($dirty) {
                        $user->save();
                    }
                    $this->line("user_id={$user->id} {$ex} 残高更新");
                } catch (\Throwable $e) {
                    Log::warning("BalanceSync error user_id={$user->id} ex={$ex}: ".$e->getMessage());
                }
            }
        }

        $this->info('残高同期 完了（freeのみ保存）');
        return self::SUCCESS;
    }
}
