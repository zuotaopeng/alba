@section('title', '')

@extends('layouts.app')

@section('style')
<style>
    
.price-box {
    box-shadow: 1px 5px 3px 1px #000;
    background-color: #2D2E2C;
    transform: perspective(50em) rotateX(-8deg);
}

.nav-item {
    display: flex;
    align-items: center;
}

.btn-content {
    height: 240px !important;
    overflow: hidden;
}

.btn-toggle {
    width: 260px;
    height: 260px;
    overflow: hidden;
}
.checkbox-span-group span {
    font-size: 13px;
}

.form-check-input:disabled {
    opacity: 1 !important;
}

/***** radio button custom *****/
.form-check .form-check-input[type="radio"] {
    top: -3px !important;
}
[data-theme-version="light"] .form-check .form-check-input{
    background-color: #28a6ff!important;
    height: 28px;
    width: 28px;
    box-shadow: inset 3px 2px 0px #222222e5, inset -1px -1px 4px #ffffff50;
}

[data-theme-version="light"] .form-check .form-check-input:checked {
    background-image: none;
}

[data-theme-version="dark"] .form-check .form-check-input{
    background-color: #171717!important;
    height: 28px;
    width: 28px;
    box-shadow: inset 3px 2px 0px #222222, inset -1px -1px 4px #ffffff50;
}

[data-theme-version="dark"] .form-check .form-check-input:checked {
    background-image: none;
}

.checkbox-warning .form-check-input[type="radio"] {
    border-color: transparent;
}

.checkbox-warning .form-check-input:checked[type="radio"]:after {
    background-color: #DBC886;
    top: 0.615rem;
    left: 0.615rem;
}

input[type="radio"] {
    display: none;
}
/** ********* 2024/04/12 *********** */
#btn_toggle {
    position: relative;
    background-image: url('{{ asset('ui/images/btn_dashboard_off.png') }}');
    background-size: contain;
}
#btn_toggle.on {
    background-image: url('{{ asset('ui/images/btn_dashboard_on.png') }}');
    background-size: contain;
}
#btn_toggle:focus {
    box-shadow: none !important;
}
.ripple {
    /*position: absolute; は必須*/
    position: absolute;
    background-color: #00000060;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    /*今回はアニメーションの名前，変化の時間，繰り返し回数*/
    animation: rippleEffect 3000ms 1;
    opacity: 0;
}
@keyframes rippleEffect {
    from {
        transform: scale(1);
        opacity: 0.45;
    }
    to {
        transform: scale(50);
        opacity: 0;
    }
}



.currency-tab .nav-link {
    background-color: #e0e0e0 !important;
    border-radius: 30px;
    padding: 10px 20px;
    transition: background-color 0.3s ease;
}

.currency-tab .nav-link.active {
    background-color: #ffdd57 !important;
    box-shadow: 0 0 10px rgba(0,0,0,0.2);
}


</style>
@endsection

@section('content')
    <div class="row px-4 pt-4">
        <div class="col-12">
            <div class="card app-background-gradient-to-right p-4 mb-0 h-100">
                <div>
                    <p class="text-white mb-1">本日の利益 / 円</p>
                    <span class="fs-3 text-white"><i class="fas fa-yen-sign"></i> {{number_format($today_profit)}}</span>
                    <p class="text-white mb-1 mt-3">当月の利益 / 円</p>
                    <span class="fs-3 text-white"><i class="fas fa-yen-sign"></i> {{number_format($month_profit)}}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col btn-content d-flex justify-content-center align-items-center">
            <button class="btn btn-icon btn-circle p-0 btn-toggle" type="button" id="btn_toggle"></button>
        </div>
    </div>

    <div class="tab-pane fade show active px-3 pb-3" id="currency_group" role="tabpanel">
        <div class="card-body pt-0">
            <div class="box-custom rounded-pill p-2 mb-4">
                <ul class="nav nav-pills justify-content-center">
                    <li class="nav-item">
                        <a href="#currency1" class="nav-link active" data-currency="btc" data-bs-toggle="tab" aria-expanded="false">
                            <img class="currency-icon" src="{{asset('ui/images/color/btc.svg')}}" height="30px"/></a>
                    </li>
                    <li class="nav-item">
                        <a href="#currency2" class="nav-link" data-currency="eth" data-bs-toggle="tab" aria-expanded="false">
                            <img class="currency-icon" src="{{asset('ui/images/black/eth.svg')}}" height="30px"/></a>
                    </li>
                    <li class="nav-item">
                        <a href="#currency3" class="nav-link" data-currency="xrp" data-bs-toggle="tab" aria-expanded="false">
                            <img class="currency-icon" src="{{asset('ui/images/black/xrp.svg')}}" height="30px"/></a>
                    </li>
                    <li class="nav-item">
                        <a href="#currency4" class="nav-link" data-currency="ltc" data-bs-toggle="tab" aria-expanded="false">
                            <img class="currency-icon" src="{{asset('ui/images/black/ltc.svg')}}" height="30px"/></a>
                    </li>
                    <li class="nav-item">
                        <a href="#currency5" class="nav-link" data-currency="bch" data-bs-toggle="tab" aria-expanded="false">
                            <img class="currency-icon" src="{{asset('ui/images/black/bch.svg')}}" height="30px"/></a>
                    </li>
                </ul>
            </div>
            <div class="tab-content">
                <div id="currency1" class="tab-pane active">
                    <div class="box-custom p-3">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check custom-checkbox checkbox-warning d-flex align-items-center w-100">
                                        <div class="col-5 ms-1">
                                            <img src="{{asset('ui/images/dashboard_bitflyer_logo.png')}}" height="16px"/>
                                            <input type="radio" class="form-check-input" id="bitflyer_radio" name="" @if($user->btc_bitflyer_auto == 1 && $user->approved_btc == 'yes') checked @endif>
                                        </div>
                                        <div class="col-7">
                                            <div class="checkbox-span-group">
                                                <span>ビットフライヤー</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="toggle-custom1">
                                        <input type="checkbox" id="bitflyer" name="bitflyer" @if($user->btc_bitflyer_auto == 1 && $user->approved_btc == 'yes') checked @endif data-toggle="toggle" @if($user->approved_btc == 'no') disabled @endif data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check custom-checkbox checkbox-warning d-flex align-items-center  w-100">
                                        <div class="col-5">
                                            <img src="{{asset('ui/images/dashboard_coincheck_logo.png')}}" height="30px"/>
                                            <input type="radio" class="form-check-input bg-dark" id="coincheck_radio" name="" @if($user->btc_coincheck_auto == 1 && $user->approved_btc == 'yes') checked @endif>
                                        </div>
                                        <div class="col-7">
                                            <div class="checkbox-span-group">
                                                <span>コインチェック</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="toggle-custom1">
                                        <input type="checkbox" id="coincheck" name="coincheck" @if($user->btc_coincheck_auto == 1 && $user->approved_btc == 'yes') checked @endif data-toggle="toggle" @if($user->approved_btc == 'no') disabled @endif data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check custom-checkbox checkbox-warning d-flex align-items-center  w-100">
                                        <div class="col-5">
                                            <img src="{{asset('ui/images/dashboard_gmo_logo.png')}}" height="10px"/>
                                            <input type="radio" class="form-check-input bg-dark" id="gmo_radio" name="" @if($user->btc_gmo_auto == 1 && $user->approved_btc == 'yes') checked @endif>
                                        </div>
                                        <div class="col-7">
                                            <div class="checkbox-span-group">
                                                <span>GMOコイン</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="toggle-custom1">
                                        <input type="checkbox" id="gmo" name="gmo" @if($user->btc_gmo_auto == 1 && $user->approved_btc == 'yes') checked @endif data-toggle="toggle" @if($user->approved_btc == 'no') disabled @endif data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check custom-checkbox checkbox-warning d-flex align-items-center  w-100">
                                        <div class="col-5">
                                            <img src="{{asset('ui/images/dashboard_bitbank_logo.png')}}" height="30px"/>
                                            <input type="radio" class="form-check-input bg-dark" id="bitbank_radio" name="" @if($user->btc_bitbank_auto == 1 && $user->approved_btc == 'yes') checked @endif>
                                        </div>
                                        <div class="col-7">
                                            <div class="checkbox-span-group">
                                                <span>ビットバンク</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="toggle-custom1">
                                        <input type="checkbox" id="bitbank" name="bitbank" @if($user->btc_bitbank_auto == 1 && $user->approved_btc == 'yes') checked @endif data-toggle="toggle" @if($user->approved_btc == 'no') disabled @endif data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check custom-checkbox checkbox-warning d-flex align-items-center w-100">
                                        <div class="col-5">
                                            <img src="{{asset('ui/images/gate_logo.png')}}" height="25px"/>
                                            <input type="radio" class="form-check-input bg-dark" id="gate_radio" name="" @if($user->btc_gate_auto == 1 && $user->approved_btc == 'yes' && $user->approved_oversea == 'yes') checked @endif>
                                        </div>
                                        <div class="col-7">
                                            <div class="checkbox-span-group">
                                                <span>Gate.io</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="toggle-custom1">
                                        <input type="checkbox" id="gate" name="gate" @if($user->btc_gate_auto == 1 && $user->approved_btc == 'yes') checked @endif data-toggle="toggle" @if($user->approved_btc == 'no' || $user->approved_oversea == 'no') disabled @endif data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check custom-checkbox checkbox-warning d-flex align-items-center w-100">
                                        <div class="col-5">
                                            <img src="{{asset('ui/images/kucoin_logo.png')}}" height="25px"/>
                                            <input type="radio" class="form-check-input bg-dark" id="kucoin_radio" name="" @if($user->btc_kucoin_auto == 1 && $user->approved_btc == 'yes' && $user->approved_oversea == 'yes') checked @endif>
                                        </div>
                                        <div class="col-7">
                                            <div class="checkbox-span-group">
                                                <span>kucoin</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="toggle-custom1">
                                        <input type="checkbox" id="kucoin" name="kucoin" @if($user->btc_kucoin_auto == 1 && $user->approved_btc == 'yes') checked @endif data-toggle="toggle" @if($user->approved_btc == 'no' || $user->approved_oversea == 'no') disabled @endif data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check custom-checkbox checkbox-warning d-flex align-items-center w-100">
                                        <div class="col-5">
                                            <img src="{{asset('ui/images/mexc_logo.png')}}" height="25px"/>
                                            <input type="radio" class="form-check-input bg-dark" id="mexc_radio" name="" @if($user->btc_mexc_auto == 1 && $user->approved_btc == 'yes' && $user->approved_oversea == 'yes') checked @endif>
                                        </div>
                                        <div class="col-7">
                                            <div class="checkbox-span-group">
                                                <span>MEXC</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="toggle-custom1">
                                        <input type="checkbox" id="mexc" name="mexc" @if($user->btc_mexc_auto == 1 && $user->approved_btc == 'yes') checked @endif data-toggle="toggle" @if($user->approved_btc == 'no' || $user->approved_oversea == 'no') disabled @endif data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check custom-checkbox checkbox-warning d-flex align-items-center w-100">
                                        <div class="col-5">
                                            <img src="{{asset('ui/images/bitget_logo.png')}}" height="25px"/>
                                            <input type="radio" class="form-check-input bg-dark" id="bitget_radio" name="" @if($user->btc_bitget_auto == 1 && $user->approved_btc == 'yes' && $user->approved_oversea == 'yes') checked @endif>
                                        </div>
                                        <div class="col-7">
                                            <div class="checkbox-span-group">
                                                <span>Bitget</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="toggle-custom1">
                                        <input type="checkbox" id="bitget" name="bitget" @if($user->btc_bitget_auto == 1 && $user->approved_btc == 'yes') checked @endif data-toggle="toggle" @if($user->approved_btc == 'no' || $user->approved_oversea == 'no') disabled @endif data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark">
                                    </div>
                                </div>
                            </div>
                        </div>                        
                    </div>
                </div>
                <div id="currency2" class="tab-pane">
                    <div class="box-custom p-4">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check custom-checkbox checkbox-warning d-flex align-items-center w-100">
                                        <div class="col-5">
                                            <img src="{{asset('ui/images/dashboard_gmo_logo.png')}}" height="10px"/>
                                            <input type="radio" class="form-check-input" id="eth_gmo_radio" name="" @if($user->eth_gmo_auto == 1 && $user->approved_eth == 'yes') checked @endif>
                                        </div>
                                        <div class="col-7">
                                            <div class="checkbox-span-group">
                                                <span>GMOコイン</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="toggle-custom1">
                                        <input type="checkbox" id="eth_gmo" name="eth_gmo" @if($user->eth_gmo_auto == 1 && $user->approved_eth == 'yes') checked @endif data-toggle="toggle" @if($user->approved_eth == 'no') disabled @endif data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check custom-checkbox checkbox-warning d-flex align-items-center  w-100">
                                        <div class="col-5">
                                            <img src="{{asset('ui/images/dashboard_bitbank_logo.png')}}" height="30px"/>
                                            <input type="radio" class="form-check-input bg-dark" id="eth_bitbank_radio" name="" @if($user->eth_bitbank_auto == 1 && $user->approved_eth == 'yes') checked @endif>
                                        </div>
                                        <div class="col-7">
                                            <div class="checkbox-span-group">
                                                <span>ビットバンク</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="toggle-custom1">
                                        <input type="checkbox" id="eth_bitbank" name="eth_bitbank" @if($user->eth_bitbank_auto == 1 && $user->approved_eth == 'yes') checked @endif data-toggle="toggle" @if($user->approved_eth == 'no') disabled @endif data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="currency3" class="tab-pane">
                    <div class="box-custom p-4">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check custom-checkbox checkbox-warning d-flex align-items-center w-100">
                                        <div class="col-5">
                                            <img src="{{asset('ui/images/dashboard_gmo_logo.png')}}" height="10px"/>
                                            <input type="radio" class="form-check-input" id="xrp_gmo_radio" name="" @if($user->xrp_gmo_auto == 1 && $user->approved_xrp == 'yes') checked @endif>
                                        </div>
                                        <div class="col-7">
                                            <div class="checkbox-span-group">
                                                <span>GMOコイン</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="toggle-custom1">
                                        <input type="checkbox" id="xrp_gmo" name="xrp_gmo" @if($user->xrp_gmo_auto == 1 && $user->approved_xrp == 'yes') checked @endif data-toggle="toggle" @if($user->approved_xrp == 'no') disabled @endif data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check custom-checkbox checkbox-warning d-flex align-items-center  w-100">
                                        <div class="col-5">
                                            <img src="{{asset('ui/images/dashboard_bitbank_logo.png')}}" height="30px"/>
                                            <input type="radio" class="form-check-input bg-dark" id="xrp_bitbank_radio" name="" @if($user->xrp_bitbank_auto == 1 && $user->approved_xrp == 'yes') checked @endif>
                                        </div>
                                        <div class="col-7">
                                            <div class="checkbox-span-group">
                                                <span>ビットバンク</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="toggle-custom1">
                                        <input type="checkbox" id="xrp_bitbank" name="xrp_bitbank" @if($user->xrp_bitbank_auto == 1 && $user->approved_xrp == 'yes') checked @endif data-toggle="toggle" @if($user->approved_xrp == 'no') disabled @endif data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="currency4" class="tab-pane">
                    <div class="box-custom p-4">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check custom-checkbox checkbox-warning d-flex align-items-center w-100">
                                        <div class="col-5">
                                            <img src="{{asset('ui/images/dashboard_gmo_logo.png')}}" height="10px"/>
                                            <input type="radio" class="form-check-input" id="ltc_gmo_radio" name="" @if($user->ltc_gmo_auto == 1 && $user->approved_ltc == 'yes') checked @endif>
                                        </div>
                                        <div class="col-7">
                                            <div class="checkbox-span-group">
                                                <span>GMOコイン</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="toggle-custom1">
                                        <input type="checkbox" id="ltc_gmo" name="ltc_gmo" @if($user->ltc_gmo_auto == 1 && $user->approved_ltc == 'yes') checked @endif data-toggle="toggle" @if($user->approved_ltc == 'no') disabled @endif data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check custom-checkbox checkbox-warning d-flex align-items-center  w-100">
                                        <div class="col-5">
                                            <img src="{{asset('ui/images/dashboard_bitbank_logo.png')}}" height="30px"/>
                                            <input type="radio" class="form-check-input bg-dark" id="ltc_bitbank_radio" name="" @if($user->ltc_bitbank_auto == 1 && $user->approved_ltc == 'yes') checked @endif>
                                        </div>
                                        <div class="col-7">
                                            <div class="checkbox-span-group">
                                                <span>ビットバンク</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="toggle-custom1">
                                        <input type="checkbox" id="ltc_bitbank" name="ltc_bitbank" @if($user->ltc_bitbank_auto == 1 && $user->approved_ltc == 'yes') checked @endif data-toggle="toggle" @if($user->approved_ltc == 'no') disabled @endif data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="currency5" class="tab-pane">
                    <div class="box-custom p-4">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check custom-checkbox checkbox-warning d-flex align-items-center w-100">
                                        <div class="col-5">
                                            <img src="{{asset('ui/images/dashboard_gmo_logo.png')}}" height="10px"/>
                                            <input type="radio" class="form-check-input" id="bch_gmo_radio" name="" @if($user->bch_gmo_auto == 1 && $user->approved_bch == 'yes') checked @endif>
                                        </div>
                                        <div class="col-7">
                                            <div class="checkbox-span-group">
                                                <span>GMOコイン</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="toggle-custom1">
                                        <input type="checkbox" id="bch_gmo" name="bch_gmo" @if($user->bch_gmo_auto == 1 && $user->approved_bch == 'yes') checked @endif data-toggle="toggle" @if($user->approved_bch == 'no') disabled @endif data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check custom-checkbox checkbox-warning d-flex align-items-center  w-100">
                                        <div class="col-5">
                                            <img src="{{asset('ui/images/dashboard_bitbank_logo.png')}}" height="30px"/>
                                            <input type="radio" class="form-check-input bg-dark" id="bch_bitbank_radio" name="" @if($user->bch_bitbank_auto == 1 && $user->approved_bch == 'yes') checked @endif>
                                        </div>
                                        <div class="col-7">
                                            <div class="checkbox-span-group">
                                                <span>ビットバンク</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="toggle-custom1">
                                        <input type="checkbox" id="bch_bitbank" name="bch_bitbank" @if($user->bch_bitbank_auto == 1 && $user->approved_bch == 'yes') checked @endif data-toggle="toggle" @if($user->approved_bch == 'no') disabled @endif data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark">
                                    </div>
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
        document.addEventListener('DOMContentLoaded', function () {
            const tabLinks = document.querySelectorAll('.nav-link[data-bs-toggle="tab"]');

            tabLinks.forEach(link => {
                link.addEventListener('shown.bs.tab', function () {
                    document.querySelectorAll('.nav-link').forEach(link => {
                        const currency = link.getAttribute('data-currency');
                        const img = link.querySelector('.currency-icon');
                        if (!currency || !img) return;

                        const path = link.classList.contains('active')
                            ? `/ui/images/color/${currency}.svg`
                            : `/ui/images/black/${currency}.svg`;

                        img.setAttribute('src', path);
                    });
                });
            });
        });
    </script>
    <script>
        let checkbox_ids = ['bitflyer', 'coincheck','gmo','bitbank','gate','kucoin','mexc','bitget'];
        let eth_xrp_checkbox_ids = ['gmo','bitbank'];
        let tab_currency = '';
        function initAllButton() {
            let all_checked = true;
            let prefix = (tab_currency === "") ? "" : tab_currency + "_";
            if(tab_currency === 'eth' || tab_currency === 'xrp' || tab_currency === 'ltc' || tab_currency === 'bch') {
                $.each(eth_xrp_checkbox_ids, function(index, obj_id) {
                    if( $('#' + prefix + obj_id).prop('disabled') ) {
                        all_checked = false;
                        return false;
                    }
                    if ( !$('#' + prefix + obj_id).is(':checked') ) {
                        all_checked = false;
                        return false;
                    }
                });
            }else{
                $.each(checkbox_ids, function(index, obj_id) {
                    if( $('#' + prefix + obj_id).prop('disabled') ) {
                        all_checked = false;
                        return false;
                    }
                    if ( !$('#' + prefix + obj_id).is(':checked') ) {
                        all_checked = false;
                        return false;
                    }
                });
            }
            if(all_checked) {
                $("#btn_toggle").addClass("on");
            } else {
                $("#btn_toggle").removeClass("on");
            }
        }

        function process_toggle(click_toggle) {
            let checked_value = false;
            if(click_toggle === "off") {
                $("#btn_toggle").addClass("on");
                checked_value = true;
            } else {
                $("#btn_toggle").removeClass("on");
                checked_value = false;
            }
            $.each(checkbox_ids, function(index, obj_id) {
                let prefix = (tab_currency === "") ? "" : tab_currency + "_";
                if( $('#' + prefix + obj_id).prop('disabled') ) {
                    return false;
                }
                $('#' + prefix + obj_id).bootstrapToggle(checked_value ? 'on' : 'off');
            });
            initAllButton();
        }

        $(function() {
            initAllButton();
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $('.nav-pills a').on('show.bs.tab', function(){
                tab_currency = $(this).attr("data-currency");
                initAllButton();
            });
            $('#btn_toggle').on('click', function(e){
                let jqueryBtnObj = $(this);
                const X = 120; //jqueryBtnObj.width() / 2;
                const Y = 120; //jqueryBtnObj.height() / 2;
                // divを生成する
                let rippleDiv = document.createElement("div");
                // divのデザイン
                rippleDiv.classList.add('ripple');
                // divの位置を.setAttributeで指定
                rippleDiv.setAttribute("style","top:"+Y+"px; left:"+X+"px;");
                // divをボタンに入れる
                this.appendChild(rippleDiv);
                //divを削除する(このコードは任意です)
                setTimeout(function() {
                    rippleDiv.remove();
                    if(jqueryBtnObj.hasClass("on")) {
                        process_toggle("on");
                    } else {
                        process_toggle("off");
                    }
                }, 800);
            });

            $('#bitflyer').change(function (e) {
                e.preventDefault();
                let auto = '0';
                if($(this).is(':checked')){
                    auto = '1';
                }
                const requestData = {};
                requestData.exchange = "bitflyer";
                requestData.currency = 'btc';
                requestData.auto = auto;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('ajaxchangeauto')}}',
                    data: requestData
                }).done(function (response) {
                    if (response.res === "success") {
                        //alert("設定が変更されました。");
                        $('#bitflyer_radio').prop("checked", (auto === "1"));
                        initAllButton();
                    } else {
                        alert("設定変更が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });
            $('#coincheck').change(function (e) {
                e.preventDefault();
                let auto = '0';
                if ($(this).is(':checked')) {
                    auto = '1';
                }
                const requestData = {};
                requestData.exchange = "coincheck";
                requestData.currency = 'btc';
                requestData.auto = auto;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('ajaxchangeauto')}}',
                    data: requestData
                }).done(function (response) {
                    if (response.res === "success") {
                        //alert("設定が変更されました。");
                        $('#coincheck_radio').prop("checked", (auto == "1") ? true : false);
                        initAllButton();
                    } else {
                        alert("設定変更が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });
            $('#gmo').change(function (e) {
                e.preventDefault();
                let auto = '0';
                if ($(this).is(':checked')) {
                    auto = '1';
                }
                const requestData = {};
                requestData.exchange = "gmo";
                requestData.currency = 'btc';
                requestData.auto = auto;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('ajaxchangeauto')}}',
                    data: requestData
                }).done(function (response) {
                    if (response.res === "success") {
                        //alert("設定が変更されました。");
                        $('#gmo_radio').prop("checked", (auto == "1") ? true : false);
                        initAllButton();
                    } else {
                        alert("設定変更が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });
            $('#bitbank').change(function (e) {
                e.preventDefault();
                let auto = '0';
                if ($(this).is(':checked')) {
                    auto = '1';
                }
                const requestData = {};
                requestData.exchange = "bitbank";
                requestData.currency = 'btc';
                requestData.auto = auto;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('ajaxchangeauto')}}',
                    data: requestData
                }).done(function (response) {
                    if (response.res === "success") {
                        //alert("設定が変更されました。");
                        $('#bitbank_radio').prop("checked", (auto == "1") ? true : false);
                        initAllButton();
                    } else {
                        alert("設定変更が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });
            $('#gate').change(function (e) {
                e.preventDefault();
                let auto = '0';
                if ($(this).is(':checked')) {
                    auto = '1';
                }
                const requestData = {};
                requestData.exchange = "gate";
                requestData.currency = 'btc';
                requestData.auto = auto;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('ajaxchangeauto')}}',
                    data: requestData
                }).done(function (response) {
                    if (response.res === "success") {
                        //alert("設定が変更されました。");
                        $('#gate_radio').prop("checked", (auto === "1"));
                        initAllButton();
                    } else {
                        alert("設定変更が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });
            $('#kucoin').change(function (e) {
                e.preventDefault();
                let auto = '0';
                if ($(this).is(':checked')) {
                    auto = '1';
                }
                const requestData = {};
                requestData.exchange = "kucoin";
                requestData.currency = 'btc';
                requestData.auto = auto;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('ajaxchangeauto')}}',
                    data: requestData
                }).done(function (response) {
                    if (response.res === "success") {
                        //alert("設定が変更されました。");
                        $('#kucoin_radio').prop("checked", (auto === "1"));
                        initAllButton();
                    } else {
                        alert("設定変更が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });
            $('#mexc').change(function (e) {
                e.preventDefault();
                let auto = '0';
                if ($(this).is(':checked')) {
                    auto = '1';
                }
                const requestData = {};
                requestData.exchange = "mexc";
                requestData.currency = 'btc';
                requestData.auto = auto;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('ajaxchangeauto')}}',
                    data: requestData
                }).done(function (response) {
                    if (response.res === "success") {
                        //alert("設定が変更されました。");
                        $('#mexc_radio').prop("checked", (auto === "1"));
                        initAllButton();
                    } else {
                        alert("設定変更が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });
            $('#bitget').change(function (e) {
                e.preventDefault();
                let auto = '0';
                if ($(this).is(':checked')) {
                    auto = '1';
                }
                const requestData = {};
                requestData.exchange = "bitget";
                requestData.currency = 'btc';
                requestData.auto = auto;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('ajaxchangeauto')}}',
                    data: requestData
                }).done(function (response) {
                    if (response.res === "success") {
                        //alert("設定が変更されました。");
                        $('#bitget_radio').prop("checked", (auto === "1"));
                        initAllButton();
                    } else {
                        alert("設定変更が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });

            $('#eth_gmo').change(function (e) {
                e.preventDefault();
                let auto = '0';
                if($(this).is(':checked')){
                    auto = '1';
                }
                const requestData = {};
                requestData.exchange = "gmo";
                requestData.currency = 'eth';
                requestData.auto = auto;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('ajaxchangeauto')}}',
                    data: requestData
                }).done(function (response) {
                    if (response.res === "success") {
                        //alert("設定が変更されました。");
                        $('#eth_gmo_radio').prop("checked", (auto == "1") ? true : false);
                        initAllButton();
                    } else {
                        alert("設定変更が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });
            $('#eth_bitbank').change(function (e) {
                e.preventDefault();
                let auto = '0';
                if ($(this).is(':checked')) {
                    auto = '1';
                }
                const requestData = {};
                requestData.exchange = "bitbank";
                requestData.currency = 'eth';
                requestData.auto = auto;
                console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('ajaxchangeauto')}}',
                    data: requestData
                }).done(function (response) {
                    if (response.res === "success") {
                        //alert("設定が変更されました。");
                        $('#eth_bitbank_radio').prop("checked", (auto == "1") ? true : false);
                        initAllButton();
                    } else {
                        alert("設定変更が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });

            $('#xrp_gmo').change(function (e) {
                e.preventDefault();
                let auto = '0';
                if($(this).is(':checked')){
                    auto = '1';
                }
                const requestData = {};
                requestData.exchange = "gmo";
                requestData.currency = 'xrp';
                requestData.auto = auto;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('ajaxchangeauto')}}',
                    data: requestData
                }).done(function (response) {
                    if (response.res === "success") {
                        //alert("設定が変更されました。");
                        $('#xrp_gmo_radio').prop("checked", (auto == "1") ? true : false);
                        initAllButton();
                    } else {
                        alert("設定変更が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });
            $('#xrp_bitbank').change(function (e) {
                e.preventDefault();
                let auto = '0';
                if ($(this).is(':checked')) {
                    auto = '1';
                }
                const requestData = {};
                requestData.exchange = "bitbank";
                requestData.currency = 'xrp';
                requestData.auto = auto;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('ajaxchangeauto')}}',
                    data: requestData
                }).done(function (response) {
                    if (response.res === "success") {
                        //alert("設定が変更されました。");
                        $('#xrp_bitbank_radio').prop("checked", (auto == "1") ? true : false);
                        initAllButton();
                    } else {
                        alert("設定変更が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });

            $('#ltc_gmo').change(function (e) {
                e.preventDefault();
                let auto = '0';
                if($(this).is(':checked')){
                    auto = '1';
                }
                const requestData = {};
                requestData.exchange = "gmo";
                requestData.currency = 'ltc';
                requestData.auto = auto;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('ajaxchangeauto')}}',
                    data: requestData
                }).done(function (response) {
                    if (response.res === "success") {
                        //alert("設定が変更されました。");
                        $('#ltc_gmo_radio').prop("checked", (auto === "1"));
                        initAllButton();
                    } else {
                        alert("設定変更が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });
            $('#ltc_bitbank').change(function (e) {
                e.preventDefault();
                let auto = '0';
                if ($(this).is(':checked')) {
                    auto = '1';
                }
                const requestData = {};
                requestData.exchange = "bitbank";
                requestData.currency = 'ltc';
                requestData.auto = auto;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('ajaxchangeauto')}}',
                    data: requestData
                }).done(function (response) {
                    if (response.res === "success") {
                        //alert("設定が変更されました。");
                        $('#ltc_bitbank_radio').prop("checked", (auto === "1"));
                        initAllButton();
                    } else {
                        alert("設定変更が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });

            $('#bch_gmo').change(function (e) {
                e.preventDefault();
                let auto = '0';
                if($(this).is(':checked')){
                    auto = '1';
                }
                const requestData = {};
                requestData.exchange = "gmo";
                requestData.currency = 'bch';
                requestData.auto = auto;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('ajaxchangeauto')}}',
                    data: requestData
                }).done(function (response) {
                    if (response.res === "success") {
                        //alert("設定が変更されました。");
                        $('#bch_gmo_radio').prop("checked", (auto === "1"));
                        initAllButton();
                    } else {
                        alert("設定変更が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });
            $('#bch_bitbank').change(function (e) {
                e.preventDefault();
                let auto = '0';
                if ($(this).is(':checked')) {
                    auto = '1';
                }
                const requestData = {};
                requestData.exchange = "bitbank";
                requestData.currency = 'bch';
                requestData.auto = auto;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('ajaxchangeauto')}}',
                    data: requestData
                }).done(function (response) {
                    if (response.res === "success") {
                        //alert("設定が変更されました。");
                        $('#bch_bitbank_radio').prop("checked", (auto === "1"));
                        initAllButton();
                    } else {
                        alert("設定変更が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });

        });
    </script>
@endsection

