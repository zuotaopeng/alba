<?php

namespace App\Console\Commands;

use App\Library\Bitbank;
use App\Library\GmoCoin;
use App\Models\OrderXrp;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class XrpMonitoring extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xrp:monitoring {--delay= : Number of seconds to delay command}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'XRP Arbitrage';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = DB::table('users')
            ->where('approved_xrp', '=', 'yes')
            ->where('xrp_threshold', '>', 0)
            ->where('xrp_amount', '>', 0)
            ->where(DB::raw("(xrp_gmo_auto + xrp_bitbank_auto)"), '>', 1)
            ->offset((config('consts.server_id')-1) * 10)
            ->limit(10)
            ->get();
        Log::info('XRP monitoring start users cnt:'.count($users));
        foreach ($users as $user) {
            $ask_array = array();
            $bid_array = array();
            if($user->xrp_bitbank_auto=='1' && $user->bitbank_accesskey && $user->bitbank_secretkey){
                $bitbank = new bitbank($user->bitbank_accesskey, $user->bitbank_secretkey);
                $bitbank_status = $bitbank->getStatus();
                if(array_key_exists('xrp',$bitbank_status) && $bitbank_status['xrp'] == 'NORMAL'){
                    $average_price_bitbank = $bitbank->get_average_price('xrp_jpy',$user->xrp_amount);
                    if(array_key_exists('ask', $average_price_bitbank)){
                        $ask_array['bitbank'] = $average_price_bitbank['ask'] * (1 + config('consts.bitbank_fee'));
                    }
                    if(array_key_exists('bid', $average_price_bitbank)){
                        $bid_array['bitbank'] = $average_price_bitbank['bid'] * (1 - config('consts.bitbank_fee'));
                    }
                }
            }
            if($user->xrp_gmo_auto=='1' && $user->gmo_accesskey && $user->gmo_secretkey){
                $gmo = new gmocoin($user->gmo_accesskey, $user->gmo_secretkey);
                $average_price_gmo = $gmo->get_average_price('XRP',$user->xrp_amount);
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
            if ($best_ask_value > 0 && $best_diff > $user->xrp_threshold) {
                try {
                    Log::info('XRP DIFF OK user_id:'.$user->id.';best ask:'.$best_ask.';best bid:'.$best_bid.';best ask value:'.$best_ask_value.';best bid value:'.$best_bid_value.';amount:'.$user->xrp_amount);
                    if ($best_ask == 'bitbank' && !empty($bitbank)) {
                        //bitbank日本円残高確認
                        $bitbank_balance_jpy = $bitbank->get_balance('jpy');
                        if($bitbank_balance_jpy < $best_ask_value*$user->xrp_amount * 1.1){
                            Log::info('user_id:'.$user->id.';bitbank 日本円残高足りない;');
                            continue;
                        }
                        if ($best_bid == 'gmo' && !empty($gmo)) {
                            //gmo XRP 残高確認
                            $gmo_balance_xrp = $gmo->get_balance('XRP');
                            if($gmo_balance_xrp < $user->xrp_amount){
                                Log::info('user_id:'.$user->id.';gmo xrp残高足りない;');
                                continue;
                            }
                            //bitbank買い
                            $bitbank_result = $bitbank->order('xrp_jpy','buy',$user->xrp_amount);
                            if(array_key_exists('order_id', $bitbank_result['data'])) {
                                Log::info('user_id:' . $user->id . ';bitbank買い成功');
                                //GMO売り
                                usleep(100000);
                                $gmo_result = $gmo->order('XRP','SELL',$user->xrp_amount);
                                if(array_key_exists('data', $gmo_result) && array_key_exists('status', $gmo_result) && $gmo_result['status'] == '0') {
                                    Log::info('user_id:' . $user->id . ';GMO売り成功');
                                    $order_list_xrp = new OrderXrp();
                                    $order_list_xrp->user_id = $user->id;
                                    $order_list_xrp->buy_exchange = 'bitbank';
                                    $order_list_xrp->buy_rate = $best_ask_value;
                                    $order_list_xrp->buy_amount = $user->xrp_amount;
                                    $order_list_xrp->sell_exchange = 'gmo';
                                    $order_list_xrp->sell_rate = $best_bid_value;
                                    $order_list_xrp->sell_amount = $user->xrp_amount;
                                    $order_list_xrp->trade_time = date('Y-m-d H:i:s');
                                    $order_list_xrp->save();
                                }else{
                                    //gmo売り失敗、ロールバック
                                    if($user->rollback=='on'){
                                        $bitbank_result = $bitbank->order('xrp_jpy','sell',$user->xrp_amount);
                                    }
                                    $order_list_xrp = new OrderXrp();
                                    $order_list_xrp->user_id = $user->id;
                                    $order_list_xrp->buy_exchange = 'bitbank';
                                    $order_list_xrp->buy_rate = $best_ask_value;
                                    $order_list_xrp->buy_amount = $user->xrp_amount;
                                    $order_list_xrp->sell_exchange = 'gmo';
                                    $order_list_xrp->sell_rate = $best_bid_value;
                                    $order_list_xrp->sell_amount = 0;
                                    $order_list_xrp->trade_time = date('Y-m-d H:i:s');
                                    $order_list_xrp->save();
                                    //自動取引OFF
                                    $user->xrp_gmo_auto = 0;
                                    $user->save();
                                    Log::info('user_id:'.$user->id.';gmo売り失敗');
                                }
                            }
                        }
                    }
                    elseif ($best_ask == 'gmo' && !empty($gmo)) {
                        Log::info('XRP GMO BUY user_id:'.$user->id);
                        //gmo JPY 残高確認
                        $gmo_balance_jpy = $gmo->get_balance('JPY');
                        if($gmo_balance_jpy < $best_ask_value*$user->xrp_amount * 1.1){
                            Log::info('user_id:'.$user->id.';gmo 日本円残高足りない;円残高：'.$gmo_balance_jpy);
                            continue;
                        }
                        if ($best_bid == 'bitbank' && !empty($bitbank)) {
                            Log::info('XRP BITBANK SELL user_id:'.$user->id);
                            //bitbank xrp残高確認
                            $bitbank_balance_xrp = $bitbank->get_balance('xrp');
                            if($bitbank_balance_xrp < $user->xrp_amount){
                                Log::info('user_id:'.$user->id.';bitbank xrp残高足りない;');
                                continue;
                            }
                            //gmo買い
                            usleep(100000);
                            $gmo_result = $gmo->order('XRP','BUY',$user->xrp_amount);
                            Log::info('user_id:' . $user->id . ';GMO買い');
                            Log::info('gmo_result:'.json_encode($gmo_result));
                            if(array_key_exists('data', $gmo_result) && array_key_exists('status', $gmo_result) && $gmo_result['status'] == '0') {
                                Log::info('user_id:' . $user->id . ';GMO買い成功');
                                //bitbank売り
                                $bitbank_result = $bitbank->order('xrp_jpy','sell',$user->xrp_amount);
                                if(array_key_exists('order_id', $bitbank_result['data'])) {
                                    Log::info('user_id:' . $user->id . ';bitbank売り成功');
                                    $order_list_xrp = new OrderXrp();
                                    $order_list_xrp->user_id = $user->id;
                                    $order_list_xrp->buy_exchange = 'gmo';
                                    $order_list_xrp->buy_rate = $best_ask_value;
                                    $order_list_xrp->buy_amount = $user->xrp_amount;
                                    $order_list_xrp->sell_exchange = 'bitbank';
                                    $order_list_xrp->sell_rate = $best_bid_value;
                                    $order_list_xrp->sell_amount = $user->xrp_amount;
                                    $order_list_xrp->trade_time = date('Y-m-d H:i:s');
                                    $order_list_xrp->save();
                                }else{
                                    //bitbank売り失敗、ロールバック
                                    if($user->rollback=='on'){
                                        $gmo_result = $gmo->order('XRP','SELL',$user->xrp_amount);
                                    }
                                    $order_list_xrp = new OrderXrp();
                                    $order_list_xrp->user_id = $user->id;
                                    $order_list_xrp->buy_exchange = 'gmo';
                                    $order_list_xrp->buy_rate = $best_ask_value;
                                    $order_list_xrp->buy_amount = $user->xrp_amount;
                                    $order_list_xrp->sell_exchange = 'bitbank';
                                    $order_list_xrp->sell_rate = $best_bid_value;
                                    $order_list_xrp->sell_amount = 0;
                                    $order_list_xrp->trade_time = date('Y-m-d H:i:s');
                                    $order_list_xrp->save();
                                    //自動取引OFF
                                    $user->xrp_bitbank_auto = 0;
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
        Log::info('xrp monitoring end');
        return CommandAlias::SUCCESS;
    }
}