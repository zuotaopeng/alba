@section('title', '設定')

@extends('layouts.app')

@section('style')
<style>
    .nav-tabs .nav-link {
        border: none;
    }

    [data-theme-version="dark"] .nav-tabs .nav-link:hover, [data-theme-version="dark"] .nav-tabs .nav-link.active {
        border: none;
        border-bottom: 2px solid #fff;
    }

    .setting-content {
        max-height: calc(100vh - 250px);
        overflow-y: auto;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <ul class="nav nav-fill nav-tabs p-2" style="padding-top: 20px !important;">
            <li class="nav-item">
                <a class="nav-link nav-link-text active" data-bs-toggle="tab" href="#tab1">共通設定</a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-text" data-bs-toggle="tab" href="#tab2">ビットフライヤー</a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-text" data-bs-toggle="tab" href="#tab3">GMOコイン</a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-text" data-bs-toggle="tab" href="#tab4">ビットバンク</a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-text" data-bs-toggle="tab" href="#tab5">コインチェック</a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-text" data-bs-toggle="tab" href="#tab7">Gate.io</a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-text" data-bs-toggle="tab" href="#tab8">kucoin</a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-text" data-bs-toggle="tab" href="#tab9">MEXC</a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-text" data-bs-toggle="tab" href="#tab10">Bitget</a>
            </li>
        </ul>
    </div>
</div>
<div class="row pt-5 px-3 setting-content">
    <div class="col-12">
        @include('include.errors')
        @include('include.status')
        <form action="{{route('savesetting')}}" method="POST">
            @csrf
            <div class="tab-content app-background-gradient">
                <div class="tab-pane fade show active" id="tab1" role="tabpanel">
                    <div class="row p-4">
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">BTC取引数量（単位：BTC）</label>
                                <input type="number" name="btc_amount" class="form-control form-control-custom-2" step="0.0001" value="{{$user->btc_amount}}" placeholder="例 : 0.001">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">BTC閾値（単位：円）</label>
                                <input type="number" name="btc_threshold" class="form-control form-control-custom-2" value="{{$user->btc_threshold}}" placeholder="例 : 3000">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">ETH取引数量（単位：ETH）</label>
                                <input type="number" name="eth_amount" class="form-control form-control-custom-2" step="0.001" value="{{$user->eth_amount}}" placeholder="例 : 1">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">ETH閾値（単位：円）</label>
                                <input type="number" name="eth_threshold" class="form-control form-control-custom-2" value="{{$user->eth_threshold}}" placeholder="例 : 100">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">XRP取引数量（単位：XRP）</label>
                                <input type="number" name="xrp_amount" class="form-control form-control-custom-2" value="{{$user->xrp_amount}}" placeholder="例 : 200">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">XRP閾値（単位：円）</label>
                                <input type="number" name="xrp_threshold" class="form-control form-control-custom-2" step="0.01" value="{{$user->xrp_threshold}}" placeholder="例 : 0.5">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">LTC取引数量（単位：LTC）</label>
                                <input type="number" name="ltc_amount" class="form-control form-control-custom-2" value="{{$user->ltc_amount}}" placeholder="例 : 10">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">LTC閾値（単位：円）</label>
                                <input type="number" name="ltc_threshold" class="form-control form-control-custom-2" step="0.01" value="{{$user->ltc_threshold}}" placeholder="例 : 10">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">BCH取引数量（単位：BCH）</label>
                                <input type="number" name="bch_amount" class="form-control form-control-custom-2" value="{{$user->bch_amount}}" placeholder="例 : 60">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">BCH閾値（単位：円）</label>
                                <input type="number" name="bch_threshold" class="form-control form-control-custom-2" step="0.01" value="{{$user->bch_threshold}}" placeholder="例 : 2">
                            </div>
                        </div>
                        <div class="col-lg-12 mt-3 mb-2">
                            <div class="d-flex justify-content-between align-items-center toggle-custom2">
                                <label class="text-label form-label">ロールバック</label>
                                <input type="checkbox" id="rollback" name="rollback" @if($user->rollback == 'yes') checked @endif data-toggle="toggle" value="yes" data-onstyle="light" data-offstyle="primary" data-on="On" data-off="Off">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab2">
                    <div class="row p-4">
                        <div class="col-4 col-sm-2 mb-3">
                            <img src="{{ asset('ui/images/bitFlyer.png') }}" class="w-100"/>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">ビットフライヤーアクセスキー</label>
                                <input type="text" name="bitflyer_accesskey" class="form-control form-control-custom-2" value="{{$user->bitflyer_accesskey}}" placeholder="">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">ビットフライヤーシークレットキー</label>
                                <input type="text" name="bitflyer_secretkey" class="form-control form-control-custom-2" value="{{$user->bitflyer_secretkey}}" placeholder="">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="d-flex justify-content-between align-items-center toggle-custom2">
                                <label class="text-label form-label">ビットフライヤー自動取引</label>
                                <input type="checkbox" id="btc_bitflyer_auto" name="btc_bitflyer_auto" @if($user->btc_bitflyer_auto == 1) checked @endif data-toggle="toggle" value="1" data-onstyle="light" data-offstyle="primary" data-on="On" data-off="Off">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab3">
                    <div class="row p-4">
                        <div class="col-4 col-sm-2 mb-4">
                            <img src="{{ asset('ui/images/gmo_coin.png') }}" class="w-100"/>
                        </div>

                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">GMOコインアクセスキー</label>
                                <input type="text" name="gmo_accesskey" class="form-control form-control-custom-2" value="{{$user->gmo_accesskey}}" placeholder="">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">GMOコインシークレットキー</label>
                                <input type="text" name="gmo_secretkey" class="form-control form-control-custom-2" value="{{$user->gmo_secretkey}}" placeholder="">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="d-flex justify-content-between align-items-center toggle-custom2">
                                <label class="text-label form-label">GMOコイン自動取引</label>
                                <input type="checkbox" id="btc_gmo_auto" name="btc_gmo_auto" @if($user->btc_gmo_auto == 1) checked @endif data-toggle="toggle" value="1" data-onstyle="light" data-offstyle="primary">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab4">
                    <div class="row p-4 pt-3">
                        <div class="col-4 col-sm-2 mb-3">
                            <img src="{{ asset('ui/images/bitbank_logo.png') }}" class="w-100"/>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">ビットバンクアクセスキー</label>
                                <input type="text" name="bitbank_accesskey" class="form-control form-control-custom-2" value="{{$user->bitbank_accesskey}}" placeholder="">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">ビットバンクシークレットキー</label>
                                <input type="text" name="bitbank_secretkey" class="form-control form-control-custom-2" value="{{$user->bitbank_secretkey}}" placeholder="">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="d-flex justify-content-between align-items-center toggle-custom2">
                                <label class="text-label form-label">ビットバンク自動取引</label>
                                <input type="checkbox" id="btc_bitbank_auto" name="btc_bitbank_auto" @if($user->btc_bitbank_auto == 1) checked @endif data-toggle="toggle" value="1" data-onstyle="light" data-offstyle="primary"s>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab5">
                    <div class="row p-4 pt-3">
                        <div class="col-4 col-sm-2 mb-3 p-2">
                            <img src="{{ asset('ui/images/coincheck_logo.png') }}" style="width: 100%;"/>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">コインチェックアクセスキー</label>
                                <input type="text" name="coincheck_accesskey" class="form-control form-control-custom-2" value="{{$user->coincheck_accesskey}}" placeholder="">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">コインチェックシークレットキー</label>
                                <input type="text" name="coincheck_secretkey" class="form-control form-control-custom-2" value="{{$user->coincheck_secretkey}}" placeholder="">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="d-flex justify-content-between align-items-center toggle-custom2">
                                <label class="text-label form-label">コインチェック自動取引</label>
                                <input type="checkbox" id="btc_coincheck_auto" name="btc_coincheck_auto" @if($user->btc_coincheck_auto == 1) checked @endif data-toggle="toggle" value="1" data-onstyle="light" data-offstyle="primary">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab7">
                    <div class="row p-4 pt-3">
                        <div class="col-4 col-sm-2 mb-3 p-2">
                            <img src="{{ asset('ui/images/gate_logo_long.png') }}" style="width: 80%;"/>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">Gate.ioアクセスキー</label>
                                <input type="text" name="gate_accesskey" class="form-control form-control-custom-2" value="{{$user->gate_accesskey}}" placeholder="">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">Gate.ioシークレットキー</label>
                                <input type="text" name="gate_secretkey" class="form-control form-control-custom-2" value="{{$user->gate_secretkey}}" placeholder="">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="d-flex justify-content-between align-items-center toggle-custom2">
                                <label class="text-label form-label">Gate.io自動取引</label>
                                <input type="checkbox" id="btc_gate_auto" name="btc_gate_auto" @if($user->btc_gate_auto == 1) checked @endif data-toggle="toggle" value="1" data-onstyle="light" data-offstyle="primary">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab8">
                    <div class="row p-4 pt-3">
                        <div class="col-4 col-sm-2 mb-3 p-2">
                            <img src="{{ asset('ui/images/kucoin_logo_long.svg') }}" style="width: 80%;"/>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">kucoinアクセスキー</label>
                                <input type="text" name="kucoin_accesskey" class="form-control form-control-custom-2" value="{{$user->kucoin_accesskey}}" placeholder="">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">kucoinシークレットキー</label>
                                <input type="text" name="kucoin_secretkey" class="form-control form-control-custom-2" value="{{$user->kucoin_secretkey}}" placeholder="">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">kucoinパスフレーズ</label>
                                <input type="text" name="kucoin_passphrase" class="form-control form-control-custom-2" value="{{$user->kucoin_passphrase}}" placeholder="">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="d-flex justify-content-between align-items-center toggle-custom2">
                                <label class="text-label form-label">kucoin自動取引</label>
                                <input type="checkbox" id="btc_kucoin_auto" name="btc_kucoin_auto" @if($user->btc_kucoin_auto == 1) checked @endif data-toggle="toggle" value="1" data-onstyle="light" data-offstyle="primary">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab9">
                    <div class="row p-4 pt-3">
                        <div class="col-4 col-sm-2 mb-3 p-2">
                            <img src="{{ asset('ui/images/mexc.svg') }}" style="width: 80%;"/>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">MEXCアクセスキー</label>
                                <input type="text" name="mexc_accesskey" class="form-control form-control-custom-2" value="{{$user->mexc_accesskey}}" placeholder="">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">MEXCシークレットキー</label>
                                <input type="text" name="mexc_secretkey" class="form-control form-control-custom-2" value="{{$user->mexc_secretkey}}" placeholder="">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="d-flex justify-content-between align-items-center toggle-custom2">
                                <label class="text-label form-label">MEXC自動取引</label>
                                <input type="checkbox" id="btc_mexc_auto" name="btc_mexc_auto" @if($user->btc_mexc_auto == 1) checked @endif data-toggle="toggle" value="1" data-onstyle="light" data-offstyle="primary">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab10">
                    <div class="row p-4 pt-3">
                        <div class="col-4 col-sm-2 mb-3 p-2">
                            <img src="{{ asset('ui/images/bitget_logo_long.png') }}" style="width: 70%;"/>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">Bitgetアクセスキー</label>
                                <input type="text" name="bitget_accesskey" class="form-control form-control-custom-2" value="{{$user->bitget_accesskey}}" placeholder="">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">Bitgetシークレットキー</label>
                                <input type="text" name="bitget_secretkey" class="form-control form-control-custom-2" value="{{$user->bitget_secretkey}}" placeholder="">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="mb-2">
                                <label class="text-label form-label">Bitgetパスフレーズ</label>
                                <input type="text" name="bitget_passphrase" class="form-control form-control-custom-2" value="{{$user->bitget_passphrase}}" placeholder="">
                            </div>
                        </div>
                        <div class="col-lg-12 mb-2">
                            <div class="d-flex justify-content-between align-items-center toggle-custom2">
                                <label class="text-label form-label">Bitget自動取引</label>
                                <input type="checkbox" id="btc_bitget_auto" name="btc_bitget_auto" @if($user->btc_bitget_auto == 1) checked @endif data-toggle="toggle" value="1" data-onstyle="light" data-offstyle="primary">
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="toolbar-bottom mt-5 mx-auto col-sm-6">
                <button class="btn btn-primary btn-lg btn-block btn-rounded btn-gradient" type="submit">保存</button>
            </div>
        </form>
    </div>
</div>
@endsection