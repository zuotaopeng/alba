<?php

namespace App\Console\Commands;

use App\Library\Bitbank;
use App\Library\GmoCoin;
use App\Models\OrderBch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class BchMonitoring extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bch:monitoring {--delay= : Number of seconds to delay command}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'BCH monitoring';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = DB::table('users')
            ->where('approved_bch', '=', 'yes')
            ->where('bch_threshold', '>', 0)
            ->where('bch_amount', '>', 0)
            ->where(DB::raw("(bch_gmo_auto + bch_bitbank_auto)"), '>', 1)
            ->offset((config('consts.server_id')-1) * 10)
            ->limit(10)
            ->get();
        Log::info('BCH monitoring start users cnt:'.count($users));
        foreach ($users as $user) {
            $ask_array = array();
            $bid_array = array();
            if($user->bch_bitbank_auto=='1' && $user->bitbank_accesskey && $user->bitbank_secretkey){
                $bitbank = new bitbank($user->bitbank_accesskey, $user->bitbank_secretkey);
                $bitbank_status = $bitbank->getStatus();
                if(array_key_exists('bch',$bitbank_status) && $bitbank_status['bch'] == 'NORMAL'){
                    $average_price_bitbank = $bitbank->get_average_price('bch_jpy',$user->bch_amount);
                    if(array_key_exists('ask', $average_price_bitbank)){
                        $ask_array['bitbank'] = $average_price_bitbank['ask'] * (1 + config('consts.bitbank_fee'));
                    }
                    if(array_key_exists('bid', $average_price_bitbank)){
                        $bid_array['bitbank'] = $average_price_bitbank['bid'] * (1 - config('consts.bitbank_fee'));
                    }
                }
            }
            if($user->bch_gmo_auto=='1' && $user->gmo_accesskey && $user->gmo_secretkey){
                $gmo = new gmocoin($user->gmo_accesskey, $user->gmo_secretkey);
                $average_price_gmo = $gmo->get_average_price('BCH',$user->bch_amount);
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
            Log::info('BCH user id:'.$user->id.';best_diff:'.$best_diff);
            //2取引所以上
            if ($best_ask_value > 0 && $best_diff > $user->bch_threshold) {
                try {
                    if ($best_ask == 'bitbank' && !empty($bitbank)) {
                        //bitbank日本円残高確認
                        $bitbank_balance_jpy = $bitbank->get_balance('jpy');
                        if($bitbank_balance_jpy < $best_ask_value*$user->bch_amount * 1.1){
                            Log::info('user_id:'.$user->id.';bitbank 日本円残高足りない;');
                            continue;
                        }
                        if ($best_bid == 'gmo' && !empty($gmo)) {
                            //gmo BCH 残高確認
                            $gmo_balance_bch = $gmo->get_balance('BCH');
                            if($gmo_balance_bch < $user->bch_amount){
                                Log::info('user_id:'.$user->id.';gmo bch残高足りない;');
                                continue;
                            }
                            //bitbank買い
                            $bitbank_result = $bitbank->order('bch_jpy','buy',$user->bch_amount);
                            if(array_key_exists('order_id', $bitbank_result['data'])) {
                                Log::info('user_id:' . $user->id . ';bitbank買い成功');
                                //GMO売り
                                usleep(100000);
                                $gmo_result = $gmo->order('BCH','SELL',$user->bch_amount);
                                if(array_key_exists('data', $gmo_result) && array_key_exists('status', $gmo_result) && $gmo_result['status'] == '0') {
                                    Log::info('user_id:' . $user->id . ';GMO売り成功');
                                    $order_list_bch = new OrderBch();
                                    $order_list_bch->user_id = $user->id;
                                    $order_list_bch->buy_exchange = 'bitbank';
                                    $order_list_bch->buy_rate = $best_ask_value;
                                    $order_list_bch->buy_amount = $user->bch_amount;
                                    $order_list_bch->sell_exchange = 'gmo';
                                    $order_list_bch->sell_rate = $best_bid_value;
                                    $order_list_bch->sell_amount = $user->bch_amount;
                                    $order_list_bch->trade_time = date('Y-m-d H:i:s');
                                    $order_list_bch->save();
                                }else{
                                    //gmo売り失敗、ロールバック
                                    if($user->rollback=='on'){
                                        $bitbank_result = $bitbank->order('bch_jpy','sell',$user->bch_amount);
                                    }
                                    $order_list_bch = new OrderBch();
                                    $order_list_bch->user_id = $user->id;
                                    $order_list_bch->buy_exchange = 'bitbank';
                                    $order_list_bch->buy_rate = $best_ask_value;
                                    $order_list_bch->buy_amount = $user->bch_amount;
                                    $order_list_bch->sell_exchange = 'gmo';
                                    $order_list_bch->sell_rate = $best_bid_value;
                                    $order_list_bch->sell_amount = 0;
                                    $order_list_bch->trade_time = date('Y-m-d H:i:s');
                                    $order_list_bch->save();
                                    //自動取引OFF
                                    $user->bch_gmo_auto = 0;
                                    $user->save();
                                    Log::info('user_id:'.$user->id.';gmo売り失敗');
                                }
                            }
                        }
                    } elseif ($best_ask == 'gmo' && !empty($gmo)) {
                        //gmo JPY 残高確認
                        $gmo_balance_jpy = $gmo->get_balance('JPY');
                        if($gmo_balance_jpy < $best_ask_value*$user->bch_amount * 1.1){
                            Log::info('user_id:'.$user->id.';gmo 日本円残高足りない;');
                            continue;
                        }
                        if ($best_bid == 'bitbank' && !empty($bitbank)) {
                            //bitbank bch残高確認
                            $bitbank_balance_bch = $bitbank->get_balance('bch');
                            if($bitbank_balance_bch < $user->bch_amount){
                                Log::info('user_id:'.$user->id.';bitbank bch残高足りない;');
                                continue;
                            }
                            //gmo買い
                            usleep(100000);
                            $gmo_result = $gmo->order('BCH','BUY',$user->bch_amount);
                            if(array_key_exists('data', $gmo_result) && array_key_exists('status', $gmo_result) && $gmo_result['status'] == '0') {
                                Log::info('user_id:' . $user->id . ';GMO買い成功');
                                //bitbank売り
                                $bitbank_result = $bitbank->order('bch_jpy','sell',$user->bch_amount);
                                if(array_key_exists('order_id', $bitbank_result['data'])) {
                                    Log::info('user_id:' . $user->id . ';bitbank売り成功');
                                    $order_list_bch = new OrderBch();
                                    $order_list_bch->user_id = $user->id;
                                    $order_list_bch->buy_exchange = 'gmo';
                                    $order_list_bch->buy_rate = $best_ask_value;
                                    $order_list_bch->buy_amount = $user->bch_amount;
                                    $order_list_bch->sell_exchange = 'bitbank';
                                    $order_list_bch->sell_rate = $best_bid_value;
                                    $order_list_bch->sell_amount = $user->bch_amount;
                                    $order_list_bch->trade_time = date('Y-m-d H:i:s');
                                    $order_list_bch->save();
                                }else{
                                    //bitbank売り失敗、ロールバック
                                    if($user->rollback=='on'){
                                        $gmo_result = $gmo->order('BCH','SELL',$user->bch_amount);
                                    }
                                    $order_list_bch = new OrderBch();
                                    $order_list_bch->user_id = $user->id;
                                    $order_list_bch->buy_exchange = 'gmo';
                                    $order_list_bch->buy_rate = $best_ask_value;
                                    $order_list_bch->buy_amount = $user->bch_amount;
                                    $order_list_bch->sell_exchange = 'bitbank';
                                    $order_list_bch->sell_rate = $best_bid_value;
                                    $order_list_bch->sell_amount = 0;
                                    $order_list_bch->trade_time = date('Y-m-d H:i:s');
                                    $order_list_bch->save();
                                    //自動取引OFF
                                    $user->bch_bitbank_auto = 0;
                                    $user->save();
                                    Log::info('user_id:'.$user->id.';gmo売り失敗');
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::info('BCH Monitoring 例外:'.$e->getMessage());
                }
            }
        }
        Log::info('BCH monitoring end');
        return CommandAlias::SUCCESS;
    }
}
