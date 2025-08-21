<?php

namespace App\Console\Commands;

use App\Library\Bitbank;
use App\Library\GmoCoin;
use App\Models\Ordereth;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class EthMonitoring extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eth:monitoring {--delay= : Number of seconds to delay command}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ETH Arbitrage';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = DB::table('users')
            ->where('approved_eth', '=', 'yes')
            ->where('eth_threshold', '>', 0)
            ->where('eth_amount', '>', 0)
            ->where(DB::raw("(eth_gmo_auto + eth_bitbank_auto)"), '>', 1)
            ->offset((config('consts.server_id')-1) * 10)
            ->limit(10)
            ->get();
        Log::info('ETH monitoring start users cnt:'.count($users));
        foreach ($users as $user) {
            $ask_array = array();
            $bid_array = array();
            if($user->eth_bitbank_auto=='1' && $user->bitbank_accesskey && $user->bitbank_secretkey){
                $bitbank = new bitbank($user->bitbank_accesskey, $user->bitbank_secretkey);
                $bitbank_status = $bitbank->getStatus();
                if(array_key_exists('eth',$bitbank_status) && $bitbank_status['eth'] == 'NORMAL'){
                    $average_price_bitbank = $bitbank->get_average_price('eth_jpy',$user->eth_amount);
                    if(array_key_exists('ask', $average_price_bitbank)){
                        $ask_array['bitbank'] = $average_price_bitbank['ask'] * (1 + config('consts.bitbank_fee'));
                    }
                    if(array_key_exists('bid', $average_price_bitbank)){
                        $bid_array['bitbank'] = $average_price_bitbank['bid'] * (1 - config('consts.bitbank_fee'));
                    }
                }
            }
            if($user->eth_gmo_auto=='1' && $user->gmo_accesskey && $user->gmo_secretkey){
                $gmo = new gmocoin($user->gmo_accesskey, $user->gmo_secretkey);
                $average_price_gmo = $gmo->get_average_price('ETH',$user->eth_amount);
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
            Log::info('ETH user id:'.$user->id.';best_diff:'.$best_diff);
            //2取引所以上
            if ($best_ask_value > 0 && $best_diff > $user->eth_threshold) {
                try {
                    if ($best_ask == 'bitbank' && !empty($bitbank)) {
                        //bitbank日本円残高確認
                        $bitbank_balance_jpy = $bitbank->get_balance('jpy');
                        if($bitbank_balance_jpy < $best_ask_value*$user->eth_amount * 1.1){
                            Log::info('user_id:'.$user->id.';bitbank 日本円残高足りない;');
                            continue;
                        }
                        if ($best_bid == 'gmo' && !empty($gmo)) {
                            //gmo ETH 残高確認
                            $gmo_balance_eth = $gmo->get_balance('ETH');
                            if($gmo_balance_eth < $user->eth_amount){
                                Log::info('user_id:'.$user->id.';gmo eth残高足りない;');
                                continue;
                            }
                            //bitbank買い
                            $bitbank_result = $bitbank->order('eth_jpy','buy',$user->eth_amount);
                            if(array_key_exists('order_id', $bitbank_result['data'])) {
                                Log::info('user_id:' . $user->id . ';bitbank買い成功');
                                //GMO売り
                                usleep(100000);
                                $gmo_result = $gmo->order('ETH','SELL',$user->eth_amount);
                                if(array_key_exists('data', $gmo_result) && array_key_exists('status', $gmo_result) && $gmo_result['status'] == '0') {
                                    Log::info('user_id:' . $user->id . ';GMO売り成功');
                                    $order_list_eth = new OrderEth();
                                    $order_list_eth->user_id = $user->id;
                                    $order_list_eth->buy_exchange = 'bitbank';
                                    $order_list_eth->buy_rate = $best_ask_value;
                                    $order_list_eth->buy_amount = $user->eth_amount;
                                    $order_list_eth->sell_exchange = 'gmo';
                                    $order_list_eth->sell_rate = $best_bid_value;
                                    $order_list_eth->sell_amount = $user->eth_amount;
                                    $order_list_eth->trade_time = date('Y-m-d H:i:s');
                                    $order_list_eth->save();
                                }else{
                                    //gmo売り失敗、ロールバック
                                    if($user->rollback=='on'){
                                        $bitbank_result = $bitbank->order('eth_jpy','sell',$user->eth_amount);
                                    }
                                    $order_list_eth = new OrderEth();
                                    $order_list_eth->user_id = $user->id;
                                    $order_list_eth->buy_exchange = 'bitbank';
                                    $order_list_eth->buy_rate = $best_ask_value;
                                    $order_list_eth->buy_amount = $user->eth_amount;
                                    $order_list_eth->sell_exchange = 'gmo';
                                    $order_list_eth->sell_rate = $best_bid_value;
                                    $order_list_eth->sell_amount = 0;
                                    $order_list_eth->trade_time = date('Y-m-d H:i:s');
                                    $order_list_eth->save();
                                    //自動取引OFF
                                    $user->eth_gmo_auto = 0;
                                    $user->save();
                                    Log::info('user_id:'.$user->id.';gmo売り失敗');
                                }
                            }
                        }
                    } elseif ($best_ask == 'gmo' && !empty($gmo)) {
                        //gmo JPY 残高確認
                        $gmo_balance_jpy = $gmo->get_balance('JPY');
                        if($gmo_balance_jpy < $best_ask_value*$user->eth_amount * 1.1){
                            Log::info('user_id:'.$user->id.';gmo 日本円残高足りない;');
                            continue;
                        }
                        if ($best_bid == 'bitbank' && !empty($bitbank)) {
                            //bitbank eth残高確認
                            $bitbank_balance_eth = $bitbank->get_balance('eth');
                            if($bitbank_balance_eth < $user->eth_amount){
                                Log::info('user_id:'.$user->id.';bitbank eth残高足りない;');
                                continue;
                            }
                            //gmo買い
                            usleep(100000);
                            $gmo_result = $gmo->order('ETH','BUY',$user->eth_amount);
                            if(array_key_exists('data', $gmo_result) && array_key_exists('status', $gmo_result) && $gmo_result['status'] == '0') {
                                Log::info('user_id:' . $user->id . ';GMO買い成功');
                                //bitbank売り
                                $bitbank_result = $bitbank->order('eth_jpy','sell',$user->eth_amount);
                                if(array_key_exists('order_id', $bitbank_result['data'])) {
                                    Log::info('user_id:' . $user->id . ';bitbank売り成功');
                                    $order_list_eth = new OrderEth();
                                    $order_list_eth->user_id = $user->id;
                                    $order_list_eth->buy_exchange = 'gmo';
                                    $order_list_eth->buy_rate = $best_ask_value;
                                    $order_list_eth->buy_amount = $user->eth_amount;
                                    $order_list_eth->sell_exchange = 'bitbank';
                                    $order_list_eth->sell_rate = $best_bid_value;
                                    $order_list_eth->sell_amount = $user->eth_amount;
                                    $order_list_eth->trade_time = date('Y-m-d H:i:s');
                                    $order_list_eth->save();
                                }else{
                                    //bitbank売り失敗、ロールバック
                                    if($user->rollback=='on'){
                                        $gmo_result = $gmo->order('ETH','SELL',$user->eth_amount);
                                    }
                                    $order_list_eth = new OrderEth();
                                    $order_list_eth->user_id = $user->id;
                                    $order_list_eth->buy_exchange = 'gmo';
                                    $order_list_eth->buy_rate = $best_ask_value;
                                    $order_list_eth->buy_amount = $user->eth_amount;
                                    $order_list_eth->sell_exchange = 'bitbank';
                                    $order_list_eth->sell_rate = $best_bid_value;
                                    $order_list_eth->sell_amount = 0;
                                    $order_list_eth->trade_time = date('Y-m-d H:i:s');
                                    $order_list_eth->save();
                                    //自動取引OFF
                                    $user->eth_bitbank_auto = 0;
                                    $user->save();
                                    Log::info('user_id:'.$user->id.';gmo売り失敗');
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    Log::info('ETH Monitoring 例外:'.$e->getMessage());
                }
            }
        }
        Log::info('ETH monitoring end');
        return CommandAlias::SUCCESS;
    }
}