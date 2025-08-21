<?php

namespace App\Console\Commands;

use App\Library\Bitbank;
use App\Library\GmoCoin;
use App\Models\OrderLtc;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class LtcMonitoring extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ltc:monitoring {--delay= : Number of seconds to delay command}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'LTC Monitoring';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = DB::table('users')
            ->where('approved_ltc', '=', 'yes')
            ->where('ltc_threshold', '>', 0)
            ->where('ltc_amount', '>', 0)
            ->where(DB::raw("(ltc_gmo_auto + ltc_bitbank_auto)"), '>', 1)
            ->offset((config('consts.server_id')-1) * 10)
            ->limit(10)
            ->get();
        Log::info('LTC monitoring start users cnt:'.count($users));
        foreach ($users as $user) {
            $ask_array = array();
            $bid_array = array();
            if($user->ltc_bitbank_auto=='1' && $user->bitbank_accesskey && $user->bitbank_secretkey){
                $bitbank = new bitbank($user->bitbank_accesskey, $user->bitbank_secretkey);
                $bitbank_status = $bitbank->getStatus();
                if(array_key_exists('ltc',$bitbank_status) && $bitbank_status['ltc'] == 'NORMAL'){
                    $average_price_bitbank = $bitbank->get_average_price('ltc_jpy',$user->ltc_amount);
                    if(array_key_exists('ask', $average_price_bitbank)){
                        $ask_array['bitbank'] = $average_price_bitbank['ask'] * (1 + config('consts.bitbank_fee'));
                    }
                    if(array_key_exists('bid', $average_price_bitbank)){
                        $bid_array['bitbank'] = $average_price_bitbank['bid'] * (1 - config('consts.bitbank_fee'));
                    }
                }
            }
            if($user->ltc_gmo_auto=='1' && $user->gmo_accesskey && $user->gmo_secretkey){
                $gmo = new gmocoin($user->gmo_accesskey, $user->gmo_secretkey);
                $average_price_gmo = $gmo->get_average_price('LTC',$user->ltc_amount);
                if(array_key_exists('ask', $average_price_gmo)){
                    $ask_array['gmo'] = $average_price_gmo['ask'] * (1 + config('consts.gmo_fee'));
                }
                if(array_key_exists('bid', $average_price_gmo)){
                    $bid_array['gmo'] = $average_price_gmo['bid'] * (1 - config('consts.gmo_fee'));
                }
            }

            //2取引所以上
            if (count($ask_array) < 2 || count($bid_array) < 2) {
                continue;
            }
            //価格差の取得
            $best_ask_value = min($ask_array);
            $best_ask = array_search($best_ask_value, $ask_array);
            $best_bid_value = max($bid_array);
            $best_bid = array_search($best_bid_value, $bid_array);
            $best_diff = $best_bid_value - $best_ask_value;
            //2取引所以上
            if ($best_ask_value > 0 && $best_diff > $user->ltc_threshold) {
                try {
                    Log::info('LTC DIFF OK user_id:'.$user->id.';best ask:'.$best_ask.';best bid:'.$best_bid.';best ask value:'.$best_ask_value.';best bid value:'.$best_bid_value.';amount:'.$user->ltc_amount);
                    if ($best_ask == 'bitbank' && !empty($bitbank)) {
                        //bitbank日本円残高確認
                        $bitbank_balance_jpy = $bitbank->get_balance('jpy');
                        if($bitbank_balance_jpy < $best_ask_value*$user->ltc_amount * 1.1){
                            Log::info('user_id:'.$user->id.';bitbank 日本円残高足りない;');
                            continue;
                        }
                        if ($best_bid == 'gmo' && !empty($gmo)) {
                            //gmo LTC 残高確認
                            $gmo_balance_ltc = $gmo->get_balance('LTC');
                            if($gmo_balance_ltc < $user->ltc_amount){
                                Log::info('user_id:'.$user->id.';gmo ltc残高足りない;');
                                continue;
                            }
                            //bitbank買い
                            $bitbank_result = $bitbank->order('ltc_jpy','buy',$user->ltc_amount);
                            if(array_key_exists('order_id', $bitbank_result['data'])) {
                                Log::info('user_id:' . $user->id . ';bitbank買い成功');
                                //GMO売り
                                usleep(100000);
                                $gmo_result = $gmo->order('LTC','SELL',$user->ltc_amount);
                                if(array_key_exists('data', $gmo_result) && array_key_exists('status', $gmo_result) && $gmo_result['status'] == '0') {
                                    Log::info('user_id:' . $user->id . ';GMO売り成功');
                                    $order_list_ltc = new OrderLtc();
                                    $order_list_ltc->user_id = $user->id;
                                    $order_list_ltc->buy_exchange = 'bitbank';
                                    $order_list_ltc->buy_rate = $best_ask_value;
                                    $order_list_ltc->buy_amount = $user->ltc_amount;
                                    $order_list_ltc->sell_exchange = 'gmo';
                                    $order_list_ltc->sell_rate = $best_bid_value;
                                    $order_list_ltc->sell_amount = $user->ltc_amount;
                                    $order_list_ltc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_ltc->save();
                                }else{
                                    //gmo売り失敗、ロールバック
                                    if($user->rollback=='on'){
                                        $bitbank_result = $bitbank->order('ltc_jpy','sell',$user->ltc_amount);
                                    }
                                    $order_list_ltc = new OrderLtc();
                                    $order_list_ltc->user_id = $user->id;
                                    $order_list_ltc->buy_exchange = 'bitbank';
                                    $order_list_ltc->buy_rate = $best_ask_value;
                                    $order_list_ltc->buy_amount = $user->ltc_amount;
                                    $order_list_ltc->sell_exchange = 'gmo';
                                    $order_list_ltc->sell_rate = $best_bid_value;
                                    $order_list_ltc->sell_amount = 0;
                                    $order_list_ltc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_ltc->save();
                                    //自動取引OFF
                                    $user->ltc_gmo_auto = 0;
                                    $user->save();
                                    Log::info('user_id:'.$user->id.';gmo売り失敗');
                                }
                            }
                        }
                    }
                    elseif ($best_ask == 'gmo' && !empty($gmo)) {
                        Log::info('LTC GMO BUY user_id:'.$user->id);
                        //gmo JPY 残高確認
                        $gmo_balance_jpy = $gmo->get_balance('JPY');
                        if($gmo_balance_jpy < $best_ask_value*$user->ltc_amount * 1.1){
                            Log::info('user_id:'.$user->id.';gmo 日本円残高足りない;円残高：'.$gmo_balance_jpy);
                            continue;
                        }
                        if ($best_bid == 'bitbank' && !empty($bitbank)) {
                            Log::info('LTC BITBANK SELL user_id:'.$user->id);
                            //bitbank ltc残高確認
                            $bitbank_balance_ltc = $bitbank->get_balance('ltc');
                            if($bitbank_balance_ltc < $user->ltc_amount){
                                Log::info('user_id:'.$user->id.';bitbank ltc残高足りない;');
                                continue;
                            }
                            //gmo買い
                            usleep(100000);
                            $gmo_result = $gmo->order('LTC','BUY',$user->ltc_amount);
                            Log::info('user_id:' . $user->id . ';GMO買い');
                            Log::info('gmo_result:'.json_encode($gmo_result));
                            if(array_key_exists('data', $gmo_result) && array_key_exists('status', $gmo_result) && $gmo_result['status'] == '0') {
                                Log::info('user_id:' . $user->id . ';GMO買い成功');
                                //bitbank売り
                                $bitbank_result = $bitbank->order('ltc_jpy','sell',$user->ltc_amount);
                                if(array_key_exists('order_id', $bitbank_result['data'])) {
                                    Log::info('user_id:' . $user->id . ';bitbank売り成功');
                                    $order_list_ltc = new OrderLtc();
                                    $order_list_ltc->user_id = $user->id;
                                    $order_list_ltc->buy_exchange = 'gmo';
                                    $order_list_ltc->buy_rate = $best_ask_value;
                                    $order_list_ltc->buy_amount = $user->ltc_amount;
                                    $order_list_ltc->sell_exchange = 'bitbank';
                                    $order_list_ltc->sell_rate = $best_bid_value;
                                    $order_list_ltc->sell_amount = $user->ltc_amount;
                                    $order_list_ltc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_ltc->save();
                                }else{
                                    //bitbank売り失敗、ロールバック
                                    if($user->rollback=='on'){
                                        $gmo_result = $gmo->order('LTC','SELL',$user->ltc_amount);
                                    }
                                    $order_list_ltc = new OrderLtc();
                                    $order_list_ltc->user_id = $user->id;
                                    $order_list_ltc->buy_exchange = 'gmo';
                                    $order_list_ltc->buy_rate = $best_ask_value;
                                    $order_list_ltc->buy_amount = $user->ltc_amount;
                                    $order_list_ltc->sell_exchange = 'bitbank';
                                    $order_list_ltc->sell_rate = $best_bid_value;
                                    $order_list_ltc->sell_amount = 0;
                                    $order_list_ltc->trade_time = date('Y-m-d H:i:s');
                                    $order_list_ltc->save();
                                    //自動取引OFF
                                    $user->ltc_bitbank_auto = 0;
                                    $user->save();
                                    Log::info('user_id:'.$user->id.';gmo売り失敗');
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    Log::info('user_id:'.$user->id.';例外発生:'.$e->getMessage());
                }
            }
        }
        Log::info('ltc monitoring end');
        return CommandAlias::SUCCESS;
    }
}
