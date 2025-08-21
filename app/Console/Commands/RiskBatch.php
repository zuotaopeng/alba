<?php

namespace App\Console\Commands;

use App\Library\Bitbank;
use App\Library\Coincheck;
use App\Library\Common;
use App\Library\GmoCoin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhitFlyer\PhitFlyerClient;
use Symfony\Component\Console\Command\Command as CommandAlias;

class RiskBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'risk:batch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sell all coins when the risk is high';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //BTCリスクオン
        $btc_rate = 0;
        $eth_rate = 0;
        $xrp_rate = 0;
        $rates = DB::table('rates')->get();
        foreach ($rates as $rate) {
            if ($rate->coin == 'BTC_JPY') {
                $btc_rate = $rate->ask;
            }
            if ($rate->coin == 'ETH_JPY') {
                $eth_rate = $rate->ask;
            }
            if ($rate->coin == 'XRP_JPY') {
                $xrp_rate = $rate->ask;
            }
        }
        $btc_users = DB::table('users')
            ->where('losscut', '=', 'on')
            ->where('losscut_line', '>', 0)
            ->get();
        $eth_users = DB::table('users')
            ->where('losscut_eth', '=', 'on')
            ->where('losscut_line_eth', '>', 0)
            ->get();
        $xrp_users = DB::table('users')
            ->where('losscut_xrp', '=', 'on')
            ->where('losscut_line_xrp', '>', 0)
            ->get();
        foreach ($btc_users as $user) {
            $base_price = $user->baseline;
            $losscut_line = $user->losscut_line;
            if ((1 - $btc_rate / $base_price) > $losscut_line / 100) {
                //全てのBTCを売る
                Log::info('BTCリスクオフ');
                if ($user->bitflyer_accesskey && $user->bitflyer_secretkey) {
                    try {
                        $bitflyer = new PhitFlyerClient($user->bitflyer_accesskey, $user->bitflyer_secretkey);
                        $btc_amount = floor(Common::getBitflyerBalance($bitflyer, 'BTC') * 1000) / 1000;
                        if ($btc_amount > 0) {
                            $bitflyer_sell = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'SELL', (int)$price = null, $btc_amount);
                            if (array_key_exists('child_order_acceptance_id', $bitflyer_sell) &&
                                $bitflyer_sell['child_order_acceptance_id']) {
                                Log::info('リスクオフBTC;user_id'.$user->id.';bitFlyer売り成功');
                            }
                        }
                    } catch (\Exception $e) {
                        Log::info('リスクオフBTC：bitFlyer Exception:' . $e->getMessage());
                    }
                }
                if ($user->coincheck_accesskey && $user->coincheck_secretkey) {
                    $coincheck = new Coincheck($user->coincheck_accesskey, $user->coincheck_secretkey);
                    $btc_amount = floor($coincheck->get_balance('btc') * 1000) / 1000;
                    if ($btc_amount > 0) {
                        $coincheck_result = $coincheck->order->create(array(
                            "amount" => $btc_amount,
                            "order_type" => "market_sell",
                            "pair" => "btc_jpy"
                        ));
                        if ($coincheck_result && $coincheck_result['success']) {
                            Log::info('リスクオフBTC;user_id'.$user->id.';coincheck売り成功');
                        }
                    }
                }
                if ($user->bitbank_accesskey && $user->bitbank_secretkey) {
                    $bitbank = new bitbank($user->bitbank_accesskey, $user->bitbank_secretkey);
                    $btc_amount = floor(($bitbank->get_balance('btc')) * 1000) / 1000;
                    if ($btc_amount > 0) {
                        $bitbank_result = $bitbank->order('btc_jpy', 'sell', $btc_amount);
                        if (array_key_exists('order_id', $bitbank_result['data'])) {
                            Log::info('リスクオフBTC;user_id'.$user->id.';bitbank売り成功');
                        }
                    }
                }
                if ($user->gmo_accesskey && $user->gmo_secretkey) {
                    $gmo = new gmocoin($user->gmo_accesskey, $user->gmo_secretkey);
                    $btc_amount = floor(($gmo->get_balance('BTC')) * 1000) / 1000;
                    if ($btc_amount > 0) {
                        usleep(100000);
                        $gmo_result = $gmo->order('BTC', 'SELL', '' . $btc_amount);
                        if (array_key_exists('data', $gmo_result) && array_key_exists('status', $gmo_result) && $gmo_result['status'] == '0') {
                            Log::info('リスクオフBTC;user_id'.$user->id.';GMO売り成功');
                        }
                    }
                }
            }
        }

        foreach ($eth_users as $user) {
            $base_price = $user->baseline_eth;
            $losscut_line = $user->losscut_line_eth;
            if ((1 - $eth_rate / $base_price) > $losscut_line / 100) {
                //全てのETHを売る
                Log::info('ETHリスクオフ');
                if ($user->bitbank_accesskey && $user->bitbank_secretkey) {
                    $bitbank = new bitbank($user->bitbank_accesskey, $user->bitbank_secretkey);
                    $eth_amount = floor(($bitbank->get_balance('eth')) * 1000) / 1000;
                    if ($eth_amount > 0) {
                        $bitbank_result = $bitbank->order('eth_jpy', 'sell', $eth_amount);
                        if (array_key_exists('order_id', $bitbank_result['data'])) {
                            Log::info('リスクオフETH;user_id'.$user->id.';bitbank売り成功');
                        }
                    }
                }
                if ($user->gmo_accesskey && $user->gmo_secretkey) {
                    $gmo = new gmocoin($user->gmo_accesskey, $user->gmo_secretkey);
                    $eth_amount = floor(($gmo->get_balance('ETH')) * 1000) / 1000;
                    if ($eth_amount > 0) {
                        usleep(100000);
                        $gmo_result = $gmo->order('ETH', 'SELL', '' . $eth_amount);
                        if (array_key_exists('data', $gmo_result) && array_key_exists('status', $gmo_result) && $gmo_result['status'] == '0') {
                            Log::info('リスクオフETH;user_id'.$user->id.';GMO売り成功');
                        }
                    }
                }
            }
        }

        foreach ($xrp_users as $user) {
            $base_price = $user->baseline_xrp;
            $losscut_line = $user->losscut_line_xrp;
            if ((1 - $xrp_rate / $base_price) > $losscut_line / 100) {
                //全てのXRPを売る
                Log::info('XRPリスクオフ');
                if ($user->bitbank_accesskey && $user->bitbank_secretkey) {
                    $bitbank = new bitbank($user->bitbank_accesskey, $user->bitbank_secretkey);
                    $xrp_amount = floor(($bitbank->get_balance('xrp')) * 1000) / 1000;
                    if ($xrp_amount > 0) {
                        $bitbank_result = $bitbank->order('xrp_jpy', 'sell', $xrp_amount);
                        if (array_key_exists('order_id', $bitbank_result['data'])) {
                            Log::info('リスクオフXRP;user_id'.$user->id.';bitbank売り成功');
                        }
                    }
                }
                if ($user->gmo_accesskey && $user->gmo_secretkey) {
                    $gmo = new gmocoin($user->gmo_accesskey, $user->gmo_secretkey);
                    $xrp_amount = floor(($gmo->get_balance('XRP')) * 1000) / 1000;
                    if ($xrp_amount > 0) {
                        usleep(100000);
                        $gmo_result = $gmo->order('XRP', 'SELL', '' . $xrp_amount);
                        if (array_key_exists('data', $gmo_result) && array_key_exists('status', $gmo_result) && $gmo_result['status'] == '0') {
                            Log::info('リスクオフXRP;user_id'.$user->id.';GMO売り成功');
                        }
                    }
                }
            }
        }
        return CommandAlias::SUCCESS;
    }
}
