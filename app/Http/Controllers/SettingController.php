<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function showSetting(){
        $user = auth()->user();
        return view('setting',compact('user'));
    }

    public function saveSetting(Request $request){
        $user = auth()->user();
        $user->btc_bitflyer_auto = 0;
        $user->btc_gmo_auto = 0;
        $user->btc_bitbank_auto = 0;
        $user->btc_coincheck_auto = 0;
        $user->btc_gate_auto = 0;
        $user->btc_kucoin_auto = 0;
        $user->btc_mexc_auto = 0;
        $user->btc_bitget_auto = 0;
        $user->rollback = 'no';
        $user->fill($request->except(['amount']));
        if($request->input('amount')){
            $user->amount = $request->input('amount');
        }
        $user->save();
        return redirect()->back()->with('status','保存しました。');
    }
}
