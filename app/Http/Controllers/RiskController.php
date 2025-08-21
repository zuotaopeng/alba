<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RiskController extends Controller
{
    public function risk() {
        return view('risk');
    }


    public function ajaxSaveRisk(Request $request) {
        $user = Auth::user();
        $losscut = $request->input('losscut');
        $losscut_eth = $request->input('losscut_eth');
        $losscut_xrp = $request->input('losscut_xrp');
        $losscutline = $request->input('losscutline');
        $losscutline_eth = $request->input('losscutline_eth');
        $losscutline_xrp = $request->input('losscutline_xrp');
        $user->losscut = $losscut;
        $user->losscut_eth = $losscut_eth;
        $user->losscut_xrp = $losscut_xrp;
        $user->losscut_line = $losscutline;
        $user->losscut_line_eth = $losscutline_eth;
        $user->losscut_line_xrp = $losscutline_xrp;
        $baseline = 0;
        $baseline_eth = 0;
        $baseline_xrp = 0;
        $rates = DB::table('rates')->get();
        foreach ($rates as $rate) {
            if ($rate->coin == 'BTC_JPY') {
                $baseline = $rate->ask;
            }
            if ($rate->coin == 'ETH_JPY') {
                $baseline_eth = $rate->ask;
            }
            if ($rate->coin == 'XRP_JPY') {
                $baseline_xrp = $rate->ask;
            }
        }

        $user->baseline = $baseline;
        $user->baseline_eth = $baseline_eth;
        $user->baseline_xrp = $baseline_xrp;
        $user->save();
        return response()->json(['result' => 'OK']);
    }


}
