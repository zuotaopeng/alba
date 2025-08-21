<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TopController extends Controller
{
    public function registerFinish(){
        return view('auth.register-finish');
    }

    public function guide($tab = '1') {
        return view('guide',compact('tab'));
    }

    public function showDashboard(){
        $user = auth()->user();
        $today = date('Y-m-d');
        $week_start = date('Y-m-d',strtotime('-1 week'));
        //$month_start = date('Y-m-d',strtotime('-1 month'));
        $today_profit_btc = DB::table('order_btcs')
            ->select(DB::raw('SUM((sell_rate-buy_rate)*buy_amount) as profit'))
            ->where('user_id',$user->id)
            ->whereDate('created_at','=',$today)
            ->where('buy_amount','>',0)
            ->where('sell_amount','>',0)
            ->first();
        $today_profit_eth = DB::table('order_eths')
            ->select(DB::raw('SUM((sell_rate-buy_rate)*buy_amount) as profit'))
            ->where('user_id',$user->id)
            ->whereDate('created_at','=',$today)
            ->where('buy_amount','>',0)
            ->where('sell_amount','>',0)
            ->first();
        $today_profit_xrp = DB::table('order_xrps')
            ->select(DB::raw('SUM((sell_rate-buy_rate)*buy_amount) as profit'))
            ->where('user_id',$user->id)
            ->whereDate('created_at','=',$today)
            ->where('buy_amount','>',0)
            ->where('sell_amount','>',0)
            ->first();
        $today_profit = $today_profit_btc->profit + $today_profit_eth->profit + $today_profit_xrp->profit;
        $week_profit_btc = DB::table('order_btcs')
            ->select(DB::raw('SUM((sell_rate-buy_rate)*buy_amount) as profit'))
            ->where('user_id',$user->id)
            ->whereDate('created_at','>',$week_start)
            ->where('buy_amount','>',0)
            ->where('sell_amount','>',0)
            ->first();
        $week_profit_eth = DB::table('order_eths')
            ->select(DB::raw('SUM((sell_rate-buy_rate)*buy_amount) as profit'))
            ->where('user_id',$user->id)
            ->whereDate('created_at','>',$week_start)
            ->where('buy_amount','>',0)
            ->where('sell_amount','>',0)
            ->first();
        $week_profit_xrp = DB::table('order_xrps')
            ->select(DB::raw('SUM((sell_rate-buy_rate)*buy_amount) as profit'))
            ->where('user_id',$user->id)
            ->whereDate('created_at','>',$week_start)
            ->where('buy_amount','>',0)
            ->where('sell_amount','>',0)
            ->first();
        $week_profit = $week_profit_btc->profit + $week_profit_eth->profit + $week_profit_xrp->profit;
        $month_profit_btc = DB::table('order_btcs')
            ->select(DB::raw('SUM((sell_rate-buy_rate)*buy_amount) as profit'))
            ->where('user_id',$user->id)
            ->whereYear('created_at','=',date('Y'))
            ->whereMonth('created_at','=',date('m'))
            ->where('buy_amount','>',0)
            ->where('sell_amount','>',0)
            ->first();
        $month_profit_eth = DB::table('order_eths')
            ->select(DB::raw('SUM((sell_rate-buy_rate)*buy_amount) as profit'))
            ->where('user_id',$user->id)
            ->whereYear('created_at','=',date('Y'))
            ->whereMonth('created_at','=',date('m'))
            ->where('buy_amount','>',0)
            ->where('sell_amount','>',0)
            ->first();
        $month_profit_xrp = DB::table('order_xrps')
            ->select(DB::raw('SUM((sell_rate-buy_rate)*buy_amount) as profit'))
            ->where('user_id',$user->id)
            ->whereYear('created_at','=',date('Y'))
            ->whereMonth('created_at','=',date('m'))
            ->where('buy_amount','>',0)
            ->where('sell_amount','>',0)
            ->first();
        $month_profit = $month_profit_btc->profit + $month_profit_eth->profit + $month_profit_xrp->profit;
        return view('dashboard',compact('today_profit','week_profit','month_profit','user'));
    }


    public function ajaxChangeAuto(Request $request){
        $request->validate([
            'exchange' => ['required'],
            'currency' => ['required'],
            'auto' => ['required']
        ]);
        $res = 'success';
        $user = auth()->user();
        $exchange = $request->input('exchange');
        $currency = $request->input('currency');
        $auto = $request->input('auto');
        $column = $currency.'_'.$exchange.'_auto';
        $exchange_array = ['bitflyer','coincheck','bitbank','gmo','gate','kucoin','mexc','bitget'];
        $currency_array = ['btc','eth','xrp','ltc','bch'];
        if(in_array($exchange,$exchange_array) && in_array($currency,$currency_array) && in_array($auto,['0','1'])){
            $user->{$column} = $auto;
        }
        $user->save();
        echo json_encode(compact('res'));
    }


    public function ajaxGetRate(Request $request){
        $latest_price = DB::table('rates')
            ->orderByDesc('created_at')
            ->first();
        $yesterday_price = DB::table('rates')
            ->where('created_at','<=',date('Y-m-d H:i:s',strtotime('-1 day')))
            ->orderByDesc('created_at')
            ->first();
        $oneday_change = '-';
        if(!empty($latest_price) && !empty($yesterday_price)){
            $oneday_change = (round(($latest_price->ask - $yesterday_price->ask)/$yesterday_price->ask * 10000)/100).'%';
        }
        echo json_encode(compact('latest_price','yesterday_price','oneday_change'));
    }

}
