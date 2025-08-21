<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function showOrderList(Request $request){
        $user = auth()->user();
        $startday = date('Y-m-d',strtotime('-1 month'));
        $endday = date('Y-m-d');
        $total_profit = 0;
        $pair = 'btc';
        $table = 'order_'.$pair.'s';
        if($request->input('pair')){
            $pair = $request->input('pair');
            $table = 'order_'.$pair.'s';
            if($pair == 'bch'){
                $table = 'order_'.$pair.'es';
            }
        }
        if($request->input('startday')){
            $startday = $request->input('startday');
        }
        if($request->input('endday')){
            $endday = $request->input('endday');
        }
        $orders_query = DB::table($table)
            ->where('user_id',$user->id)
            ->whereDate('created_at','>=',$startday)
            ->whereDate('created_at','<=',$endday);
        $orders = $orders_query->get();
        foreach($orders as $order){
            $buy_rate = $order->buy_rate;
            $sell_rate = $order->sell_rate;
            $sell_amount = $order->sell_amount;
            $profit = ($sell_rate - $buy_rate) * $sell_amount;
            $total_profit += $profit;
        }
        session()->flashInput($request->input());
        return view('orderlist',compact('orders','total_profit'));
    }
}
