<?php

namespace App\Console\Commands;

use App\Library\Bitbank;
use App\Library\Bitget;
use App\Library\Coincheck;
use App\Library\Common;
use App\Library\Gate;
use App\Library\GmoCoin;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use KuCoin\SDK\Auth;
use KuCoin\SDK\KuCoinApi;
use KuCoin\SDK\PrivateApi\Account;
use Lin\Mxc\MxcSpot;
use PhitFlyer\PhitFlyerClient;
use Symfony\Component\Console\Command\Command as CommandAlias;

class BalanceBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balance:batch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get balance';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = User::where('approved_btc','=','yes')
            ->orWhere('approved_eth','=','yes')
            ->orWhere('approved_xrp','=','yes')
            ->orderBy('id')
            ->get();
        Log::info('Balance batch start users cnt:'.count($users));
        foreach($users as $user){
            //残高確認
            //bitflyer
            if($user->bitflyer_accesskey && $user->bitflyer_secretkey){
                try{
                    $bitflyer = new PhitFlyerClient($user->bitflyer_accesskey, $user->bitflyer_secretkey);
                    //残高を更新
                    $bitflyer_balances = Common::getBitflyerAllBalance($bitflyer);
                    $user->bitflyer_btc = $bitflyer_balances['BTC'];
                    $user->bitflyer_jpy = $bitflyer_balances['JPY'];
                }catch(\Exception $e){
                    if(str_contains($e->getPrevious()->getMessage(), 'Authentication error') || str_contains($e->getPrevious()->getMessage(), 'Key not found')){
                        $user->bitflyer_btc = '認証エラー';
                        $user->bitflyer_jpy = '認証エラー';
                    }else{
                        $user->bitflyer_btc = 'その他のエラー';
                        $user->bitflyer_jpy = 'その他のエラー';
                    }
                }
            }
            else{
                $user->bitflyer_btc = '認証キー未設定';
                $user->bitflyer_jpy = '認証キー未設定';
            }
            Log::info('user_id:'.$user->id.';bitflyer_balance_btc:'.$user->bitflyer_btc);
            Log::info('user_id:'.$user->id.';bitflyer_balance_jpy:'.$user->bitflyer_jpy);
            $user->save();
            //coincheck
            if($user->coincheck_accesskey && $user->coincheck_secretkey){
                $coincheck = new Coincheck($user->coincheck_accesskey, $user->coincheck_secretkey);
                //残高を更新
                $coincheck_balances = $coincheck->get_balance_all();
                $user->coincheck_btc = $coincheck_balances['btc'];
                $user->coincheck_jpy = $coincheck_balances['jpy'];
            }
            else{
                $user->coincheck_btc = '認証キー未設定';
                $user->coincheck_jpy = '認証キー未設定';
            }
            $user->save();
            Log::info('user_id:'.$user->id.';coincheck_balance_btc:'.$user->coincheck_btc);
            Log::info('user_id:'.$user->id.';coincheck_balance_jpy:'.$user->coincheck_jpy);
            //bitbank
            if($user->bitbank_accesskey && $user->bitbank_secretkey){
                $bitbank = new bitbank($user->bitbank_accesskey, $user->bitbank_secretkey);
                //残高を更新
                $bitbank_balance_result = $bitbank->get_balance_all();
                $bitbank_balance_jpy = $bitbank_balance_result['jpy'];
                $bitbank_balance_btc = $bitbank_balance_result['btc'];
                $bitbank_balance_eth = $bitbank_balance_result['eth'];
                $bitbank_balance_xrp = $bitbank_balance_result['xrp'];
                if($bitbank_balance_jpy == -1 || $bitbank_balance_btc == -1){
                    $bitbank_balance_jpy = '認証エラー';
                    $bitbank_balance_btc = '認証エラー';
                    $bitbank_balance_eth = '認証エラー';
                    $bitbank_balance_xrp = '認証エラー';
                }elseif($bitbank_balance_jpy == -2 || $bitbank_balance_btc == -2){
                    $bitbank_balance_jpy = '取引所エラー';
                    $bitbank_balance_btc = '取引所エラー';
                    $bitbank_balance_eth = '取引所エラー';
                    $bitbank_balance_xrp = '取引所エラー';
                }elseif($bitbank_balance_jpy == -3 || $bitbank_balance_btc == -3){
                    $bitbank_balance_jpy = 'その他のエラー';
                    $bitbank_balance_btc = 'その他のエラー';
                    $bitbank_balance_eth = 'その他のエラー';
                    $bitbank_balance_xrp = 'その他のエラー';
                }
            }
            else{
                $bitbank_balance_jpy = '認証キー未設定';
                $bitbank_balance_btc = '認証キー未設定';
                $bitbank_balance_eth = '認証キー未設定';
                $bitbank_balance_xrp = '認証キー未設定';
            }
            $user->bitbank_jpy = $bitbank_balance_jpy;
            $user->bitbank_btc = $bitbank_balance_btc;
            $user->bitbank_eth = $bitbank_balance_eth;
            $user->bitbank_xrp = $bitbank_balance_xrp;
            $user->save();
            Log::info('user_id:'.$user->id.';bitbank_balance_jpy:'.$user->bitbank_jpy);
            Log::info('user_id:'.$user->id.';bitbank_balance_btc:'.$user->bitbank_btc);
            Log::info('user_id:'.$user->id.';bitbank_balance_eth:'.$user->bitbank_eth);
            Log::info('user_id:'.$user->id.';bitbank_balance_xrp:'.$user->bitbank_xrp);

            //gmo
            if($user->gmo_accesskey && $user->gmo_secretkey){
                $gmo = new gmocoin($user->gmo_accesskey,$user->gmo_secretkey);
                //残高を更新
                usleep(500000);
                $gmo_balances = $gmo->get_all_balance();
                $user->gmo_jpy = $gmo_balances['JPY'];
                $user->gmo_btc = $gmo_balances['BTC'];
                $user->gmo_eth = $gmo_balances['ETH'];
                $user->gmo_xrp = $gmo_balances['XRP'];
            }
            else{
                $user->gmo_jpy = '認証キー未設定';
                $user->gmo_btc = '認証キー未設定';
                $user->gmo_eth = '認証キー未設定';
                $user->gmo_xrp = '認証キー未設定';
            }
            $user->save();
            Log::info('user_id:'.$user->id.';gmo_balance_jpy:'.$user->gmo_jpy);
            Log::info('user_id:'.$user->id.';gmo_balance_btc:'.$user->gmo_btc);
            Log::info('user_id:'.$user->id.';gmo_balance_eth:'.$user->gmo_eth);
            Log::info('user_id:'.$user->id.';gmo_balance_xrp:'.$user->gmo_xrp);
            //Gate.io
            if($user->approved_oversea == 'yes'){
                if($user->btc_gateio_auto=='1' && $user->gateio_accesskey && $user->gateio_secretkey) {
                    $gateio = new Gate($user->gateio_accesskey, $user->gateio_secretkey);
                    $user->gate_btc = $gateio->get_balance('BTC');
                    $user->gate_usdt = $gateio->get_balance('USDT');
                    $user->save();
                    Log::info('user_id:'.$user->id.';gate_balance_usdt:'.$user->gate_usdt);
                    Log::info('user_id:'.$user->id.';gate_balance_btc:'.$user->gate_btc);
                }
            }
            //Kucoin
            if($user->approved_oversea == 'yes'){
                if($user->btc_kucoin_auto=='1' && $user->kucoin_accesskey && $user->kucoin_secretkey && $user->kucoin_passphrase) {
                    //kucoin
                    KuCoinApi::setBaseUri('https://api.kucoin.com');
                    KuCoinApi::setDebugMode(false);
                    //板情報を取得
                    $auth = new Auth($user->kucoin_accesskey, $user->kucoin_secretkey, $user->kucoin_passphrase, Auth::API_KEY_VERSION_V2);
                    $kucoin = new Account($auth);
                    $kucoin_balances = Common::getKucoinAllBalance($kucoin);
                    $user->kucoin_usdt = $kucoin_balances['USDT'];
                    $user->kucoin_btc = $kucoin_balances['BTC'];
                    $user->save();
                    Log::info('user_id:'.$user->id.';kucoin_balance_usdt:'.$user->kucoin_usdt);
                    Log::info('user_id:'.$user->id.';kucoin_balance_btc:'.$user->kucoin_btc);
                }
            }
            //MEXC
            if($user->approved_oversea == 'yes'){
                if($user->btc_mexc_auto=='1' && $user->mexc_accesskey && $user->mexc_secretkey) {
                    $mexc = new MxcSpot($user->mexc_accesskey,$user->mexc_secretkey);
                    $balances = Common::get_mexc_allbalances($mexc);
                    $user->mexc_usdt = $balances['USDT'];
                    $user->mexc_btc = $balances['BTC'];
                    $user->save();
                    Log::info('user_id:'.$user->id.';mexc_balance_usdt:'.$user->mexc_usdt);
                    Log::info('user_id:'.$user->id.';mexc_balance_btc:'.$user->mexc_btc);
                }
            }
            //Bitget
            if($user->approved_oversea == 'yes'){
                if($user->btc_bitget_auto=='1' && $user->bitget_accesskey && $user->bitget_secretkey && $user->bitget_passphrase) {
                    $bitget = new Bitget($user->bitget_accesskey, $user->bitget_secretkey, $user->bitget_passphrase);
                    $bitgetbalances = $bitget->get_balance_all();
                    $user->bitget_usdt = $bitgetbalances['usdt'];
                    $user->bitget_btc = $bitgetbalances['btc'];
                    $user->save();
                    Log::info('user_id:'.$user->id.';bitget_balance_usdt:'.$user->bitget_usdt);
                    Log::info('user_id:'.$user->id.';bitget_balance_btc:'.$user->bitget_btc);
                }
            }

            sleep(1);
        }
        //為替レートも1時間ごとに更新
        $result = Common::getExchangeRate();
        DB::table('rates')
            ->where('exchange', 'port')
            ->where('coin', 'USD_JPY')
            ->update(['ask' => $result['ask'], 'bid' => $result['bid']]);
        Log::info('balance batch end');
        return CommandAlias::SUCCESS;
    }
}
