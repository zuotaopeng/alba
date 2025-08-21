<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RateController extends Controller
{
    public function showRate(){
        return view('rate');
    }

    public function ajaxGetRate(){
        try {
            $ask_array = array();
            $bid_array = array();
            $ask_array_xrp = array();
            $bid_array_xrp = array();
            $ask_array_eth = array();
            $bid_array_eth = array();
            $ask_array_ltc = array();
            $bid_array_ltc = array();
            $ask_array_bch = array();
            $bid_array_bch = array();

            $result = array();
            $result_rate = DB::table('rates')->get();
            if(count($result_rate)>0){
                foreach($result_rate as $row){
                    if($row->coin == 'BTC_JPY'){
                        $ask_array[$row->exchange] = $row->ask;
                        $bid_array[$row->exchange] = $row->bid;
                        $result[$row->exchange.'_ask'] = $row->ask;
                        $result[$row->exchange.'_bid'] = $row->bid;
                    }else if($row->coin=='XRP_JPY'){
                        $ask_array_xrp[$row->exchange] = $row->ask;
                        $bid_array_xrp[$row->exchange] = $row->bid;
                        $result[$row->exchange.'_ask_xrp'] = $row->ask;
                        $result[$row->exchange.'_bid_xrp'] = $row->bid;
                    }else if($row->coin=='ETH_JPY'){
                        $ask_array_eth[$row->exchange] = $row->ask;
                        $bid_array_eth[$row->exchange] = $row->bid;
                        $result[$row->exchange.'_ask_eth'] = $row->ask;
                        $result[$row->exchange.'_bid_eth'] = $row->bid;
                    }else if($row->coin=='LTC_JPY'){
                        $ask_array_ltc[$row->exchange] = $row->ask;
                        $bid_array_ltc[$row->exchange] = $row->bid;
                        $result[$row->exchange.'_ask_ltc'] = $row->ask;
                        $result[$row->exchange.'_bid_ltc'] = $row->bid;
                    }else if($row->coin=='BCH_JPY'){
                        $ask_array_bch[$row->exchange] = $row->ask;
                        $bid_array_bch[$row->exchange] = $row->bid;
                        $result[$row->exchange.'_ask_bch'] = $row->ask;
                        $result[$row->exchange.'_bid_bch'] = $row->bid;
                    }
                }
                $best_ask_value = min($ask_array);
                $best_ask = array_search($best_ask_value, $ask_array);
                $best_bid_value = max($bid_array);
                $best_bid = array_search($best_bid_value, $bid_array);
                $best_diff = $best_bid_value - $best_ask_value;
                $result['best_ask'] = $best_ask;
                $result['best_bid'] = $best_bid;
                $result['best_diff'] = $best_diff;
                $best_ask_value_xrp = min($ask_array_xrp);
                $best_ask_xrp = array_search($best_ask_value_xrp, $ask_array_xrp);
                $best_bid_value_xrp = max($bid_array_xrp);
                $best_bid_xrp = array_search($best_bid_value_xrp, $bid_array_xrp);
                $best_diff_xrp = $best_bid_value_xrp - $best_ask_value_xrp;
                $result['best_ask_xrp'] = $best_ask_xrp;
                $result['best_bid_xrp'] = $best_bid_xrp;
                $result['best_diff_xrp'] = $best_diff_xrp;
                $best_ask_value_eth = min($ask_array_eth);
                $best_ask_eth = array_search($best_ask_value_eth, $ask_array_eth);
                $best_bid_value_eth = max($bid_array_eth);
                $best_bid_eth = array_search($best_bid_value_eth, $bid_array_eth);
                $best_diff_eth = $best_bid_value_eth - $best_ask_value_eth;
                $result['best_ask_eth'] = $best_ask_eth;
                $result['best_bid_eth'] = $best_bid_eth;
                $result['best_diff_eth'] = $best_diff_eth;

                $best_ask_value_ltc = min($ask_array_ltc);
                $best_ask_ltc = array_search($best_ask_value_ltc, $ask_array_ltc);
                $best_bid_value_ltc = max($bid_array_ltc);
                $best_bid_ltc = array_search($best_bid_value_ltc, $bid_array_ltc);
                $best_diff_ltc = $best_bid_value_ltc - $best_ask_value_ltc;
                $result['best_ask_ltc'] = $best_ask_ltc;
                $result['best_bid_ltc'] = $best_bid_ltc;
                $result['best_diff_ltc'] = $best_diff_ltc;

                $best_ask_value_bch = min($ask_array_bch);
                $best_ask_bch = array_search($best_ask_value_bch, $ask_array_bch);
                $best_bid_value_bch = max($bid_array_bch);
                $best_bid_bch = array_search($best_bid_value_bch, $bid_array_bch);
                $best_diff_bch = $best_bid_value_bch - $best_ask_value_bch;
                $result['best_ask_bch'] = $best_ask_bch;
                $result['best_bid_bch'] = $best_bid_bch;
                $result['best_diff_bch'] = $best_diff_bch;
            }
        } catch (Exception $e) {
            $data = $e->getMessage();
        }
        echo json_encode($result);
    }

}
