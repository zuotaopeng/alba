<?php

namespace App\Console\Commands;

use App\Library\Bitbank;
use App\Library\Bitget;
use App\Library\Coincheck;
use App\Library\Common;
use App\Library\Gate;
use App\Library\GmoCoin;
use App\Models\OrderBtc;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use KuCoin\SDK\Auth;
use KuCoin\SDK\KuCoinApi;
use KuCoin\SDK\PrivateApi\Account;
use KuCoin\SDK\PrivateApi\Order as KucoinOrder;
use KuCoin\SDK\PublicApi\Symbol as PublicSymbol;
use Lin\Mxc\MxcSpot;
use PhitFlyer\PhitFlyerClient;
use Symfony\Component\Console\Command\Command as CommandAlias;

class BtcMonitoring extends Command
{
    protected $signature = 'btc:monitoring {--delay= : Number of seconds to delay command}';

    protected $description = 'BTC Arbitrage';

    public function handle()
    {
        $users = User::where('approved_btc','=','yes')
            ->where('btc_threshold','>',0)
            ->where('btc_amount','>',0)
            ->where(DB::raw("(btc_bitflyer_auto + btc_coincheck_auto + btc_gmo_auto + btc_bitbank_auto + btc_gate_auto + btc_kucoin_auto + btc_mexc_auto + btc_bitget_auto)"),'>',1)
            ->orderBy('id')
            ->offset((config('consts.server_id')-1) * 10)
            ->limit(10)
            ->get();
        Log::info('btc monitoring start users cnt:'.count($users));
        $usdjpy_bid = 0;
        $usdjpy_ask = 0;
        $usdjpy = DB::table('rates')
            ->where('coin','=','USD_JPY')
            ->first();
        if(!empty($usdjpy)){
            $usdjpy_bid = $usdjpy->bid;
            $usdjpy_ask = $usdjpy->ask;
        }
        foreach($users as $user){
            $ask_array = array();
            $bid_array = array();
            if($user->btc_bitflyer_auto=='1' && $user->bitflyer_accesskey && $user->bitflyer_secretkey){
                $bitflyer = new PhitFlyerClient($user->bitflyer_accesskey, $user->bitflyer_secretkey);
                $bitflyer_status = '';
                try{
                    $bitflyer_health = $bitflyer->getHealth();
                    $bitflyer_status = $bitflyer_health['status'];
                }catch (\Exception $e){
                    Log::info('bitflyer get health exception:'.$e->getMessage());
                }
                if($bitflyer_status == 'NORMAL' && intval(date('H')) != 4){
                    $average_price_bitflyer = Common::getBitflyerAveragePrice($bitflyer,$user->btc_amount);
                    if(array_key_exists('ask', $average_price_bitflyer)){
                        $ask_array['bitflyer'] = $average_price_bitflyer['ask'] * (1 + config('consts.biftlyer_fee'));
                    }
                    if(array_key_exists('bid', $average_price_bitflyer)){
                        $bid_array['bitflyer'] = $average_price_bitflyer['bid'] * (1 - config('consts.biftlyer_fee'));
                    }
                }
            }
            if($user->btc_coincheck_auto=='1' && $user->coincheck_accesskey && $user->coincheck_secretkey){
                $coincheck = new Coincheck($user->coincheck_accesskey, $user->coincheck_secretkey);
                $average_price_coincheck = $coincheck->get_average_price($user->btc_amount);
                if(array_key_exists('ask', $average_price_coincheck)){
                    $ask_array['coincheck'] = $average_price_coincheck['ask'];
                }
                if(array_key_exists('bid', $average_price_coincheck)){
                    $bid_array['coincheck'] = $average_price_coincheck['bid'];
                }
            }
            if($user->btc_bitbank_auto=='1' && $user->bitbank_accesskey && $user->bitbank_secretkey){
                $bitbank = new bitbank($user->bitbank_accesskey, $user->bitbank_secretkey);
                $bitbank_status = $bitbank->getStatus();
                if(array_key_exists('btc',$bitbank_status) && $bitbank_status['btc'] == 'NORMAL'){
                    $average_price_bitbank = $bitbank->get_average_price('btc_jpy',$user->btc_amount);
                    if(array_key_exists('ask', $average_price_bitbank)){
                        $ask_array['bitbank'] = $average_price_bitbank['ask'] * (1 + config('consts.bitbank_fee'));
                    }
                    if(array_key_exists('bid', $average_price_bitbank)){
                        $bid_array['bitbank'] = $average_price_bitbank['bid'] * (1 - config('consts.bitbank_fee'));
                    }
                }
            }
            if($user->btc_gmo_auto=='1' && $user->gmo_accesskey && $user->gmo_secretkey){
                $gmo = new gmocoin($user->gmo_accesskey, $user->gmo_secretkey);
                $average_price_gmo = $gmo->get_average_price('BTC',$user->btc_amount);
                if(array_key_exists('ask', $average_price_gmo)){
                    $ask_array['gmo'] = $average_price_gmo['ask'] * (1 + config('consts.gmo_fee'));
                }
                if(array_key_exists('bid', $average_price_gmo)){
                    $bid_array['gmo'] = $average_price_gmo['bid'] * (1 - config('consts.gmo_fee'));
                }
            }
            if($user->approved_oversea == 'yes'){
                if($user->btc_gate_auto=='1' && $user->gate_accesskey && $user->gate_secretkey){
                    $gate = new Gate($user->gate_accesskey, $user->gate_secretkey);
                    $average_price_gate = $gate->get_average_price($user->btc_amount);
                    if(array_key_exists('ask', $average_price_gate)){
                        $ask_array['gate'] = ceil($average_price_gate['ask'] * $usdjpy_bid);
                    }
                    if(array_key_exists('bid', $average_price_gate)){
                        $bid_array['gate'] = floor($average_price_gate['bid'] * $usdjpy_ask);
                    }
                }
                if($user->btc_kucoin_auto=='1' && $user->kucoin_accesskey && $user->kucoin_secretkey){
                    //kucoin
                    KuCoinApi::setBaseUri('https://api.kucoin.com');
                    KuCoinApi::setDebugMode(false);
                    //板情報を取得
                    $kucoin_public_symbol = new PublicSymbol();
                    $kucoin_auth = new Auth($user->kucoinaccesskey, $user->kucoinsecretkey, $user->kucoinpassphrase, Auth::API_KEY_VERSION_V2);
                    $kucoin = new Account($kucoin_auth);
                    $average_price_kucoin = Common::get_kucoin_average_price($kucoin_public_symbol, 'BTC-USDT', $user->btc_amount);
                    if(array_key_exists('ask', $average_price_kucoin)){
                        $ask_array['kucoin'] = ceil($average_price_kucoin['ask'] * $usdjpy_bid);
                    }
                    if(array_key_exists('bid', $average_price_kucoin)){
                        $bid_array['kucoin'] = floor($average_price_kucoin['bid'] * $usdjpy_ask);
                    }
                }
                if($user->btc_mexc_auto=='1' && $user->mexc_accesskey && $user->mexc_secretkey) {
                    $mexc = new MxcSpot($user->mexc_accesskey, $user->mexc_secretkey);
                    $average_price_mexc = Common::get_mexc_average_price($mexc, 'BTCUSDT', $user->btc_amount);
                    if (array_key_exists('ask', $average_price_mexc)) {
                        $ask_array['mexc'] = ceil($average_price_mexc['ask'] * $usdjpy_bid);
                    }
                    if (array_key_exists('bid', $average_price_mexc)) {
                        $bid_array['mexc'] = floor($average_price_mexc['bid'] * $usdjpy_ask);
                    }
                }
                if($user->btc_bitget_auto=='1' && $user->bitget_accesskey && $user->bitget_secretkey){
                    $bitget = new Bitget($user->bitget_accesskey, $user->bitget_secretkey, $user->bitget_passphrase);
                    $average_price_bitget = $bitget->get_average_price($user->btc_amount);
                    if(array_key_exists('ask', $average_price_bitget)){
                        $ask_array['bitget'] = ceil($average_price_bitget['ask'] * $usdjpy_bid);
                    }
                    if(array_key_exists('bid', $average_price_bitget)){
                        $bid_array['bitget'] = floor($average_price_bitget['bid'] * $usdjpy_ask);
                    }
                }
            }

            //2取引所以上
            if(count($ask_array) < 2 || count($bid_array) < 2){
                continue;
            }
            //価格差の取得
            $best_ask_value = min($ask_array);
            $best_ask = array_search($best_ask_value, $ask_array);
            $best_bid_value = max($bid_array);
            $best_bid = array_search($best_bid_value, $bid_array);
            $best_diff = $best_bid_value - $best_ask_value;
            Log::info('BTC user id:'.$user->id.';best_diff:'.$best_diff);
            //2取引所以上
            if($best_ask_value > 0 && $best_diff > $user->btc_threshold){
                try{
                    //取引所の失敗時間を確認する。
                    $error_cnt = DB::table('order_btcs')
                        ->whereRaw("trade_time > current_timestamp + interval -1 hour")
                        ->where('user_id','=',$user->id)
                        ->where(function($query) use ($best_ask, $best_bid){
                            $query->where(function($query1) use ($best_ask){
                                $query1->where('buy_amount','=',0)
                                    ->where('buy_exchange','=',$best_ask);
                            })->orWhere(function($query2) use ($best_bid){
                                $query2->where('sell_amount','=',0)
                                    ->where('sell_exchange','=',$best_bid);
                            });
                        })->count();
                    if($error_cnt > 0){
                        Log::info('user_id:'.$user->id.';error orders cnt:'.$error_cnt);
                        continue;
                    }
                    if ($best_ask == 'bitflyer' && !empty($bitflyer)){
                        //日本円残高確認
                        $bitflyer_balance_jpy = Common::getBitflyerBalance($bitflyer, 'JPY');
                        if($bitflyer_balance_jpy < $best_ask_value*$user->btc_amount * 1.1){
                            Log::info('user_id:'.$user->id.';biftlyer日本円残高足りない;');
                            continue;
                        }
                        if($best_bid == 'coincheck' && !empty($coincheck)){
                            //coincheck BTC 残高確認
                            $coincheck_balance_btc = $coincheck->get_balance('btc');
                            if($coincheck_balance_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';coincheck btc残高足りない;');
                                continue;
                            }
                            //bitflyerで買い
                            $bitflyer_buy = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'BUY', 0, $user->btc_amount);
                            if(array_key_exists('child_order_acceptance_id',$bitflyer_buy) &&
                                $bitflyer_buy['child_order_acceptance_id']){
                                Log::info('user_id:'.$user->id.';bitflyer買い成功');
                                //coincheckで売り
                                $coincheck_result = $coincheck->order(array(
                                    "amount" => $user->btc_amount,
                                    "order_type" => "market_sell",
                                    "pair" => "btc_jpy"
                                ));
                                if($coincheck_result && array_key_exists('success',$coincheck_result) &&
                                    $coincheck_result['success']){
                                    Log::info('user_id:'.$user->id.';coincheck売り成功');
                                    //coincheckで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitflyer';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'coincheck';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                }else{
                                    //coincheck売り失敗、ロールバック
                                    if($user->rollback=='on'){
                                        $bitflyer_sell = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'SELL', 0, $user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitflyer';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'coincheck';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_coincheck_auto = 0;
                                    $user->save();
                                    Log::info('user_id:'.$user->id.';coincheck売り失敗');
                                }
                            }
                        }
                        elseif($best_bid == 'bitbank' && !empty($bitbank)){
                            //bitbank BTC 残高確認
                            $bitbank_balance_btc = $bitbank->get_balance('btc');
                            if($bitbank_balance_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';bitbank btc残高足りない;');
                                continue;
                            }
                            //bitflyerで買い
                            $bitflyer_buy = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'BUY', 0, $user->btc_amount);
                            if(array_key_exists('child_order_acceptance_id',$bitflyer_buy) &&
                                $bitflyer_buy['child_order_acceptance_id']){
                                Log::info('user_id:'.$user->id.';bitflyer買い成功');
                                //bitbank売り
                                $bitbank_result = $bitbank->order('btc_jpy','sell',$user->btc_amount);
                                if(array_key_exists('order_id', $bitbank_result['data'])){
                                    Log::info('user_id:'.$user->id.';bitbank売り成功');
                                    //bitflyerで買い、bitbankで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitflyer';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitbank';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                }else{
                                    //coincheck売り失敗、ロールバック
                                    if($user->rollback=='on'){
                                        $bitflyer_sell = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'SELL', 0, $user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitflyer';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitbank';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_bitbank_auto = 0;
                                    $user->save();
                                    Log::info('user_id:'.$user->id.';bitbank売り失敗');
                                }
                            }
                        }
                        elseif($best_bid == 'gmo' && !empty($gmo)){
                            //gmo BTC 残高確認
                            $gmo_balance_btc = $gmo->get_balance('BTC');
                            if($gmo_balance_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';gmo btc残高足りない;');
                                continue;
                            }
                            //bitflyerで買い
                            $bitflyer_buy = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'BUY', 0, $user->btc_amount);
                            if(array_key_exists('child_order_acceptance_id',$bitflyer_buy) &&
                                $bitflyer_buy['child_order_acceptance_id']){
                                Log::info('user_id:'.$user->id.';bitflyer買い成功');
                                //gmoで売り
                                usleep(100000);
                                $gmo_result = $gmo->order('BTC','SELL',$user->btc_amount);
                                if(array_key_exists('data', $gmo_result) && array_key_exists('status', $gmo_result) && $gmo_result['status'] == '0'){
                                    Log::info('user_id:'.$user->id.';GMO売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitflyer';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'gmo';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                }else{
                                    //GMO売り失敗、ロールバック
                                    if($user->rollback=='on'){
                                        $bitflyer_sell = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'SELL', 0, $user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitflyer';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'gmo';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_gmo_auto = 0;
                                    $user->save();
                                    Log::info('user_id:'.$user->id.';gmo売り失敗');
                                }
                            }
                        }
                        elseif($best_bid == 'gate' && !empty($gate)){
                            //gate BTC 残高確認
                            $gate_btc = $gate->get_balance('BTC');
                            if($gate_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';gate btc残高足りない;');
                                continue;
                            }
                            //bitflyerで買い
                            $bitflyer_buy = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'BUY', 0, $user->btc_amount);
                            if(array_key_exists('child_order_acceptance_id',$bitflyer_buy) &&
                                $bitflyer_buy['child_order_acceptance_id']) {
                                Log::info('user_id:' . $user->id . ';bitflyer買い成功');
                                //gateで売り
                                $gate_result = $gate->order('BTC_USDT','sell',$user->btc_amount);
                                if(array_key_exists('id',$gate_result)){
                                    Log::info('user_id:' . $user->id . ';gate売り成功');
                                    //bitflyerで買い、gateで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitflyer';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'gate';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //gate売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $bitflyer_sell = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'SELL', 0, $user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitflyer';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'gate';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_gate_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';gate売り失敗');
                                }
                            }

                        }
                        elseif($best_bid == 'kucoin' && !empty($kucoin) && !empty($kucoin_auth)){
                            //kucoin BTC 残高確認
                            $kucoin_btc = Common::getKucoinBalance($kucoin, 'BTC');
                            if($kucoin_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';kucoin btc残高足りない;');
                                continue;
                            }
                            //bitflyerで買い
                            $bitflyer_buy = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'BUY', 0, $user->btc_amount);
                            if(array_key_exists('child_order_acceptance_id',$bitflyer_buy) &&
                                $bitflyer_buy['child_order_acceptance_id']) {
                                Log::info('user_id:' . $user->id . ';bitflyer買い成功');
                                //kucoinで売り
                                $kucoin_order = new KucoinOrder($kucoin_auth);
                                $order_param = [
                                    'clientOid' => uniqid(),
                                    'size'      => $user->btc_amount,
                                    'symbol'    => 'BTC-USDT',
                                    'type'      => 'market',
                                    'side'      => 'buy'
                                ];
                                $kucoinsell_result = $kucoin_order->create($order_param);
                                if(array_key_exists('orderId',$kucoinsell_result)){
                                    Log::info('user_id:' . $user->id . ';kucoin売り成功');
                                    //bitflyerで買い、gateで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitflyer';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'kucoin';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //kucoin売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $bitflyer_sell = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'SELL', 0, $user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitflyer';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'kucoin';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_kucoin_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';kucoin売り失敗');
                                }
                            }

                        }
                        elseif($best_bid == 'mexc' && !empty($mexc)){
                            //mexc BTC 残高確認
                            $mexc_balances = Common::get_mexc_allbalances($mexc);
                            $mexc_btc = $mexc_balances['BTC'];
                            if($mexc_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';mexc btc残高足りない;');
                                continue;
                            }
                            //bitflyerで買い
                            $bitflyer_buy = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'BUY', 0, $user->btc_amount);
                            if(array_key_exists('child_order_acceptance_id',$bitflyer_buy) &&
                                $bitflyer_buy['child_order_acceptance_id']) {
                                Log::info('user_id:' . $user->id . ';bitflyer買い成功');
                                //mexcで売り
                                $mexc_sell_result = $mexc->order()->postPlace([
                                    'symbol'=>'BTC_USDT',
                                    'price'=> round($best_bid_value/$usdjpy_bid - 100),
                                    'quantity'=>$user->btc_amount,
                                    'trade_type'=>'ASK',
                                    'order_type'=>'IMMEDIATE_OR_CANCEL'
                                ]);
                                if(array_key_exists('code',$mexc_sell_result) && $mexc_sell_result['code'] == 200){
                                    Log::info('user_id:' . $user->id . ';mexc売り成功');
                                    //bitflyerで買い、mexcで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitflyer';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'mexc';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //kucoin売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $bitflyer_sell = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'SELL', 0, $user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitflyer';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'mexc';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_mexc_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';mexc売り失敗');
                                }
                            }

                        }
                        elseif($best_bid == 'bitget' && !empty($bitget)){
                            //bitget BTC 残高確認
                            $bitget_balances = $bitget->get_balance_all();
                            $bitget_btc = $bitget_balances['btc'];
                            if($bitget_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';bitget btc残高足りない;');
                                continue;
                            }
                            //bitflyerで買い
                            $bitflyer_buy = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'BUY', 0, $user->btc_amount);
                            if(array_key_exists('child_order_acceptance_id',$bitflyer_buy) &&
                                $bitflyer_buy['child_order_acceptance_id']) {
                                Log::info('user_id:' . $user->id . ';bitflyer買い成功');
                                //bitgetで売り
                                $bitget_sell_result = $bitget->order('BTCUSDT_SPBL', 'sell', $user->btc_amount);
                                if(is_array($bitget_sell_result) && array_key_exists('data',$bitget_sell_result) &&
                                    is_array($bitget_sell_result['data']) && array_key_exists('orderId',$bitget_sell_result['data'])){
                                    Log::info('user_id:' . $user->id . ';bitget売り成功');
                                    //bitflyerで買い、mexcで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitflyer';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitget';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //kucoin売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $bitflyer_sell = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'SELL', 0, $user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitflyer';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitget';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_bitget_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';bitget売り失敗');
                                }
                            }

                        }
                    }
                    elseif ($best_ask == 'coincheck' && !empty($coincheck)){
                        //coincheck日本円残高確認
                        $coincheck_balance_jpy = $coincheck->get_balance('jpy');
                        if($coincheck_balance_jpy < $best_ask_value*$user->btc_amount * 1.1){
                            Log::info('user_id:'.$user->id.';coincheck 日本円残高足りない;');
                            continue;
                        }
                        if($best_bid == 'bitflyer' && !empty($bitflyer)){
                            $bitflyer_balance_btc = Common::getBitflyerBalance($bitflyer, 'BTC');
                            if($bitflyer_balance_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';bitflyer btc残高足りない;');
                                continue;
                            }
                            //coincheck買い
                            $coincheck_result = $coincheck->order(array(
                                "market_buy_amount" => round($user->btc_amount * $best_ask_value),
                                "order_type" => "market_buy",
                                "pair" => "btc_jpy"
                            ));
                            if($coincheck_result && array_key_exists('success',$coincheck_result) &&
                                $coincheck_result['success']){
                                Log::info('user_id:'.$user->id.';coincheck買い成功');
                                //bitflyerで売り
                                $bitflyer_sell = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'SELL', 0, $user->btc_amount);
                                if(array_key_exists('child_order_acceptance_id',$bitflyer_sell) &&
                                    $bitflyer_sell['child_order_acceptance_id']){
                                    Log::info('user_id:'.$user->id.';bitflyer売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'coincheck';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitflyer';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                }else{
                                    //bitflyer売り失敗、ロールバック
                                    if($user->rollback=='on'){
                                        $coincheck_result = $coincheck->order(array(
                                            "amount" => $user->btc_amount,
                                            "order_type" => "market_sell",
                                            "pair" => "btc_jpy"
                                        ));
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'coincheck';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitflyer';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_bitflyer_auto = 0;
                                    $user->save();
                                    Log::info('user_id:'.$user->id.';bitflyer売り失敗');
                                }
                            }
                        }
                        elseif($best_bid == 'bitbank' && !empty($bitbank)){
                            //bitbank BTC 残高確認
                            $bitbank_balance_btc = $bitbank->get_balance('btc');
                            if($bitbank_balance_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';bitbank btc残高足りない;');
                                continue;
                            }
                            //coincheck買い
                            $coincheck_result = $coincheck->order(array(
                                "market_buy_amount" => round($user->btc_amount * $best_ask_value),
                                "order_type" => "market_buy",
                                "pair" => "btc_jpy"
                            ));
                            if($coincheck_result && array_key_exists('success',$coincheck_result) &&
                                $coincheck_result['success']){
                                Log::info('user_id:'.$user->id.';coincheck買い成功');
                                //bitbank売り
                                $bitbank_result = $bitbank->order('btc_jpy','sell',$user->btc_amount);
                                if(array_key_exists('order_id', $bitbank_result['data'])) {
                                    Log::info('user_id:' . $user->id . ';bitbank売り成功');
                                    //bitflyerで買い、bitbankで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'coincheck';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitbank';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                }else{
                                    //bitbank売り失敗、ロールバック
                                    if($user->rollback=='on'){
                                        $coincheck_result = $coincheck->order(array(
                                            "amount" => $user->btc_amount,
                                            "order_type" => "market_sell",
                                            "pair" => "btc_jpy"
                                        ));
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'coincheck';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitbank';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_bitbank_auto = 0;
                                    $user->save();
                                    Log::info('user_id:'.$user->id.';bitbank売り失敗');
                                }
                            }
                        }
                        elseif($best_bid == 'gmo' && !empty($gmo)){
                            //gmo BTC 残高確認
                            $gmo_balance_btc = $gmo->get_balance('BTC');
                            if($gmo_balance_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';gmo btc残高足りない;');
                                continue;
                            }
                            //coincheck買い
                            $coincheck_result = $coincheck->order(array(
                                "market_buy_amount" => round($user->btc_amount * $best_ask_value),
                                "order_type" => "market_buy",
                                "pair" => "btc_jpy"
                            ));
                            if($coincheck_result && array_key_exists('success',$coincheck_result) &&
                                $coincheck_result['success']){
                                Log::info('user_id:'.$user->id.';coincheck買い成功');
                                //GMO売り
                                usleep(100000);
                                $gmo_result = $gmo->order('BTC','SELL',$user->btc_amount);
                                if(array_key_exists('data', $gmo_result) && array_key_exists('status', $gmo_result) && $gmo_result['status'] == '0'){
                                    Log::info('user_id:'.$user->id.';GMO売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'coincheck';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'gmo';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                }else{
                                    //gmo売り失敗、ロールバック
                                    if($user->rollback=='on'){
                                        $coincheck_result = $coincheck->order(array(
                                            "amount" => $user->btc_amount,
                                            "order_type" => "market_sell",
                                            "pair" => "btc_jpy"
                                        ));
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'coincheck';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'gmo';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_gmo_auto = 0;
                                    $user->save();
                                    Log::info('user_id:'.$user->id.';gmo売り失敗');
                                }
                            }
                        }
                        elseif($best_bid == 'gate' && !empty($gate)){
                            //gate BTC 残高確認
                            $gate_btc = $gate->get_balance('BTC');
                            if($gate_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';gate btc残高足りない;');
                                continue;
                            }
                            //coincheck買い
                            $coincheck_result = $coincheck->order(array(
                                "market_buy_amount" => round($user->btc_amount * $best_ask_value),
                                "order_type" => "market_buy",
                                "pair" => "btc_jpy"
                            ));
                            if($coincheck_result && array_key_exists('success',$coincheck_result) &&
                                $coincheck_result['success']){
                                Log::info('user_id:'.$user->id.';coincheck買い成功');
                                //gateで売り
                                $gate_result = $gate->order('BTC_USDT','sell',$user->btc_amount);
                                if(array_key_exists('id',$gate_result)){
                                    Log::info('user_id:' . $user->id . ';gate売り成功');
                                    //bitflyerで買い、gateで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'coincheck';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'gate';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //gate売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $coincheck_result = $coincheck->order(array(
                                            "amount" => $user->btc_amount,
                                            "order_type" => "market_sell",
                                            "pair" => "btc_jpy"
                                        ));
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'coincheck';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'gate';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_gate_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';gate売り失敗');
                                }
                            }

                        }
                        elseif($best_bid == 'kucoin' && !empty($kucoin) && !empty($kucoin_auth)){
                            //kucoin BTC 残高確認
                            $kucoin_btc = Common::getKucoinBalance($kucoin, 'BTC');
                            if($kucoin_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';kucoin btc残高足りない;');
                                continue;
                            }
                            //coincheck買い
                            $coincheck_result = $coincheck->order(array(
                                "market_buy_amount" => round($user->btc_amount * $best_ask_value),
                                "order_type" => "market_buy",
                                "pair" => "btc_jpy"
                            ));
                            if($coincheck_result && array_key_exists('success',$coincheck_result) &&
                                $coincheck_result['success']){
                                Log::info('user_id:'.$user->id.';coincheck買い成功');
                                //kucoinで売り
                                $kucoin_order = new KucoinOrder($kucoin_auth);
                                $order_param = [
                                    'clientOid' => uniqid(),
                                    'size'      => $user->btc_amount,
                                    'symbol'    => 'BTC-USDT',
                                    'type'      => 'market',
                                    'side'      => 'buy'
                                ];
                                $kucoinsell_result = $kucoin_order->create($order_param);
                                if(array_key_exists('orderId',$kucoinsell_result)){
                                    Log::info('user_id:' . $user->id . ';kucoin売り成功');
                                    //bitflyerで買い、gateで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'coincheck';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'kucoin';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //kucoin売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $coincheck_result = $coincheck->order(array(
                                            "amount" => $user->btc_amount,
                                            "order_type" => "market_sell",
                                            "pair" => "btc_jpy"
                                        ));
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'coincheck';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'kucoin';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_kucoin_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';kucoin売り失敗');
                                }
                            }

                        }
                        elseif($best_bid == 'mexc' && !empty($mexc)){
                            //mexc BTC 残高確認
                            $mexc_balances = Common::get_mexc_allbalances($mexc);
                            $mexc_btc = $mexc_balances['BTC'];
                            if($mexc_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';mexc btc残高足りない;');
                                continue;
                            }
                            //coincheck買い
                            $coincheck_result = $coincheck->order(array(
                                "market_buy_amount" => round($user->btc_amount * $best_ask_value),
                                "order_type" => "market_buy",
                                "pair" => "btc_jpy"
                            ));
                            if($coincheck_result && array_key_exists('success',$coincheck_result) &&
                                $coincheck_result['success']){
                                Log::info('user_id:'.$user->id.';coincheck買い成功');
                                //mexcで売り
                                $mexc_sell_result = $mexc->order()->postPlace([
                                    'symbol'=>'BTC_USDT',
                                    'price'=> round($best_bid_value/$usdjpy_bid - 100),
                                    'quantity'=>$user->btc_amount,
                                    'trade_type'=>'ASK',
                                    'order_type'=>'IMMEDIATE_OR_CANCEL'
                                ]);
                                if(array_key_exists('code',$mexc_sell_result) && $mexc_sell_result['code'] == 200){
                                    Log::info('user_id:' . $user->id . ';mexc売り成功');
                                    //coincheckで買い、mexcで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'coincheck';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'mexc';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //kucoin売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $coincheck_result = $coincheck->order(array(
                                            "amount" => $user->btc_amount,
                                            "order_type" => "market_sell",
                                            "pair" => "btc_jpy"
                                        ));
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'coincheck';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'mexc';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_mexc_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';mexc売り失敗');
                                }
                            }

                        }
                        elseif($best_bid == 'bitget' && !empty($bitget)){
                            //bitget BTC 残高確認
                            $bitget_balances = $bitget->get_balance_all();
                            $bitget_btc = $bitget_balances['btc'];
                            if($bitget_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';bitget btc残高足りない;');
                                continue;
                            }
                            //coincheck買い
                            $coincheck_result = $coincheck->order(array(
                                "market_buy_amount" => round($user->btc_amount * $best_ask_value),
                                "order_type" => "market_buy",
                                "pair" => "btc_jpy"
                            ));
                            if($coincheck_result && array_key_exists('success',$coincheck_result) &&
                                $coincheck_result['success']){
                                Log::info('user_id:'.$user->id.';coincheck買い成功');
                                //bitgetで売り
                                $bitget_sell_result = $bitget->order('BTCUSDT_SPBL', 'sell', $user->btc_amount);
                                if(is_array($bitget_sell_result) && array_key_exists('data',$bitget_sell_result) &&
                                    is_array($bitget_sell_result['data']) && array_key_exists('orderId',$bitget_sell_result['data'])){
                                    Log::info('user_id:' . $user->id . ';bitget売り成功');
                                    //coincheckで買い、mexcで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'coincheck';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitget';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //kucoin売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $coincheck_result = $coincheck->order(array(
                                            "amount" => $user->btc_amount,
                                            "order_type" => "market_sell",
                                            "pair" => "btc_jpy"
                                        ));
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'coincheck';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitget';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_bitget_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';bitget売り失敗');
                                }
                            }

                        }
                    }
                    elseif ($best_ask == 'bitbank' && !empty($bitbank)){
                        //bitbank日本円残高確認
                        $bitbank_balance_jpy = $bitbank->get_balance('jpy');
                        if($bitbank_balance_jpy < $best_ask_value*$user->btc_amount * 1.1){
                            Log::info('user_id:'.$user->id.';bitbank 日本円残高足りない;');
                            continue;
                        }
                        if($best_bid == 'coincheck' && !empty($coincheck)){
                            //coincheck btc残高確認
                            $coincheck_balance_btc = $coincheck->get_balance('btc');
                            if($coincheck_balance_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';coincheck btc残高足りない;');
                                continue;
                            }
                            //bitbank買い
                            $bitbank_result = $bitbank->order('btc_jpy','buy',$user->btc_amount);
                            if(array_key_exists('order_id', $bitbank_result['data'])) {
                                Log::info('user_id:' . $user->id . ';bitbank買い成功');
                                //coincheck売り
                                $coincheck_result = $coincheck->order(array(
                                    "amount" => $user->btc_amount,
                                    "order_type" => "market_sell",
                                    "pair" => "btc_jpy"
                                ));
                                if($coincheck_result && array_key_exists('success',$coincheck_result) &&
                                    $coincheck_result['success']){
                                    Log::info('user_id:'.$user->id.';coincheck売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitbank';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'coincheck';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                }else{
                                    //coincheck売り失敗、ロールバック
                                    if($user->rollback=='on'){
                                        $bitbank_result = $bitbank->order('btc_jpy','sell',$user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitbank';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'coincheck';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_coincheck_auto = 0;
                                    $user->save();
                                    Log::info('user_id:'.$user->id.';coincheck売り失敗');
                                }
                            }
                        }
                        elseif($best_bid == 'bitflyer' && !empty($bitflyer)){
                            //bitflyer btc残高確認
                            $bitflyer_balance_btc = Common::getBitflyerBalance($bitflyer, 'BTC');
                            if($bitflyer_balance_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';bitflyer btc残高足りない;');
                                continue;
                            }
                            //bitbank買い
                            $bitbank_result = $bitbank->order('btc_jpy','buy',$user->btc_amount);
                            if(array_key_exists('order_id', $bitbank_result['data'])) {
                                Log::info('user_id:' . $user->id . ';bitbank買い成功');
                                //bitflyerで売り
                                $bitflyer_sell = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'SELL', 0, $user->btc_amount);
                                if(array_key_exists('child_order_acceptance_id',$bitflyer_sell) &&
                                    $bitflyer_sell['child_order_acceptance_id']){
                                    Log::info('user_id:'.$user->id.';bitflyer売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitbank';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitflyer';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                }else{
                                    //bitflyer売り失敗、ロールバック
                                    if($user->rollback=='on'){
                                        $bitbank_result = $bitbank->order('btc_jpy','sell',$user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitbank';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitflyer';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_bitflyer_auto = 0;
                                    $user->save();
                                    Log::info('user_id:'.$user->id.';bitflyer売り失敗');
                                }
                            }
                        }
                        elseif($best_bid == 'gmo' && !empty($gmo)){
                            //gmo BTC 残高確認
                            $gmo_balance_btc = $gmo->get_balance('BTC');
                            if($gmo_balance_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';gmo btc残高足りない;');
                                continue;
                            }
                            //bitbank買い
                            $bitbank_result = $bitbank->order('btc_jpy','buy',$user->btc_amount);
                            if(array_key_exists('order_id', $bitbank_result['data'])) {
                                Log::info('user_id:' . $user->id . ';bitbank買い成功');
                                //GMO売り
                                usleep(100000);
                                $gmo_result = $gmo->order('BTC','SELL',$user->btc_amount);
                                if(array_key_exists('data', $gmo_result) && array_key_exists('status', $gmo_result) && $gmo_result['status'] == '0') {
                                    Log::info('user_id:' . $user->id . ';GMO売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitbank';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'gmo';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                }else{

                                    //gmo売り失敗、ロールバック
                                    if($user->rollback=='on'){
                                        $bitbank_result = $bitbank->order('btc_jpy','sell',$user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitbank';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'gmo';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_gmo_auto = 0;
                                    $user->save();
                                    Log::info('user_id:'.$user->id.';gmo売り失敗');
                                }
                            }
                        }
                        elseif($best_bid == 'gate' && !empty($gate)){
                            //gate BTC 残高確認
                            $gate_btc = $gate->get_balance('BTC');
                            if($gate_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';gate btc残高足りない;');
                                continue;
                            }
                            //bitbank買い
                            $bitbank_result = $bitbank->order('btc_jpy','buy',$user->btc_amount);
                            if(array_key_exists('order_id', $bitbank_result['data'])) {
                                Log::info('user_id:' . $user->id . ';bitbank買い成功');
                                //gateで売り
                                $gate_result = $gate->order('BTC_USDT','sell',$user->btc_amount);
                                if(array_key_exists('id',$gate_result)){
                                    Log::info('user_id:' . $user->id . ';gate売り成功');
                                    //bitflyerで買い、gateで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitbank';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'gate';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //gate売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $bitbank_result = $bitbank->order('btc_jpy','sell',$user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitbank';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'gate';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_gate_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';gate売り失敗');
                                }
                            }

                        }
                        elseif($best_bid == 'kucoin' && !empty($kucoin) && !empty($kucoin_auth)){
                            //kucoin BTC 残高確認
                            $kucoin_btc = Common::getKucoinBalance($kucoin, 'BTC');
                            if($kucoin_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';kucoin btc残高足りない;');
                                continue;
                            }
                            //bitbank買い
                            $bitbank_result = $bitbank->order('btc_jpy','buy',$user->btc_amount);
                            if(array_key_exists('order_id', $bitbank_result['data'])) {
                                Log::info('user_id:' . $user->id . ';bitbank買い成功');
                                //kucoinで売り
                                $kucoin_order = new KucoinOrder($kucoin_auth);
                                $order_param = [
                                    'clientOid' => uniqid(),
                                    'size'      => $user->btc_amount,
                                    'symbol'    => 'BTC-USDT',
                                    'type'      => 'market',
                                    'side'      => 'buy'
                                ];
                                $kucoinsell_result = $kucoin_order->create($order_param);
                                if(array_key_exists('orderId',$kucoinsell_result)){
                                    Log::info('user_id:' . $user->id . ';kucoin売り成功');
                                    //bitflyerで買い、gateで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitbank';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'kucoin';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //kucoin売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $bitbank_result = $bitbank->order('btc_jpy','sell',$user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitbank';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'kucoin';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_kucoin_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';kucoin売り失敗');
                                }
                            }

                        }
                        elseif($best_bid == 'mexc' && !empty($mexc)){
                            //mexc BTC 残高確認
                            $mexc_balances = Common::get_mexc_allbalances($mexc);
                            $mexc_btc = $mexc_balances['BTC'];
                            if($mexc_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';mexc btc残高足りない;');
                                continue;
                            }
                            //bitbank買い
                            $bitbank_result = $bitbank->order('btc_jpy','buy',$user->btc_amount);
                            if(array_key_exists('order_id', $bitbank_result['data'])) {
                                Log::info('user_id:' . $user->id . ';bitbank買い成功');
                                //mexcで売り
                                $mexc_sell_result = $mexc->order()->postPlace([
                                    'symbol'=>'BTC_USDT',
                                    'price'=> round($best_bid_value/$usdjpy_bid - 100),
                                    'quantity'=>$user->btc_amount,
                                    'trade_type'=>'ASK',
                                    'order_type'=>'IMMEDIATE_OR_CANCEL'
                                ]);
                                if(array_key_exists('code',$mexc_sell_result) && $mexc_sell_result['code'] == 200){
                                    Log::info('user_id:' . $user->id . ';mexc売り成功');
                                    //bitbankで買い、mexcで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitbank';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'mexc';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //mexc売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $bitbank_result = $bitbank->order('btc_jpy','sell',$user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitbank';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'mexc';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_mexc_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';mexc売り失敗');
                                }
                            }

                        }
                        elseif($best_bid == 'bitget' && !empty($bitget)){
                            //bitget BTC 残高確認
                            $bitget_balances = $bitget->get_balance_all();
                            $bitget_btc = $bitget_balances['btc'];
                            if($bitget_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';bitget btc残高足りない;');
                                continue;
                            }
                            //bitbank買い
                            $bitbank_result = $bitbank->order('btc_jpy','buy',$user->btc_amount);
                            if(array_key_exists('order_id', $bitbank_result['data'])) {
                                Log::info('user_id:' . $user->id . ';bitbank買い成功');
                                //bitgetで売り
                                $bitget_sell_result = $bitget->order('BTCUSDT_SPBL', 'sell', $user->btc_amount);
                                if(is_array($bitget_sell_result) && array_key_exists('data',$bitget_sell_result) &&
                                    is_array($bitget_sell_result['data']) && array_key_exists('orderId',$bitget_sell_result['data'])){
                                    Log::info('user_id:' . $user->id . ';bitget売り成功');
                                    //bitbankで買い、mexcで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitbank';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitget';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //kucoin売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $bitbank_result = $bitbank->order('btc_jpy','sell',$user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitbank';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitget';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_bitget_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';bitget売り失敗');
                                }
                            }

                        }
                    }
                    elseif ($best_ask == 'gmo' && !empty($gmo)){
                        //GMO日本円残高確認
                        $gmo_balance_jpy = $gmo->get_balance('JPY');
                        if($gmo_balance_jpy < $best_ask_value*$user->btc_amount * 1.1){
                            Log::info('user_id:'.$user->id.';gmo 日本円残高足りない;');
                            continue;
                        }
                        if($best_bid == 'coincheck' && !empty($coincheck)){
                            //coincheck BTC 残高確認
                            $coincheck_balance_btc = $coincheck->get_balance('btc');
                            if($coincheck_balance_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';coincheck btc残高足りない;');
                                continue;
                            }
                            //gmo買い
                            usleep(100000);
                            $gmo_result = $gmo->order('BTC','BUY',$user->btc_amount);
                            if(array_key_exists('data', $gmo_result) && array_key_exists('status', $gmo_result) && $gmo_result['status'] == '0'){
                                Log::info('user_id:'.$user->id.';GMO買い成功');
                                //coincheck売り
                                $coincheck_result = $coincheck->order(array(
                                    "amount" => $user->btc_amount,
                                    "order_type" => "market_sell",
                                    "pair" => "btc_jpy"
                                ));
                                if($coincheck_result && array_key_exists('success',$coincheck_result) &&
                                    $coincheck_result['success']){
                                    Log::info('user_id:'.$user->id.';coincheck売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gmo';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'coincheck';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                }else{
                                    //coincheck売り失敗、ロールバック
                                    if($user->rollback=='on'){
                                        usleep(100000);
                                        $gmo_result = $gmo->order('BTC','SELL',$user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gmo';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'coincheck';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_coincheck_auto = 0;
                                    $user->save();
                                    Log::info('user_id:'.$user->id.';coincheck売り失敗');
                                }
                            }
                        }
                        elseif($best_bid == 'bitflyer' && !empty($bitflyer)){
                            //bitflyer BTC 残高確認
                            $bitflyer_balance_btc = Common::getBitflyerBalance($bitflyer, 'BTC');
                            if($bitflyer_balance_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';bitflyer btc残高足りない;');
                                continue;
                            }
                            //gmo買い
                            usleep(100000);
                            $gmo_result = $gmo->order('BTC','BUY',$user->btc_amount);
                            if(array_key_exists('data', $gmo_result) && array_key_exists('status', $gmo_result) && $gmo_result['status'] == '0'){
                                Log::info('user_id:'.$user->id.';GMO買い成功');
                                //bitflyerで売り
                                $bitflyer_sell = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'SELL', 0, $user->btc_amount);
                                if(array_key_exists('child_order_acceptance_id',$bitflyer_sell) &&
                                    $bitflyer_sell['child_order_acceptance_id']){
                                    Log::info('user_id:'.$user->id.';bitflyer売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gmo';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitflyer';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                }else{
                                    //coincheck売り失敗、ロールバック
                                    if($user->rollback=='on'){
                                        usleep(100000);
                                        $gmo_result = $gmo->order('BTC','SELL',$user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gmo';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitflyer';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_bitflyer_auto = 0;
                                    $user->save();
                                    Log::info('user_id:'.$user->id.';bitflyer売り失敗');
                                }
                            }
                        }
                        elseif($best_bid == 'bitbank' && !empty($bitbank)){
                            //bitbank BTC 残高確認
                            $bitbank_balance_btc = $bitbank->get_balance('btc');
                            if($bitbank_balance_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';bitbank btc残高足りない;');
                                continue;
                            }
                            //gmo買い
                            usleep(100000);
                            $gmo_result = $gmo->order('BTC','BUY',$user->btc_amount);
                            if(array_key_exists('data', $gmo_result) && array_key_exists('status', $gmo_result) && $gmo_result['status'] == '0'){
                                Log::info('user_id:'.$user->id.';GMO買い成功');
                                //bitbank売り
                                $bitbank_result = $bitbank->order('btc_jpy','sell',$user->btc_amount);
                                if(array_key_exists('order_id', $bitbank_result['data'])) {
                                    Log::info('user_id:' . $user->id . ';bitbank売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gmo';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitbank';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                }else{
                                    //bitbank売り失敗、ロールバック
                                    if($user->rollback == 'on'){
                                        usleep(100000);
                                        $gmo_result = $gmo->order('BTC','SELL',$user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gmo';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitbank';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_bitbank_auto = 0;
                                    $user->save();
                                    Log::info('user_id:'.$user->id.';bitbank売り失敗');
                                }
                            }
                        }
                        elseif($best_bid == 'gate' && !empty($gate)){
                            //gate BTC 残高確認
                            $gate_btc = $gate->get_balance('BTC');
                            if($gate_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';gate btc残高足りない;');
                                continue;
                            }
                            usleep(100000);
                            $gmo_result = $gmo->order('BTC','BUY',$user->btc_amount);
                            if(array_key_exists('data', $gmo_result) && array_key_exists('status', $gmo_result) && $gmo_result['status'] == '0'){
                                Log::info('user_id:'.$user->id.';GMO買い成功');
                                //gateで売り
                                $gate_result = $gate->order('BTC_USDT','sell',$user->btc_amount);
                                if(array_key_exists('id',$gate_result)){
                                    Log::info('user_id:' . $user->id . ';gate売り成功');
                                    //bitflyerで買い、gateで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gmo';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'gate';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //gate売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        usleep(100000);
                                        $gmo_result = $gmo->order('BTC','SELL',$user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gmo';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'gate';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_gate_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';gate売り失敗');
                                }
                            }

                        }
                        elseif($best_bid == 'kucoin' && !empty($kucoin) && !empty($kucoin_auth)){
                            //kucoin BTC 残高確認
                            $kucoin_btc = Common::getKucoinBalance($kucoin, 'BTC');
                            if($kucoin_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';kucoin btc残高足りない;');
                                continue;
                            }
                            usleep(100000);
                            $gmo_result = $gmo->order('BTC','BUY',$user->btc_amount);
                            if(array_key_exists('data', $gmo_result) && array_key_exists('status', $gmo_result) && $gmo_result['status'] == '0'){
                                Log::info('user_id:'.$user->id.';GMO買い成功');
                                //kucoinで売り
                                $kucoin_order = new KucoinOrder($kucoin_auth);
                                $order_param = [
                                    'clientOid' => uniqid(),
                                    'size'      => $user->btc_amount,
                                    'symbol'    => 'BTC-USDT',
                                    'type'      => 'market',
                                    'side'      => 'buy'
                                ];
                                $kucoinsell_result = $kucoin_order->create($order_param);
                                if(array_key_exists('orderId',$kucoinsell_result)){
                                    Log::info('user_id:' . $user->id . ';kucoin売り成功');
                                    //gmoで買い、gateで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gmo';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'kucoin';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //kucoin売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        usleep(100000);
                                        $gmo_result = $gmo->order('BTC','SELL',$user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gmo';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'kucoin';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_kucoin_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';kucoin売り失敗');
                                }
                            }

                        }
                        elseif($best_bid == 'mexc' && !empty($mexc)){
                            //mexc BTC 残高確認
                            $mexc_balances = Common::get_mexc_allbalances($mexc);
                            $mexc_btc = $mexc_balances['BTC'];
                            if($mexc_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';mexc btc残高足りない;');
                                continue;
                            }
                            usleep(100000);
                            $gmo_result = $gmo->order('BTC','BUY',$user->btc_amount);
                            if(array_key_exists('data', $gmo_result) && array_key_exists('status', $gmo_result) && $gmo_result['status'] == '0'){
                                Log::info('user_id:'.$user->id.';GMO買い成功');
                                //mexcで売り
                                $mexc_sell_result = $mexc->order()->postPlace([
                                    'symbol'=>'BTC_USDT',
                                    'price'=> round($best_bid_value/$usdjpy_bid - 100),
                                    'quantity'=>$user->btc_amount,
                                    'trade_type'=>'ASK',
                                    'order_type'=>'IMMEDIATE_OR_CANCEL'
                                ]);
                                if(array_key_exists('code',$mexc_sell_result) && $mexc_sell_result['code'] == 200){
                                    Log::info('user_id:' . $user->id . ';mexc売り成功');
                                    //gmoで買い、mexcで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gmo';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'mexc';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //mexc売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        usleep(100000);
                                        $gmo_result = $gmo->order('BTC','SELL',$user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gmo';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'mexc';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_mexc_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';mexc売り失敗');
                                }
                            }

                        }
                        elseif($best_bid == 'bitget' && !empty($bitget)){
                            //bitget BTC 残高確認
                            $bitget_balances = $bitget->get_balance_all();
                            $bitget_btc = $bitget_balances['btc'];
                            if($bitget_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';bitget btc残高足りない;');
                                continue;
                            }
                            usleep(100000);
                            $gmo_result = $gmo->order('BTC','BUY',$user->btc_amount);
                            if(array_key_exists('data', $gmo_result) && array_key_exists('status', $gmo_result) && $gmo_result['status'] == '0'){
                                Log::info('user_id:'.$user->id.';GMO買い成功');
                                //bitgetで売り
                                $bitget_sell_result = $bitget->order('BTCUSDT_SPBL', 'sell', $user->btc_amount);
                                if(is_array($bitget_sell_result) && array_key_exists('data',$bitget_sell_result) &&
                                    is_array($bitget_sell_result['data']) && array_key_exists('orderId',$bitget_sell_result['data'])){
                                    Log::info('user_id:' . $user->id . ';bitget売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gmo';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitget';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //kucoin売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        usleep(100000);
                                        $gmo_result = $gmo->order('BTC','SELL',$user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gmo';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitget';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_bitget_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';bitget売り失敗');
                                }
                            }

                        }
                    }
                    elseif ($best_ask == 'gate' && !empty($gate)){
                        //gate 残高確認
                        $gate_usdt = $gate->get_balance('USDT');
                        $jpy_balance = floatval($gate_usdt * $usdjpy->ask);
                        $buy_usdt = round($user->btc_amount * $best_ask_value / $usdjpy->bid);
                        if($jpy_balance < $best_ask_value*$user->btc_amount * 1.1){
                            Log::info('user_id:'.$user->id.';gate usdt残高足りない;');
                            continue;
                        }
                        if($best_bid == 'coincheck' && !empty($coincheck)){
                            //coincheck BTC 残高確認
                            $coincheck_balance_btc = $coincheck->get_balance('btc');
                            if($coincheck_balance_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';coincheck btc残高足りない;');
                                continue;
                            }
                            //gate買い
                            $gate_result = $gate->order('BTC_USDT','buy', $buy_usdt);
                            if(array_key_exists('filled_amount',$gate_result) && $gate_result['filled_amount'] >0 ){
                                Log::info('user_id:'.$user->id.';gate買い成功');
                                //coincheck売り
                                $coincheck_result = $coincheck->order(array(
                                    "amount" => $user->btc_amount,
                                    "order_type" => "market_sell",
                                    "pair" => "btc_jpy"
                                ));
                                if($coincheck_result && array_key_exists('success',$coincheck_result) &&
                                    $coincheck_result['success']){
                                    Log::info('user_id:'.$user->id.';coincheck売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gate';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'coincheck';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                }
                                else {
                                    //coincheck売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $gate_result = $gate->order('BTC_USDT', 'sell', $user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gate';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'coincheck';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_coincheck_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';coincheck売り失敗');
                                }
                            }
                        }
                        elseif ($best_bid == 'bitflyer' && !empty($bitflyer)) {
                            //bitflyer BTC 残高確認
                            $bitflyer_balance_btc = Common::getBitflyerBalance($bitflyer, 'BTC');
                            if ($bitflyer_balance_btc < $user->btc_amount) {
                                Log::info('user_id:' . $user->id . ';bitflyer btc残高足りない;');
                                continue;
                            }
                            //gate買い
                            $gate_result = $gate->order('BTC_USDT', 'buy', $buy_usdt);
                            if (array_key_exists('filled_amount', $gate_result) && $gate_result['filled_amount'] > 0) {
                                Log::info('user_id:' . $user->id . ';gate買い成功');
                                //bitflyerで売り
                                $bitflyer_sell = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'SELL', 0, $user->btc_amount);
                                if (array_key_exists('child_order_acceptance_id', $bitflyer_sell) &&
                                    $bitflyer_sell['child_order_acceptance_id']) {
                                    Log::info('user_id:' . $user->id . ';bitflyer売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gate';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitflyer';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //coincheck売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $gate_result = $gate->order('BTC_USDT', 'sell', $user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gate';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitflyer';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_bitflyer_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';bitflyer売り失敗');
                                }
                            }
                        }
                        elseif ($best_bid == 'bitbank' && !empty($bitbank)) {
                            //bitbank BTC 残高確認
                            $bitbank_balance_btc = $bitbank->get_balance('btc');
                            if ($bitbank_balance_btc < $user->btc_amount) {
                                Log::info('user_id:' . $user->id . ';bitbank btc残高足りない;');
                                continue;
                            }
                            //gate買い
                            $gate_result = $gate->order('BTC_USDT', 'buy', $buy_usdt);
                            if (array_key_exists('filled_amount', $gate_result) && $gate_result['filled_amount'] > 0) {
                                Log::info('user_id:' . $user->id . ';gate買い成功');
                                //bitbank売り
                                $bitbank_result = $bitbank->order('btc_jpy', 'sell', $user->btc_amount);
                                if (array_key_exists('order_id', $bitbank_result['data'])) {
                                    Log::info('user_id:' . $user->id . ';bitbank売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gate';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitbank';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                }
                                else {
                                    //bitbank売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $gate_result = $gate->order('BTC_USDT', 'sell', $user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gate';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitbank';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_bitbank_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';bitbank売り失敗');
                                }
                            }
                        }
                        elseif($best_bid == 'kucoin' && !empty($kucoin) && !empty($kucoin_auth)){
                            //kucoin BTC 残高確認
                            $kucoin_btc = Common::getKucoinBalance($kucoin, 'BTC');
                            if($kucoin_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';kucoin btc残高足りない;');
                                continue;
                            }
                            //gate買い
                            $gate_result = $gate->order('BTC_USDT', 'buy', $buy_usdt);
                            if (array_key_exists('filled_amount', $gate_result) && $gate_result['filled_amount'] > 0) {
                                Log::info('user_id:' . $user->id . ';gate買い成功');
                                //kucoinで売り
                                $kucoin_order = new KucoinOrder($kucoin_auth);
                                $order_param = [
                                    'clientOid' => uniqid(),
                                    'size'      => $user->btc_amount,
                                    'symbol'    => 'BTC-USDT',
                                    'type'      => 'market',
                                    'side'      => 'sell'
                                ];
                                $kucoinsell_result = $kucoin_order->create($order_param);
                                if(array_key_exists('orderId',$kucoinsell_result)){
                                    Log::info('user_id:' . $user->id . ';kucoin売り成功');
                                    //gateで買い、kucoinで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gate';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'kucoin';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //kucoin売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $gate_result = $gate->order('BTC_USDT', 'sell', $user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gate';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'kucoin';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_kucoin_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';kucoin売り失敗');
                                }
                            }
                        }
                        elseif ($best_bid == 'mexc' && !empty($mexc)){
                            //mexc BTC 残高確認
                            $mexc_balances = Common::get_mexc_allbalances($mexc);
                            $mexc_btc = $mexc_balances['BTC'];
                            if($mexc_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';mexc btc残高足りない;');
                                continue;
                            }
                            usleep(100000);
                            //gate買い
                            $gate_result = $gate->order('BTC_USDT', 'buy', $buy_usdt);
                            if (array_key_exists('filled_amount', $gate_result) && $gate_result['filled_amount'] > 0) {
                                Log::info('user_id:' . $user->id . ';gate買い成功');
                                //mexcで売り
                                $mexc_sell_result = $mexc->order()->postPlace([
                                    'symbol'=>'BTC_USDT',
                                    'price'=> round($best_bid_value/$usdjpy_bid - 100),
                                    'quantity'=>$user->btc_amount,
                                    'trade_type'=>'ASK',
                                    'order_type'=>'IMMEDIATE_OR_CANCEL'
                                ]);
                                if(is_array($mexc_sell_result) && array_key_exists('code',$mexc_sell_result) &&
                                    $mexc_sell_result['code'] == 200){
                                    Log::info('user_id:' . $user->id . ';mexc売り成功');
                                    //gateで買い、mexcで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gate';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'mexc';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //mexc売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $gate_result = $gate->order('BTC_USDT', 'sell', $user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gate';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'mexc';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_mexc_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';mexc売り失敗');
                                }
                            }
                        }
                        elseif ($best_bid == 'bitget' && !empty($bitget)) {
                            //bitget BTC 残高確認
                            $bitget_balances = $bitget->get_balance_all();
                            $bitget_btc = $bitget_balances['btc'];
                            if ($bitget_btc < $user->btc_amount) {
                                Log::info('user_id:' . $user->id . ';bitget btc残高足りない;');
                                continue;
                            }
                            usleep(100000);
                            //gate買い
                            $gate_result = $gate->order('BTC_USDT', 'buy', $buy_usdt);
                            if (array_key_exists('filled_amount', $gate_result) && $gate_result['filled_amount'] > 0) {
                                Log::info('user_id:' . $user->id . ';gate買い成功');
                                //bitgetで売り
                                $bitget_sell_result = $bitget->order('BTCUSDT_SPBL', 'sell', $user->btc_amount);
                                if (is_array($bitget_sell_result) && array_key_exists('data', $bitget_sell_result) &&
                                    is_array($bitget_sell_result['data']) && array_key_exists('orderId', $bitget_sell_result['data'])) {
                                    Log::info('user_id:' . $user->id . ';bitget売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gate';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitget';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //kucoin売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $gate_result = $gate->order('BTC_USDT', 'sell', $user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc;
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gate';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitget';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_bitget_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';bitget売り失敗');
                                }
                            }
                        }
                    }
                    elseif ($best_ask == 'kucoin' && !empty($kucoin) && !empty($kucoin_auth)){
                        //kucoin 残高確認
                        $kucoin_usdt = Common::getKucoinBalance($kucoin, 'USDT');
                        $jpy_balance = floatval($kucoin_usdt * $usdjpy->ask);
                        if($jpy_balance < $best_ask_value*$user->btc_amount * 1.1){
                            Log::info('user_id:'.$user->id.';kucoin usdt残高足りない;');
                            continue;
                        }
                        if($best_bid == 'coincheck' && !empty($coincheck)){
                            //coincheck BTC 残高確認
                            $coincheck_balance_btc = $coincheck->get_balance('btc');
                            if($coincheck_balance_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';coincheck btc残高足りない;');
                                continue;
                            }
                            //kucoin買い
                            $kucoin_order = new KucoinOrder($kucoin_auth);
                            $order_param = [
                                'clientOid' => uniqid(),
                                'size'      => $user->btc_amount,
                                'symbol'    => 'BTC-USDT',
                                'type'      => 'market',
                                'side'      => 'buy'
                            ];
                            $kucoin_result = $kucoin_order->create($order_param);
                            if(array_key_exists('orderId',$kucoin_result)){
                                Log::info('user_id:'.$user->id.';kucoin買い成功');
                                //coincheck売り
                                $coincheck_result = $coincheck->order(array(
                                    "amount" => $user->btc_amount,
                                    "order_type" => "market_sell",
                                    "pair" => "btc_jpy"
                                ));
                                if($coincheck_result && array_key_exists('success',$coincheck_result) &&
                                    $coincheck_result['success']){
                                    Log::info('user_id:'.$user->id.';coincheck売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'kucoin';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'coincheck';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                }
                                else {
                                    //coincheck売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $order_param = [
                                            'clientOid' => uniqid(),
                                            'size'      => $user->btc_amount,
                                            'symbol'    => 'BTC-USDT',
                                            'type'      => 'market',
                                            'side'      => 'sell'
                                        ];
                                        $kucoin_result = $kucoin_order->create($order_param);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'kucoin';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'coincheck';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_coincheck_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';coincheck売り失敗');
                                }
                            }
                        }
                        elseif ($best_bid == 'bitflyer' && !empty($bitflyer)) {
                            //bitflyer BTC 残高確認
                            $bitflyer_balance_btc = Common::getBitflyerBalance($bitflyer, 'BTC');
                            if ($bitflyer_balance_btc < $user->btc_amount) {
                                Log::info('user_id:' . $user->id . ';bitflyer btc残高足りない;');
                                continue;
                            }
                            //kucoin買い
                            $kucoin_order = new KucoinOrder($kucoin_auth);
                            $order_param = [
                                'clientOid' => uniqid(),
                                'size'      => $user->btc_amount,
                                'symbol'    => 'BTC-USDT',
                                'type'      => 'market',
                                'side'      => 'buy'
                            ];
                            $kucoin_result = $kucoin_order->create($order_param);
                            if(array_key_exists('orderId',$kucoin_result)){
                                Log::info('user_id:' . $user->id . ';kucoin買い成功');
                                //bitflyerで売り
                                $bitflyer_sell = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'SELL', 0, $user->btc_amount);
                                if (array_key_exists('child_order_acceptance_id', $bitflyer_sell) &&
                                    $bitflyer_sell['child_order_acceptance_id']) {
                                    Log::info('user_id:' . $user->id . ';bitflyer売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'kucoin';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitflyer';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //coincheck売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $order_param = [
                                            'clientOid' => uniqid(),
                                            'size'      => $user->btc_amount,
                                            'symbol'    => 'BTC-USDT',
                                            'type'      => 'market',
                                            'side'      => 'sell'
                                        ];
                                        $kucoin_result = $kucoin_order->create($order_param);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'kucoin';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitflyer';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_bitflyer_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';bitflyer売り失敗');
                                }
                            }
                        }
                        elseif ($best_bid == 'bitbank' && !empty($bitbank)) {
                            //bitbank BTC 残高確認
                            $bitbank_balance_btc = $bitbank->get_balance('btc');
                            if ($bitbank_balance_btc < $user->btc_amount) {
                                Log::info('user_id:' . $user->id . ';bitbank btc残高足りない;');
                                continue;
                            }
                            //kucoin買い
                            $kucoin_order = new KucoinOrder($kucoin_auth);
                            $order_param = [
                                'clientOid' => uniqid(),
                                'size'      => $user->btc_amount,
                                'symbol'    => 'BTC-USDT',
                                'type'      => 'market',
                                'side'      => 'buy'
                            ];
                            $kucoin_result = $kucoin_order->create($order_param);
                            if(array_key_exists('orderId',$kucoin_result)){
                                Log::info('user_id:' . $user->id . ';kucoin買い成功');
                                //bitbank売り
                                $bitbank_result = $bitbank->order('btc_jpy', 'sell', $user->btc_amount);
                                if (array_key_exists('order_id', $bitbank_result['data'])) {
                                    Log::info('user_id:' . $user->id . ';bitbank売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'kucoin';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitbank';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                }
                                else {
                                    //bitbank売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $order_param = [
                                            'clientOid' => uniqid(),
                                            'size'      => $user->btc_amount,
                                            'symbol'    => 'BTC-USDT',
                                            'type'      => 'market',
                                            'side'      => 'sell'
                                        ];
                                        $kucoin_result = $kucoin_order->create($order_param);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'kucoin';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitbank';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_bitbank_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';bitbank売り失敗');
                                }
                            }
                        }
                        elseif($best_bid == 'gate' && !empty($gate)){
                            //gate BTC 残高確認
                            $gate_btc = $gate->get_balance('BTC');
                            if($gate_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';gate btc残高足りない;');
                                continue;
                            }
                            usleep(100000);
                            //kucoin買い
                            $kucoin_order = new KucoinOrder($kucoin_auth);
                            $order_param = [
                                'clientOid' => uniqid(),
                                'size'      => $user->btc_amount,
                                'symbol'    => 'BTC-USDT',
                                'type'      => 'market',
                                'side'      => 'buy'
                            ];
                            $kucoin_result = $kucoin_order->create($order_param);
                            if(array_key_exists('orderId',$kucoin_result)){
                                Log::info('user_id:'.$user->id.';kucoin買い成功');
                                //gateで売り
                                $gate_result = $gate->order('BTC_USDT','sell',$user->btc_amount);
                                if(array_key_exists('id',$gate_result)){
                                    Log::info('user_id:' . $user->id . ';gate売り成功');
                                    //bitflyerで買い、gateで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'kucoin';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'gate';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //gate売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $order_param = [
                                            'clientOid' => uniqid(),
                                            'size'      => $user->btc_amount,
                                            'symbol'    => 'BTC-USDT',
                                            'type'      => 'market',
                                            'side'      => 'sell'
                                        ];
                                        $kucoin_result = $kucoin_order->create($order_param);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'kucoin';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'gate';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_gate_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';gate売り失敗');
                                }
                            }

                        }
                        elseif ($best_bid == 'mexc' && !empty($mexc)){
                            //mexc BTC 残高確認
                            $mexc_balances = Common::get_mexc_allbalances($mexc);
                            $mexc_btc = $mexc_balances['BTC'];
                            if($mexc_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';mexc btc残高足りない;');
                                continue;
                            }
                            usleep(100000);
                            //kucoin買い
                            $kucoin_order = new KucoinOrder($kucoin_auth);
                            $order_param = [
                                'clientOid' => uniqid(),
                                'size'      => $user->btc_amount,
                                'symbol'    => 'BTC-USDT',
                                'type'      => 'market',
                                'side'      => 'buy'
                            ];
                            $kucoin_result = $kucoin_order->create($order_param);
                            if(array_key_exists('orderId',$kucoin_result)){
                                Log::info('user_id:' . $user->id . ';kucoin買い成功');
                                //mexcで売り
                                $mexc_sell_result = $mexc->order()->postPlace([
                                    'symbol'=>'BTC_USDT',
                                    'price'=> round($best_bid_value/$usdjpy_bid - 100),
                                    'quantity'=>$user->btc_amount,
                                    'trade_type'=>'ASK',
                                    'order_type'=>'IMMEDIATE_OR_CANCEL'
                                ]);
                                if(is_array($mexc_sell_result) && array_key_exists('code',$mexc_sell_result) &&
                                    $mexc_sell_result['code'] == 200){
                                    Log::info('user_id:' . $user->id . ';mexc売り成功');
                                    //gateで買い、mexcで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'kucoin';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'mexc';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //mexc売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $order_param = [
                                            'clientOid' => uniqid(),
                                            'size'      => $user->btc_amount,
                                            'symbol'    => 'BTC-USDT',
                                            'type'      => 'market',
                                            'side'      => 'sell'
                                        ];
                                        $kucoin_result = $kucoin_order->create($order_param);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'kucoin';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'mexc';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_mexc_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';mexc売り失敗');
                                }
                            }
                        }
                        elseif ($best_bid == 'bitget' && !empty($bitget)) {
                            //bitget BTC 残高確認
                            $bitget_balances = $bitget->get_balance_all();
                            $bitget_btc = $bitget_balances['btc'];
                            if ($bitget_btc < $user->btc_amount) {
                                Log::info('user_id:' . $user->id . ';bitget btc残高足りない;');
                                continue;
                            }
                            usleep(100000);
                            //kucoin買い
                            $kucoin_order = new KucoinOrder($kucoin_auth);
                            $order_param = [
                                'clientOid' => uniqid(),
                                'size'      => $user->btc_amount,
                                'symbol'    => 'BTC-USDT',
                                'type'      => 'market',
                                'side'      => 'buy'
                            ];
                            $kucoin_result = $kucoin_order->create($order_param);
                            if(array_key_exists('orderId',$kucoin_result)){
                                Log::info('user_id:' . $user->id . ';gate買い成功');
                                //bitgetで売り
                                $bitget_sell_result = $bitget->order('BTCUSDT_SPBL', 'sell', $user->btc_amount);
                                if (is_array($bitget_sell_result) && array_key_exists('data', $bitget_sell_result) &&
                                    is_array($bitget_sell_result['data']) && array_key_exists('orderId', $bitget_sell_result['data'])) {
                                    Log::info('user_id:' . $user->id . ';bitget売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'kucoin';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitget';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //kucoin売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $order_param = [
                                            'clientOid' => uniqid(),
                                            'size'      => $user->btc_amount,
                                            'symbol'    => 'BTC-USDT',
                                            'type'      => 'market',
                                            'side'      => 'sell'
                                        ];
                                        $kucoin_result = $kucoin_order->create($order_param);
                                    }
                                    $order_list_btc = new OrderBtc;
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'kucoin';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitget';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_bitget_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';bitget売り失敗');
                                }
                            }
                        }
                    }
                    elseif ($best_ask == 'mexc' && !empty($mexc)){
                        //mexc 残高確認
                        $mexc_balances = Common::get_mexc_allbalances($mexc);
                        $mexc_usdt = $mexc_balances['USDT'];
                        $jpy_balance = floatval($mexc_usdt * $usdjpy->ask);
                        if($jpy_balance < $best_ask_value*$user->btc_amount * 1.1){
                            Log::info('user_id:'.$user->id.';mexc usdt残高足りない;');
                            continue;
                        }
                        if($best_bid == 'coincheck' && !empty($coincheck)){
                            //coincheck BTC 残高確認
                            $coincheck_balance_btc = $coincheck->get_balance('btc');
                            if($coincheck_balance_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';coincheck btc残高足りない;');
                                continue;
                            }
                            //gate買い
                            $gate_result = $gate->order('BTC_USDT','buy', $buy_usdt);
                            if(array_key_exists('filled_amount',$gate_result) && $gate_result['filled_amount'] >0 ){
                                Log::info('user_id:'.$user->id.';gate買い成功');
                                //coincheck売り
                                $coincheck_result = $coincheck->order(array(
                                    "amount" => $user->btc_amount,
                                    "order_type" => "market_sell",
                                    "pair" => "btc_jpy"
                                ));
                                if($coincheck_result && array_key_exists('success',$coincheck_result) &&
                                    $coincheck_result['success']){
                                    Log::info('user_id:'.$user->id.';coincheck売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gate';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'coincheck';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                }
                                else {
                                    //coincheck売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $gate_result = $gate->order('BTC_USDT', 'sell', $user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gate';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'coincheck';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_coincheck_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';coincheck売り失敗');
                                }
                            }
                        }
                        elseif ($best_bid == 'bitflyer' && !empty($bitflyer)) {
                            //bitflyer BTC 残高確認
                            $bitflyer_balance_btc = Common::getBitflyerBalance($bitflyer, 'BTC');
                            if ($bitflyer_balance_btc < $user->btc_amount) {
                                Log::info('user_id:' . $user->id . ';bitflyer btc残高足りない;');
                                continue;
                            }
                            //gate買い
                            $gate_result = $gate->order('BTC_USDT', 'buy', $buy_usdt);
                            if (array_key_exists('filled_amount', $gate_result) && $gate_result['filled_amount'] > 0) {
                                Log::info('user_id:' . $user->id . ';gate買い成功');
                                //bitflyerで売り
                                $bitflyer_sell = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'SELL', 0, $user->btc_amount);
                                if (array_key_exists('child_order_acceptance_id', $bitflyer_sell) &&
                                    $bitflyer_sell['child_order_acceptance_id']) {
                                    Log::info('user_id:' . $user->id . ';bitflyer売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gate';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitflyer';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //coincheck売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $gate_result = $gate->order('BTC_USDT', 'sell', $user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gate';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitflyer';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_bitflyer_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';bitflyer売り失敗');
                                }
                            }
                        }
                        elseif ($best_bid == 'bitbank' && !empty($bitbank)) {
                            //bitbank BTC 残高確認
                            $bitbank_balance_btc = $bitbank->get_balance('btc');
                            if ($bitbank_balance_btc < $user->btc_amount) {
                                Log::info('user_id:' . $user->id . ';bitbank btc残高足りない;');
                                continue;
                            }
                            //gate買い
                            $gate_result = $gate->order('BTC_USDT', 'buy', $buy_usdt);
                            if (array_key_exists('filled_amount', $gate_result) && $gate_result['filled_amount'] > 0) {
                                Log::info('user_id:' . $user->id . ';gate買い成功');
                                //bitbank売り
                                $bitbank_result = $bitbank->order('btc_jpy', 'sell', $user->btc_amount);
                                if (array_key_exists('order_id', $bitbank_result['data'])) {
                                    Log::info('user_id:' . $user->id . ';bitbank売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gate';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitbank';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                }
                                else {
                                    //bitbank売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $gate_result = $gate->order('BTC_USDT', 'sell', $user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'gate';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitbank';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_bitbank_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';bitbank売り失敗');
                                }
                            }
                        }
                        elseif($best_bid == 'gate' && !empty($gate)){
                            //gate BTC 残高確認
                            $gate_btc = $gate->get_balance('BTC');
                            if($gate_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';gate btc残高足りない;');
                                continue;
                            }
                            usleep(100000);
                            //mexc買い
                            $mexc_buy_result = $mexc->order()->postPlace([
                                'symbol'=>'BTC_USDT',
                                'price'=> round($best_ask_value/$usdjpy->bid + 100),
                                'quantity'=>$user->btc_amount,
                                'trade_type'=>'BID',
                                'order_type'=>'IMMEDIATE_OR_CANCEL'
                            ]);
                            if(array_key_exists('code',$mexc_buy_result) && $mexc_buy_result['code'] == 200){
                                Log::info('user_id:'.$user->id.';mexc買い成功');
                                //gateで売り
                                $gate_result = $gate->order('BTC_USDT','sell',$user->btc_amount);
                                if(array_key_exists('id',$gate_result)){
                                    Log::info('user_id:' . $user->id . ';gate売り成功');
                                    //bitflyerで買い、gateで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'mexc';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'gate';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //gate売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $mexc->order()->postPlace([
                                            'symbol'=>'BTC_USDT',
                                            'price'=> round($best_bid_value/$usdjpy->bid - 100),
                                            'quantity'=>$user->btc_amount,
                                            'trade_type'=>'ASK',
                                            'order_type'=>'IMMEDIATE_OR_CANCEL'
                                        ]);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'mexc';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'gate';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_gate_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';gate売り失敗');
                                }
                            }

                        }
                        elseif($best_bid == 'kucoin' && !empty($kucoin) && !empty($kucoin_auth)){
                            //kucoin BTC 残高確認
                            $kucoin_btc = Common::getKucoinBalance($kucoin, 'BTC');
                            if($kucoin_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';kucoin btc残高足りない;');
                                continue;
                            }
                            //mexc買い
                            $mexc_buy_result = $mexc->order()->postPlace([
                                'symbol'=>'BTC_USDT',
                                'price'=> round($best_ask_value/$usdjpy->bid + 100),
                                'quantity'=>$user->btc_amount,
                                'trade_type'=>'BID',
                                'order_type'=>'IMMEDIATE_OR_CANCEL'
                            ]);
                            if(array_key_exists('code',$mexc_buy_result) && $mexc_buy_result['code'] == 200){
                                Log::info('user_id:'.$user->id.';mexc買い成功');
                                //kucoinで売り
                                $kucoin_order = new KucoinOrder($kucoin_auth);
                                $order_param = [
                                    'clientOid' => uniqid(),
                                    'size'      => $user->btc_amount,
                                    'symbol'    => 'BTC-USDT',
                                    'type'      => 'market',
                                    'side'      => 'sell'
                                ];
                                $kucoinsell_result = $kucoin_order->create($order_param);
                                if(array_key_exists('orderId',$kucoinsell_result)){
                                    Log::info('user_id:' . $user->id . ';kucoin売り成功');
                                    //gateで買い、kucoinで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'mexc';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'kucoin';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //kucoin売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $mexc->order()->postPlace([
                                            'symbol'=>'BTC_USDT',
                                            'price'=> round($best_bid_value/$usdjpy->bid - 100),
                                            'quantity'=>$user->btc_amount,
                                            'trade_type'=>'ASK',
                                            'order_type'=>'IMMEDIATE_OR_CANCEL'
                                        ]);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'mexc';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'kucoin';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_kucoin_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';kucoin売り失敗');
                                }
                            }
                        }
                        elseif ($best_bid == 'bitget' && !empty($bitget)) {
                            //bitget BTC 残高確認
                            $bitget_balances = $bitget->get_balance_all();
                            $bitget_btc = $bitget_balances['btc'];
                            if ($bitget_btc < $user->btc_amount) {
                                Log::info('user_id:' . $user->id . ';bitget btc残高足りない;');
                                continue;
                            }
                            usleep(100000);
                            //mexc買い
                            $mexc_buy_result = $mexc->order()->postPlace([
                                'symbol'=>'BTC_USDT',
                                'price'=> round($best_ask_value/$usdjpy->bid + 100),
                                'quantity'=>$user->btc_amount,
                                'trade_type'=>'BID',
                                'order_type'=>'IMMEDIATE_OR_CANCEL'
                            ]);
                            if(array_key_exists('code',$mexc_buy_result) && $mexc_buy_result['code'] == 200){
                                Log::info('user_id:'.$user->id.';mexc買い成功');
                                //bitgetで売り
                                $bitget_sell_result = $bitget->order('BTCUSDT_SPBL', 'sell', $user->btc_amount);
                                if (is_array($bitget_sell_result) && array_key_exists('data', $bitget_sell_result) &&
                                    is_array($bitget_sell_result['data']) && array_key_exists('orderId', $bitget_sell_result['data'])) {
                                    Log::info('user_id:' . $user->id . ';bitget売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'mexc';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitget';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //bitget売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $mexc->order()->postPlace([
                                            'symbol'=>'BTC_USDT',
                                            'price'=> round($best_bid_value/$usdjpy->bid - 100),
                                            'quantity'=>$user->btc_amount,
                                            'trade_type'=>'ASK',
                                            'order_type'=>'IMMEDIATE_OR_CANCEL'
                                        ]);
                                    }
                                    $order_list_btc = new OrderBtc;
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'mexc';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitget';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_bitget_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';bitget売り失敗');
                                }
                            }
                        }
                    }
                    elseif ($best_ask == 'bitget' && !empty($bitget)){
                        //bitget BTC 残高確認
                        $bitget_balances = $bitget->get_balance_all();
                        $bitget_usdt = $bitget_balances['usdt'];
                        $jpy_balance = floatval($bitget_usdt * $usdjpy->ask);
                        $buy_usdt = round($user->btc_amount * $best_ask_value / $usdjpy->bid);
                        if($jpy_balance < $best_ask_value*$user->btc_amount * 1.1){
                            Log::info('user_id:'.$user->id.';bitget usdt残高足りない;');
                            continue;
                        }
                        if($best_bid == 'coincheck' && !empty($coincheck)){
                            //coincheck BTC 残高確認
                            $coincheck_balance_btc = $coincheck->get_balance('btc');
                            if($coincheck_balance_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';coincheck btc残高足りない;');
                                continue;
                            }
                            //bitget買い
                            $bitget_buy_result = $bitget->order('BTCUSDT_SPBL', 'buy',
                                round($best_ask_value*$user->btc_amount/$usdjpy->bid));
                            if(is_array($bitget_buy_result) && array_key_exists('data',$bitget_buy_result) &&
                                is_array($bitget_buy_result['data']) && array_key_exists('orderId',$bitget_buy_result['data'])){
                                Log::info('user_id:'.$user->id.';bitget買い成功');
                                //coincheck売り
                                $coincheck_result = $coincheck->order(array(
                                    "amount" => $user->btc_amount,
                                    "order_type" => "market_sell",
                                    "pair" => "btc_jpy"
                                ));
                                if($coincheck_result && array_key_exists('success',$coincheck_result) &&
                                    $coincheck_result['success']){
                                    Log::info('user_id:'.$user->id.';coincheck売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitget';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'coincheck';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                }
                                else {
                                    //coincheck売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $bitget_sell_result = $bitget->order('BTCUSDT_SPBL', 'sell', $user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitget';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'coincheck';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_coincheck_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';coincheck売り失敗');
                                }
                            }
                        }
                        elseif ($best_bid == 'bitflyer' && !empty($bitflyer)) {
                            //bitflyer BTC 残高確認
                            $bitflyer_balance_btc = Common::getBitflyerBalance($bitflyer, 'BTC');
                            if ($bitflyer_balance_btc < $user->btc_amount) {
                                Log::info('user_id:' . $user->id . ';bitflyer btc残高足りない;');
                                continue;
                            }
                            //bitgate買い
                            $bitget_buy_result = $bitget->order('BTCUSDT_SPBL', 'buy',
                                round($best_ask_value*$user->btc_amount/$usdjpy->bid));
                            if(is_array($bitget_buy_result) && array_key_exists('data',$bitget_buy_result) &&
                                is_array($bitget_buy_result['data']) && array_key_exists('orderId',$bitget_buy_result['data'])){
                                Log::info('user_id:' . $user->id . ';bitget買い成功');
                                //bitflyerで売り
                                $bitflyer_sell = $bitflyer->meSendChildOrder('BTC_JPY', 'MARKET', 'SELL', 0, $user->btc_amount);
                                if (array_key_exists('child_order_acceptance_id', $bitflyer_sell) &&
                                    $bitflyer_sell['child_order_acceptance_id']) {
                                    Log::info('user_id:' . $user->id . ';bitflyer売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitget';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitflyer';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //coincheck売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $bitget_sell_result = $bitget->order('BTCUSDT_SPBL', 'sell', $user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitget';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitflyer';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_bitflyer_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';bitflyer売り失敗');
                                }
                            }
                        }
                        elseif ($best_bid == 'bitbank' && !empty($bitbank)) {
                            //bitbank BTC 残高確認
                            $bitbank_balance_btc = $bitbank->get_balance('btc');
                            if ($bitbank_balance_btc < $user->btc_amount) {
                                Log::info('user_id:' . $user->id . ';bitbank btc残高足りない;');
                                continue;
                            }
                            //bitget買い
                            $bitget_buy_result = $bitget->order('BTCUSDT_SPBL', 'buy',
                                round($best_ask_value*$user->btc_amount/$usdjpy->bid));
                            if(is_array($bitget_buy_result) && array_key_exists('data',$bitget_buy_result) &&
                                is_array($bitget_buy_result['data']) && array_key_exists('orderId',$bitget_buy_result['data'])){
                                Log::info('user_id:' . $user->id . ';bitget買い成功');
                                //bitbank売り
                                $bitbank_result = $bitbank->order('btc_jpy', 'sell', $user->btc_amount);
                                if (array_key_exists('order_id', $bitbank_result['data'])) {
                                    Log::info('user_id:' . $user->id . ';bitbank売り成功');
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitget';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitbank';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                }
                                else {
                                    //bitbank売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $bitget_sell_result = $bitget->order('BTCUSDT_SPBL', 'sell',
                                            round($best_ask_value*$user->btc_amount/$usdjpy->bid));
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitget';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'bitbank';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_bitbank_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';bitbank売り失敗');
                                }
                            }
                        }
                        elseif($best_bid == 'gate' && !empty($gate)){
                            //gate残高確認
                            $gate_btc = $gate->get_balance('BTC');
                            if($gate_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';gate btc残高足りない;');
                                continue;
                            }
                            //bitget買い
                            $bitgetbuy_result = $bitget->order('BTCUSDT_SPBL', 'buy',
                                round($best_ask_value*$user->btc_amount/$usdjpy->bid));
                            if(is_array($bitgetbuy_result) && array_key_exists('data',$bitgetbuy_result) &&
                                is_array($bitgetbuy_result['data']) && array_key_exists('orderId',$bitgetbuy_result['data'])){
                                Log::info('user_id:'.$user->id.';bitget買い成功');
                                //gateで売り
                                $gate_result = $gate->order('BTC_USDT','sell',$user->btc_amount);
                                if(array_key_exists('id',$gate_result)){
                                    Log::info('user_id:' . $user->id . ';gate売り成功');
                                    //bitflyerで買い、gateで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitget';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'gate';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //gate売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $bitget->order('BTCUSDT_SPBL', 'sell', $user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitget';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'gate';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_gate_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';gate売り失敗');
                                }
                            }

                        }
                        elseif($best_bid == 'kucoin' && !empty($kucoin) && !empty($kucoin_auth)){
                            //kucoin BTC 残高確認
                            $kucoin_btc = Common::getKucoinBalance($kucoin, 'BTC');
                            if($kucoin_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';kucoin btc残高足りない;');
                                continue;
                            }
                            //bitget買い
                            $bitgetbuy_result = $bitget->order('BTCUSDT_SPBL', 'buy',
                                round($best_ask_value*$user->btc_amount/$usdjpy->bid));
                            if(is_array($bitgetbuy_result) && array_key_exists('data',$bitgetbuy_result) &&
                                is_array($bitgetbuy_result['data']) && array_key_exists('orderId',$bitgetbuy_result['data'])){
                                Log::info('user_id:'.$user->id.';bitget買い成功');
                                //kucoinで売り
                                $kucoin_order = new KucoinOrder($kucoin_auth);
                                $order_param = [
                                    'clientOid' => uniqid(),
                                    'size'      => $user->btc_amount,
                                    'symbol'    => 'BTC-USDT',
                                    'type'      => 'market',
                                    'side'      => 'sell'
                                ];
                                $kucoinsell_result = $kucoin_order->create($order_param);
                                if(array_key_exists('orderId',$kucoinsell_result)){
                                    Log::info('user_id:' . $user->id . ';kucoin売り成功');
                                    //gateで買い、kucoinで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitget';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'kucoin';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //kucoin売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $bitget->order('BTCUSDT_SPBL', 'sell', $user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitget';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'kucoin';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_kucoin_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';kucoin売り失敗');
                                }
                            }
                        }
                        elseif ($best_bid == 'mexc' && !empty($mexc)){
                            //mexc BTC 残高確認
                            $mexc_balances = Common::get_mexc_allbalances($mexc);
                            $mexc_btc = $mexc_balances['BTC'];
                            if($mexc_btc < $user->btc_amount){
                                Log::info('user_id:'.$user->id.';mexc btc残高足りない;');
                                continue;
                            }
                            usleep(100000);
                            //bitget買い
                            $bitgetbuy_result = $bitget->order('BTCUSDT_SPBL', 'buy',
                                round($best_ask_value*$user->btc_amount/$usdjpy->bid));
                            if(is_array($bitgetbuy_result) && array_key_exists('data',$bitgetbuy_result) &&
                                is_array($bitgetbuy_result['data']) && array_key_exists('orderId',$bitgetbuy_result['data'])){
                                Log::info('user_id:'.$user->id.';bitget買い成功');
                                //mexcで売り
                                $mexc_sell_result = $mexc->order()->postPlace([
                                    'symbol'=>'BTC_USDT',
                                    'price'=> round($best_bid_value/$usdjpy_bid - 100),
                                    'quantity'=>$user->btc_amount,
                                    'trade_type'=>'ASK',
                                    'order_type'=>'IMMEDIATE_OR_CANCEL'
                                ]);
                                if(is_array($mexc_sell_result) && array_key_exists('code',$mexc_sell_result) &&
                                    $mexc_sell_result['code'] == 200){
                                    Log::info('user_id:' . $user->id . ';mexc売り成功');
                                    //gateで買い、mexcで売り
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitget';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'mexc';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = $user->btc_amount;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                } else {
                                    //mexc売り失敗、ロールバック
                                    if ($user->rollback == 'on') {
                                        $bitget->order('BTCUSDT_SPBL', 'sell', $user->btc_amount);
                                    }
                                    $order_list_btc = new OrderBtc();
                                    $order_list_btc->user_id = $user->id;
                                    $order_list_btc->buy_exchange = 'bitget';
                                    $order_list_btc->buy_rate = $best_ask_value;
                                    $order_list_btc->buy_amount = $user->btc_amount;
                                    $order_list_btc->sell_exchange = 'mexc';
                                    $order_list_btc->sell_rate = $best_bid_value;
                                    $order_list_btc->sell_amount = 0;
                                    $order_list_btc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_btc->save();
                                    //自動取引OFF
                                    $user->btc_mexc_auto = 0;
                                    $user->save();
                                    Log::info('user_id:' . $user->id . ';mexc売り失敗');
                                }
                            }
                        }

                    }

                }
                catch (Exception $e) {
                    Log::info('btc monitoring 例外:'.$e->getMessage());
                }
            }
        }
        Log::info('btc monitoring end');
        return CommandAlias::SUCCESS;
    }
}
