@section('title', '')

@extends('layouts.app')

@section('style')
    <style>
        
    </style>
@endsection

@section('content')
    <div class="tab-pane fade show active px-3 py-3 mt-5" id="currency_group" role="tabpanel">
        <div class="card-body pt-0">
            <ul class="nav nav-pills justify-content-center mb-4">
                <li class="nav-item">
                    <a href="#currency1" class="nav-link nav-link-text fs-4 active" data-currency="" data-bs-toggle="tab" aria-expanded="false">BTC</a>
                </li>
                <li class="nav-item">
                    <a href="#currency2" class="nav-link nav-link-text fs-4" data-currency="eth" data-bs-toggle="tab" aria-expanded="false">ETH</a>
                </li>
                <li class="nav-item">
                    <a href="#currency3" class="nav-link nav-link-text fs-4" data-currency="xrp" data-bs-toggle="tab" aria-expanded="true">XRP</a>
                </li>
                <li class="nav-item">
                    <a href="#currency4" class="nav-link nav-link-text fs-4" data-currency="xrp" data-bs-toggle="tab" aria-expanded="true">LTC</a>
                </li>
                <li class="nav-item">
                    <a href="#currency5" class="nav-link nav-link-text fs-4" data-currency="xrp" data-bs-toggle="tab" aria-expanded="true">BCH</a>
                </li>
            </ul>
            <div class="tab-content app-background-gradient">
                <div id="currency1" class="tab-pane active">
                    <div class="row pt-5 px-3">
                        <div class="col-12">
                            <div class="table-responsive table-group">
                                <table class="table table-bordered table-responsive-sm">
                                    <thead class="bg-transparent sticky-top">
                                    <tr>
                                        <th>取引所</th>
                                        <th>売値</th>
                                        <th>買値</th>
                                    </tr>
                                    </thead>
                                    <tbody class="text-white">
                                    <tr>
                                        <td class="text-white">bitFlyer</td>
                                        <td class="text-white" id="bf_sell">0</td>
                                        <td class="text-white" id="bf_buy">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-white">coincheck</td>
                                        <td class="text-white" id="cc_sell">0</td>
                                        <td class="text-white" id="cc_buy">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-white">bitbank</td>
                                        <td class="text-white" id="bb_sell">0</td>
                                        <td class="text-white" id="bb_buy">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-white">GMOコイン</td>
                                        <td class="text-white" id="gmo_sell">0</td>
                                        <td class="text-white" id="gmo_buy">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-white">Gate.io</td>
                                        <td class="text-white" id="gate_sell">0</td>
                                        <td class="text-white" id="gate_buy">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-white">kucoin</td>
                                        <td class="text-white" id="kucoin_sell">0</td>
                                        <td class="text-white" id="kucoin_buy">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-white">MEXC</td>
                                        <td class="text-white" id="mexc_sell">0</td>
                                        <td class="text-white" id="mexc_buy">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-white">Bitget</td>
                                        <td class="text-white" id="bitget_sell">0</td>
                                        <td class="text-white" id="bitget_buy">0</td>
                                    </tr>
                                    </tbody>
                                </table>
                                <div class="box-body text-center arbitrage-panel" style=" padding: 5px 5px 5px 5px;">
                                    <h6 class="text-u-c p-v-sm m-0 m-t" style=" font-size: 13px;margin-top:5px !important;margin-bottom:5px !important;">最適なアービトラージ：
                                        <span style=" font-size: larger; font-weight: 600;" id="btc_best_ask"></span>で購入、<br>
                                        <span style=" font-size: larger; font-weight: 600;" id="btc_best_bid"></span>で売却</h6>
                                    <h3 class="m-0 m-l m-v">
                                        <sup style=" top: -10px; ">￥</sup>
                                        <span class="text-2x" style=" font-size: 40px;" id="btc_best_diff"></span>
                                        <span class="text-xs">/ BTC</span>
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="currency2" class="tab-pane">
                    <div class="row pt-5 px-3">
                        <div class="col-12">
                            <div class="table-responsive table-group">
                                <table class="table table-bordered table-responsive-sm">
                                    <thead class="bg-transparent sticky-top">
                                    <tr>
                                        <th>取引所</th>
                                        <th>売値</th>
                                        <th>買値</th>
                                    </tr>
                                    </thead>
                                    <tbody class="text-white">
                                    <tr>
                                        <td class="text-white">bitbank</td>
                                        <td class="text-white" id="eth_bb_sell">0</td>
                                        <td class="text-white" id="eth_bb_buy">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-white">GMOコイン</td>
                                        <td class="text-white" id="eth_gmo_sell">0</td>
                                        <td class="text-white" id="eth_gmo_buy">0</td>
                                    </tr>
                                    </tbody>
                                </table>
                                <div class="box-body text-center arbitrage-panel" style=" padding: 5px 5px 5px 5px;">
                                    <h6 class="text-u-c p-v-sm m-0 m-t" style=" font-size: 13px;margin-top:5px !important;margin-bottom:5px !important;">最適なアービトラージ：
                                        <span style=" font-size: larger; font-weight: 600;" id="eth_best_ask"></span>で購入、<br>
                                        <span style=" font-size: larger; font-weight: 600;" id="eth_best_bid"></span>で売却</h6>
                                    <h3 class="m-0 m-l m-v">
                                        <sup style=" top: -10px; ">￥</sup>
                                        <span class="text-2x" style=" font-size: 40px;" id="eth_best_diff"></span>
                                        <span class="text-xs">/ ETH</span>
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="currency3" class="tab-pane">
                    <div class="row pt-5 px-3">
                        <div class="col-12">
                            <div class="table-responsive table-group">
                                <table class="table table-bordered table-responsive-sm">
                                    <thead class="bg-transparent sticky-top">
                                    <tr>
                                        <th>取引所</th>
                                        <th>売値</th>
                                        <th>買値</th>
                                    </tr>
                                    </thead>
                                    <tbody class="text-white">
                                    <tr>
                                        <td class="text-white">bitbank</td>
                                        <td class="text-white" id="xrp_bb_sell">0</td>
                                        <td class="text-white" id="xrp_bb_buy">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-white">GMOコイン</td>
                                        <td class="text-white" id="xrp_gmo_sell">0</td>
                                        <td class="text-white" id="xrp_gmo_buy">0</td>
                                    </tr>
                                    </tbody>
                                </table>
                                <div class="box-body text-center arbitrage-panel" style=" padding: 5px 5px 5px 5px;">
                                    <h6 class="text-u-c p-v-sm m-0 m-t" style=" font-size: 13px;margin-top:5px !important;margin-bottom:5px !important;">最適なアービトラージ：
                                        <span style=" font-size: larger; font-weight: 600;" id="xrp_best_ask"></span>で購入、<br>
                                        <span style=" font-size: larger; font-weight: 600;" id="xrp_best_bid"></span>で売却</h6>
                                    <h3 class="m-0 m-l m-v">
                                        <sup style=" top: -10px; ">￥</sup>
                                        <span class="text-2x" style=" font-size: 40px;" id="xrp_best_diff"></span>
                                        <span class="text-xs">/ XRP</span>
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="currency4" class="tab-pane">
                    <div class="row pt-5 px-3">
                        <div class="col-12">
                            <div class="table-responsive table-group">
                                <table class="table table-bordered table-responsive-sm">
                                    <thead class="bg-transparent sticky-top">
                                    <tr>
                                        <th>取引所</th>
                                        <th>売値</th>
                                        <th>買値</th>
                                    </tr>
                                    </thead>
                                    <tbody class="text-white">
                                    <tr>
                                        <td class="text-white">bitbank</td>
                                        <td class="text-white" id="ltc_bb_sell">0</td>
                                        <td class="text-white" id="ltc_bb_buy">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-white">GMOコイン</td>
                                        <td class="text-white" id="ltc_gmo_sell">0</td>
                                        <td class="text-white" id="ltc_gmo_buy">0</td>
                                    </tr>
                                    </tbody>
                                </table>
                                <div class="box-body text-center arbitrage-panel" style=" padding: 5px 5px 5px 5px;">
                                    <h6 class="text-u-c p-v-sm m-0 m-t" style=" font-size: 13px;margin-top:5px !important;margin-bottom:5px !important;">最適なアービトラージ：
                                        <span style=" font-size: larger; font-weight: 600;" id="ltc_best_ask"></span>で購入、<br>
                                        <span style=" font-size: larger; font-weight: 600;" id="ltc_best_bid"></span>で売却</h6>
                                    <h3 class="m-0 m-l m-v">
                                        <sup style=" top: -10px; ">￥</sup>
                                        <span class="text-2x" style=" font-size: 40px;" id="ltc_best_diff"></span>
                                        <span class="text-xs">/ LTC</span>
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="currency5" class="tab-pane">
                    <div class="row pt-5 px-3">
                        <div class="col-12">
                            <div class="table-responsive table-group">
                                <table class="table table-bordered table-responsive-sm">
                                    <thead class="bg-transparent sticky-top">
                                    <tr>
                                        <th>取引所</th>
                                        <th>売値</th>
                                        <th>買値</th>
                                    </tr>
                                    </thead>
                                    <tbody class="text-white">
                                    <tr>
                                        <td class="text-white">bitbank</td>
                                        <td class="text-white" id="bch_bb_sell">0</td>
                                        <td class="text-white" id="bch_bb_buy">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-white">GMOコイン</td>
                                        <td class="text-white" id="bch_gmo_sell">0</td>
                                        <td class="text-white" id="bch_gmo_buy">0</td>
                                    </tr>
                                    </tbody>
                                </table>
                                <div class="box-body text-center arbitrage-panel" style=" padding: 5px 5px 5px 5px;">
                                    <h6 class="text-u-c p-v-sm m-0 m-t" style=" font-size: 13px;margin-top:5px !important;margin-bottom:5px !important;">最適なアービトラージ：
                                        <span style=" font-size: larger; font-weight: 600;" id="bch_best_ask"></span>で購入、<br>
                                        <span style=" font-size: larger; font-weight: 600;" id="bch_best_bid"></span>で売却</h6>
                                    <h3 class="m-0 m-l m-v">
                                        <sup style=" top: -10px; ">￥</sup>
                                        <span class="text-2x" style=" font-size: 40px;" id="bch_best_diff"></span>
                                        <span class="text-xs">/ BCH</span>
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('script')
    <script>
        $(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            getRate()
            setInterval(getRate, 3000);
            function getRate() {
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('ajaxgetrate')}}'
                }).done(function (response) {
                    //console.log(response);
                    $("#bf_sell").text(String(Math.round(response.bitflyer_bid)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#bf_buy").text(String(Math.round(response.bitflyer_ask)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#gmo_sell").text(String(Math.round(response.gmo_bid)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#gmo_buy").text(String(Math.round(response.gmo_ask)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#cc_sell").text(String(Math.round(response.coincheck_bid)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#cc_buy").text(String(Math.round(response.coincheck_ask)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#bb_sell").text(String(Math.round(response.bitbank_bid)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#bb_buy").text(String(Math.round(response.bitbank_ask)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));

                    $("#binance_sell").text(String(Math.round(response.binance_bid)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#binance_buy").text(String(Math.round(response.binance_ask)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#gate_sell").text(String(Math.round(response.gate_bid)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#gate_buy").text(String(Math.round(response.gate_ask)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#kucoin_sell").text(String(Math.round(response.kucoin_bid)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#kucoin_buy").text(String(Math.round(response.kucoin_ask)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#mexc_sell").text(String(Math.round(response.mexc_bid)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#mexc_buy").text(String(Math.round(response.mexc_ask)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#bitget_sell").text(String(Math.round(response.bitget_bid)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#bitget_buy").text(String(Math.round(response.bitget_ask)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));

                    $("#btc_best_ask").text(response.best_ask);
                    $("#btc_best_bid").text(response.best_bid);
                    $("#btc_best_diff").text(numberFormat(Math.round(response.best_diff)),',');

                    $("#eth_bb_sell").text(String(Math.round(response.bitbank_bid_eth)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#eth_bb_buy").text(String(Math.round(response.bitbank_ask_eth)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#eth_gmo_sell").text(String(Math.round(response.gmo_bid_eth)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#eth_gmo_buy").text(String(Math.round(response.gmo_ask_eth)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#eth_best_ask").text(response.best_ask_eth);
                    $("#eth_best_bid").text(response.best_bid_eth);
                    $("#eth_best_diff").text(numberFormat(Math.round(response.best_diff_eth),','));

                    $("#xrp_gmo_sell").text(String(Math.round(response.gmo_bid_xrp * 1000) / 1000).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#xrp_gmo_buy").text(String(Math.round(response.gmo_ask_xrp * 1000) / 1000).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#xrp_bb_sell").text(String(Math.round(response.bitbank_bid_xrp * 1000) / 1000).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#xrp_bb_buy").text(String(Math.round(response.bitbank_ask_xrp * 1000) / 1000).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#xrp_best_ask").text(response.best_ask_xrp);
                    $("#xrp_best_bid").text(response.best_bid_xrp);
                    $("#xrp_best_diff").text(numberFormat(Math.round(response.best_diff_xrp * 1000) / 1000),',');

                    $("#ltc_gmo_sell").text(String(Math.round(response.gmo_bid_ltc * 1000) / 1000).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#ltc_gmo_buy").text(String(Math.round(response.gmo_ask_ltc * 1000) / 1000).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#ltc_bb_sell").text(String(Math.round(response.bitbank_bid_ltc * 1000) / 1000).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#ltc_bb_buy").text(String(Math.round(response.bitbank_ask_ltc * 1000) / 1000).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#ltc_best_ask").text(response.best_ask_ltc);
                    $("#ltc_best_bid").text(response.best_bid_ltc);
                    $("#ltc_best_diff").text(numberFormat(Math.round(response.best_diff_ltc * 1000) / 1000),',');

                    $("#bch_gmo_sell").text(String(Math.round(response.gmo_bid_bch * 1000) / 1000).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#bch_gmo_buy").text(String(Math.round(response.gmo_ask_bch * 1000) / 1000).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#bch_bb_sell").text(String(Math.round(response.bitbank_bid_bch * 1000) / 1000).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#bch_bb_buy").text(String(Math.round(response.bitbank_ask_bch * 1000) / 1000).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,'));
                    $("#bch_best_ask").text(response.best_ask_bch);
                    $("#bch_best_bid").text(response.best_bid_bch);
                    $("#bch_best_diff").text(numberFormat(Math.round(response.best_diff_bch * 1000) / 1000),',');

                }).fail(function () {
                    // alert("レートの取得が失敗しました。");
                });
            }

        });
        let numberFormat = function(number, delimiter) {
            delimiter = delimiter || ',';
            if (isNaN(number)) return number;
            if (typeof delimiter !== 'string' || delimiter === '') return number;
            let reg = new RegExp(delimiter.replace(/\./, '\\.'), 'g');
            number = String(number).replace(reg, '');
            while (number !== (number = number.replace(/^(-?[0-9]+)([0-9]{3})/, '$1' + delimiter + '$2')));
            return number;
        };
    </script>
@endsection

