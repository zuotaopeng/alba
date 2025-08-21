<?php

namespace App\Console\Commands;

use App\Library\Bitbank;
use App\Library\Bitget;
use App\Library\Coincheck;
use App\Library\Common;
use App\Library\Gate;
use App\Library\GmoCoin;
use App\Models\Rate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use KuCoin\SDK\KuCoinApi;
use KuCoin\SDK\PublicApi\Symbol;
use Lin\Mxc\MxcSpot;
use PhitFlyer\PhitFlyerClient;
use Symfony\Component\Console\Command\Command as CommandAlias;

class RateBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rate:batch {--delay= : Number of seconds to delay command}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get realtime rate';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        //bitFlyer
        $bitflyer = new PhitFlyerClient();
        $average_price_bitflyer = Common::getBitflyerAveragePrice($bitflyer,0.01);
        $rate = Rate::where('coin', '=' ,'BTC_JPY')
            ->where('exchange','=','bitflyer')
            ->first();
        if(empty($rate)){
            $rate = new Rate();
        }
        $rate->exchange = 'bitflyer';
        $rate->coin = 'BTC_JPY';
        $rate->ask = $average_price_bitflyer['ask'];
        $rate->bid = $average_price_bitflyer['bid'];
        $rate->save();


        $usdjpy = DB::table('rates')
            ->where('coin','=','USD_JPY')
            ->first();
        $usdjpy_bid = 0;
        $usdjpy_ask = 0;
        if(!empty($usdjpy)){
            $usdjpy_bid = $usdjpy->bid;
            $usdjpy_ask = $usdjpy->ask;
        }


        //gate
        $gate = new Gate('','');
        $ticker_gate = $gate->get_average_price(0.001);
        $gate_ask = round($ticker_gate['ask']*$usdjpy_bid);
        $gate_bid = round($ticker_gate['bid']*$usdjpy_ask);
        $rate = Rate::where('coin', '=' ,'BTC_JPY')
            ->where('exchange','=','gate')
            ->first();
        if(empty($rate)){
            $rate = new Rate();
        }
        $rate->exchange = 'gate';
        $rate->coin = 'BTC_JPY';
        $rate->ask = $gate_ask;
        $rate->bid = $gate_bid;
        $rate->save();

        //kucoin
        KuCoinApi::setBaseUri('https://api.kucoin.com');
        KuCoinApi::setDebugMode(false);
        //板情報を取得
        $kucoin_symbol = new Symbol();
        try {
            $kucoin_btcusdt_ticker = $kucoin_symbol->getTicker('BTC-USDT');
            if(array_key_exists('bestAsk',$kucoin_btcusdt_ticker)){
                $kucoin_ask = $kucoin_btcusdt_ticker['bestAsk']*$usdjpy_bid;
                $kucoin_bid = $kucoin_btcusdt_ticker['bestBid']*$usdjpy_ask;
                $rate = Rate::where('coin', '=' ,'BTC_JPY')
                    ->where('exchange','=','kucoin')
                    ->first();
                if(empty($rate)){
                    $rate = new Rate();
                }
                $rate->exchange = 'kucoin';
                $rate->coin = 'BTC_JPY';
                $rate->ask = $kucoin_ask;
                $rate->bid = $kucoin_bid;
                $rate->save();
            }
        } catch (\Exception $e) {
        }


        //mexc
        $mexc = new MxcSpot('','');
        try {
            $mexc_btcusdt_ticker = $mexc->market()->getTicker([
                'symbol'=>'btc_usdt',
                'limit'=>2
            ]);
            if(array_key_exists('data',$mexc_btcusdt_ticker)){
                $data = $mexc_btcusdt_ticker['data'];
                if(count($data) > 0){
                    $ticker = $data[0];
                    if(array_key_exists('ask',$ticker)){
                        $mexc_ask = $ticker['ask']*$usdjpy_bid;
                        $mexc_bid = $ticker['bid']*$usdjpy_ask;
                        $rate = Rate::where('coin', '=' ,'BTC_JPY')
                            ->where('exchange','=','mexc')
                            ->first();
                        if(empty($rate)){
                            $rate = new Rate();
                        }
                        $rate->exchange = 'mexc';
                        $rate->coin = 'BTC_JPY';
                        $rate->ask = $mexc_ask;
                        $rate->bid = $mexc_bid;
                        $rate->save();
                    }
                }
            }
        }catch (\Exception $e){
            print_r($e->getMessage());
        }

        //bitget
        $bitget = new Bitget('','','');
        $ticker_bitget = $bitget->get_average_price(0.001);
        $bitget_ask = round($ticker_bitget['ask']*$usdjpy_bid);
        $bitget_bid = round($ticker_bitget['bid']*$usdjpy_ask);
        $rate = Rate::where('coin', '=' ,'BTC_JPY')
            ->where('exchange','=','bitget')
            ->first();
        if(empty($rate)){
            $rate = new Rate();
        }
        $rate->exchange = 'bitget';
        $rate->coin = 'BTC_JPY';
        $rate->ask = $bitget_ask;
        $rate->bid = $bitget_bid;
        $rate->save();

        return CommandAlias::SUCCESS;
    }
}
