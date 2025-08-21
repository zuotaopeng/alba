@section('title', '取引履歴')

@extends('layouts.app')

@section('style')
<style>
    [data-theme-version="dark"] .bootstrap-select .btn {
        border-color: rgba(255, 255, 255, 0.6) !important;
        background: transparent !important;
        border-radius: 16px !important;
    }
    [data-theme-version="dark"] .bootstrap-select .btn :hover {
        background: transparent !important;
    }

    [data-theme-version="dark"] .table tbody tr td {
        color: #fff !important;
    }

    .table-group {
        max-height: calc(100vh - 500px);
    }
    @media (min-width: 768px){
        .table-group {
            max-height: calc(100vh - 330px);
        }
    }
</style>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <form action="{{route('showorderlist')}}" class="w-100">
                <div class="row mt-4 mx-2">
                    <div class="mb-3 col-md-6 ">
                        <input type="radio" class="btn-check" name="pair" value="btc" id="pair_btc" @if(empty(old('pair')) || old('pair') == 'btc') checked @endif>
                        <label class="btn tp-btn-light btn-outline-primary" for="pair_btc"><img src="{{asset('ui/images/color/btc.svg')}}" width="20px"/><span class="ms-1">BTC</span></label>
                        <input type="radio" class="btn-check" name="pair" value="eth" id="pair_eth" @if(old('pair') == 'eth') checked @endif>
                        <label class="btn tp-btn-light btn-outline-primary" for="pair_eth"><img src="{{asset('ui/images/color/eth.svg')}}" width="20px"/><span class="ms-1">ETH</span></label>
                        <input type="radio" class="btn-check" name="pair" value="xrp" id="pair_xrp" @if(old('pair') == 'xrp') checked @endif>
                        <label class="btn tp-btn-light btn-outline-primary" for="pair_xrp"><img src="{{asset('ui/images/color/xrp.svg')}}" width="20px"/><span class="ms-1">XRP</span></label>
                        <input type="radio" class="btn-check" name="pair" value="ltc" id="pair_ltc" @if(old('pair') == 'ltc') checked @endif>
                        <label class="btn tp-btn-light btn-outline-primary" for="pair_ltc"><img src="{{asset('ui/images/color/ltc.svg')}}" width="20px"/><span class="ms-1">LTC</span></label>
                        <input type="radio" class="btn-check" name="pair" value="bch" id="pair_bch" @if(old('pair') == 'bch') checked @endif>
                        <label class="btn tp-btn-light btn-outline-primary" for="pair_bch"><img src="{{asset('ui/images/color/bch.svg')}}" width="20px"/><span class="ms-1">BCH</span></label>
                    </div>
                </div>
                <div class="row mx-4">
                    <div class="mb-3 col-md-3">
                        <label class="form-label">開始時間</label>
                        <input type="text" class="form-control form-control-custom-3 datepicker" name="startday" @if(empty(old('startday'))) value="{{date('Y/m/d',strtotime('-1 month'))}}" @else value="{{old('startday')}}" @endif>
                    </div>
                    <div class="mb-3 col-md-3">
                        <label class="form-label">終了時間</label>
                        <input type="text" class="form-control form-control-custom-3 datepicker" name="endday" @if(empty(old('endday'))) value="{{date('Y/m/d')}}" @else value="{{old('endday')}}" @endif>
                    </div>
                    <div class="my-4 mb-sm-3 mt-sm-0 col-md-2">
                        <label class="form-label w-100 d-none d-md-block">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block btn-rounded btn-gradient btn-lg"><i class="fas fa-search me-2"></i>検索</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row pt-3 px-3">
        <div class="col-12">
            <div class="table-responsive table-group">
                <table class="table app-background-gradient table-bordered table-responsive-sm">
                    <thead class="sticky-top">
                        <tr>
                            <th rowspan="2">取引時間</th>
                            <th colspan="3">買い</th>
                            <th colspan="3">売り</th>
                        </tr>
                        <tr>
                            <th>取引所</th>
                            <th>価格</th>
                            <th>数量</th>
                            <th>取引所</th>
                            <th>価格</th>
                            <th>数量</th>
                        </tr>
                    </thead>
                    <tbody class="text-white">
                    @if(count($orders) > 0)
                        @foreach($orders as $order)
                            <tr>
                                <td>{{date('Y/m/d H:i:s',strtotime($order->created_at))}}</td>
                                <td>{{$order->buy_exchange}}</td>
                                <td>{{$order->buy_rate}}</td>
                                <td>{{$order->buy_amount}}</td>
                                <td>{{$order->sell_exchange}}</td>
                                <td>{{$order->sell_rate}}</td>
                                <td>{{$order->sell_amount}}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan='7' style='text-align:left; display: table-cell;font-size:16px;padding-right:40px;'>
                                <span class="text-white">合計利益（JPY）：{{number_format($total_profit)}}</span>
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="7" class="text-center">データがありません。</td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(function () {
            $(".datepicker").datepicker({
                autoclose: true,
                todayHighlight: true,
                format: 'yyyy/mm/dd',
                language: 'ja'
            });

        });
    </script>
@endsection