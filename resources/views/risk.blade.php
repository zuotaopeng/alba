@section('title', '')
@extends('layouts.app')

@section('style')
    <style>
        .card {
            border-radius: 20px !important;
            box-shadow: 6px 6px 10px -5px #00000080;
        }
        .progress-content  {
            /* border: 1px solid white; */
            background: #292b30;
            border-radius: 20px;
            box-shadow: inset 5px 6px 2px #00000050, inset -3px -4px 1px #ffffff30;
        }

        /******** custom range ******/
        .range {
            display: flex;
            align-items: center;
            background-color: #202226;
            /*background-image: linear-gradient(to right, rgba(255,255,255,0), rgba(255,255,255,0.7));*/
            border-radius: 0.7rem;
        }

        .range-input {
            -webkit-appearance: none;
            appearance: none; 
            width: 100%;
            cursor: pointer;
            outline: none;
            border-radius: 0.7rem;
            height: 8px;
            background: linear-gradient(to right, rgb(89, 68, 152), rgb(232, 63, 77) 20%, transparent 20%);
        }
          
        .range-input::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none; 
            height: 16px;
            width: 16px;
            background-color: #E7E7E7;
            box-shadow: 2px 2px 4px 0px #00000080;
            border-radius: 50%;
            border: none;
            transition: .2s ease-in-out;
        }
        
        .range-input::-moz-range-thumb {
            height: 16px;
            width: 16px;
            background-color: #E7E7E7;
            box-shadow: 2px 2px 4px 0px #00000080;
            border-radius: 50%;
            border: none;
            transition: .2s ease-in-out;
        }
        .range_value {
            font-size: 26px;    
            width: 50px;
            text-align: center;
        }

        /********* form range **********/
        

        /******** general progress *********/
        .progress {
            height: 16px;
            border-radius: 0.7rem;
            background-color: #28a6ff !important;
            background-image: linear-gradient(to right, rgba(255,255,255,0), rgba(255,255,255,0.7)) !important;
            background-repeat: repeat !important;
        }

        /********* pro progress bar ******/
        .progress_bar .pro-bar {
            height: 16px;
            border-radius: 0.7rem;
            background-color: #28a6ff !important;
            background-image: linear-gradient(to right, rgba(255,255,255,0), rgba(255,255,255,0.7)) !important;
            background-repeat: repeat !important;
            position: relative;
        }
        .progress_bar .progress-bar-inner {
            background-color: hsl(0, 0%, 88%);
            display: block;
            width: 0;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            transition: width 1s linear 0s;
            border-radius: 0.7rem;
        }
        .progress_bar .progress-bar-inner:after {
            content: "";
            width: 24px;
            height: 24px;
            background-color: #E7E7E7;
            border-radius: 50%;
            position: absolute;
            right: -10px;
            top: -4px;
            box-shadow: 2px 2px 4px 0px #00000080;
        }
    </style>
@endsection

@section('content')
    <div class="tab-pane fade show active px-3 py-3 mt-5" id="currency_group" role="tabpanel">
        <div class="card-body pt-0">
            <ul class="nav nav-pills justify-content-center mb-4">
                <li class="nav-item">
                    <a href="#btc_tab" class="nav-link nav-link-text active" data-currency="btc" data-bs-toggle="tab" aria-expanded="false"><span class="ms-1 fs-4">BTC</span></a>
                </li>
                <li class="nav-item">
                    <a href="#eth_tab" class="nav-link nav-link-text" data-currency="eth" data-bs-toggle="tab" aria-expanded="false"><span class="ms-1 fs-4">ETH</span></a>
                </li>
                <li class="nav-item">
                    <a href="#xrp_tab" class="nav-link nav-link-text" data-currency="xrp" data-bs-toggle="tab" aria-expanded="true"><span class="ms-1 fs-4">XRP</span></a>
                </li>
            </ul>
            <div class="tab-content">
                <div id="btc_tab" class="tab-pane active">
                    <div class="row pt-2 px-3">
                        <div class="col-12">
                            <div class="card app-background-gradient-to-right p-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check custom-checkbox checkbox-warning d-flex align-items-center w-100">
                                        <div class="checkbox-span-group">
                                            <span class="text-white">ロスカット</span>
                                        </div>
                                    </div>
                                    <div class="toggle-custom1">
                                        <input type="checkbox" id="loss_cut" name="loss_cut" data-toggle="toggle" data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark"  data-tyle="dark" @if(Auth::user()->losscut == 'on') checked @endif>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 pt-5">
                            <div class="box-custom p-4">
                                <div class="d-flex justify-content-between align-items-center mx-2 mb-3">
                                    <span class="">ロスカットライン</span>
                                    <span id="loss_cut_value" class="">{{Auth::user()->losscut_line}}%</span>
                                </div>

                                <div class="p-3 progress-content">
                                   <div class="range">
                                        <input type="range" min="0" max="100" @if(empty(Auth::user()->losscut_line)) value="20" @else value="{{Auth::user()->losscut_line}}" @endif id="loss_cut_limit" class="range-input" />
                                   </div>
                                </div>
                                <div class="text-center mt-3 mb-4">
                                    <small>スイッチをオンにした瞬間を起点とした<br/>任意の下落率で全ポジションを強制的に決済します</small>
                                </div>
                                <div class="toggle-custom1 d-flex justify-content-end align-items-center mb-4">
                                    <label for="loss_cut_recommend" class="mb-0 me-4 fs-4">推奨設定</label>
                                    <input type="checkbox" id="loss_cut_recommend" name="loss_cut_recommend" data-toggle="toggle" data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="eth_tab" class="tab-pane">
                    <div class="row pt-2 px-3">
                        <div class="col-12">
                            <div class="card app-background-gradient-to-right p-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check custom-checkbox checkbox-warning d-flex align-items-center w-100">
                                        <div class="checkbox-span-group">
                                            <span class="text-white">ロスカット</span>
                                        </div>
                                    </div>
                                    <div class="toggle-custom1">
                                        <input type="checkbox" id="loss_cut_eth" name="loss_cut_eth" data-toggle="toggle" data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark" @if(Auth::user()->losscut_eth == 'on') checked @endif>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 pt-5">
                            <div class="box-custom p-4">
                                <div class="d-flex justify-content-between align-items-center mx-2 mb-3">
                                    <span class="">ロスカットライン</span>
                                    <span class="" id="loss_cut_value_eth">{{Auth::user()->losscut_line_eth}}%</span>
                                </div>
                                <div class="p-3 progress-content">
                                    <div class="range">
                                        <input type="range" min="0" max="100" @if(empty(Auth::user()->losscut_line_eth)) value="20" @else value="{{Auth::user()->losscut_line_eth}}" @endif id="loss_cut_limit_eth" class="range-input" />
                                    </div>
                                </div>
                                <div class="text-center mt-3 mb-4">
                                    <small>スイッチをオンにした瞬間を起点とした<br/>任意の下落率で全ポジションを強制的に決済します</small>
                                </div>
                                <div class="toggle-custom1 d-flex justify-content-end align-items-center mb-4">
                                    <label for="loss_cut_recommend_eth" class="mb-0 me-4 fs-4">推奨設定</label>
                                    <input type="checkbox" id="loss_cut_recommend_eth" name="loss_cut_recommend_eth" data-toggle="toggle" data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="xrp_tab" class="tab-pane">
                    <div class="row pt-2 px-3">
                        <div class="col-12">
                            <div class="card app-background-gradient-to-right p-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check custom-checkbox checkbox-warning d-flex align-items-center w-100">
                                        <div class="checkbox-span-group">
                                            <span class="text-white">ロスカット</span>
                                        </div>
                                    </div>
                                    <div class="toggle-custom1">
                                        <input type="checkbox" id="loss_cut_xrp" name="loss_cut_xrp" data-toggle="toggle" data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark" @if(Auth::user()->losscut_xrp == 'on') checked @endif>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 pt-5">
                            <div class="box-custom p-4">
                                <div class="d-flex justify-content-between align-items-center mx-2 mb-3">
                                    <span class="">ロスカットライン</span>
                                    <span class="" id="loss_cut_value_xrp">{{Auth::user()->losscut_line_xrp}}%</span>
                                </div>
                                <div class="p-3 progress-content">
                                    <div class="range">
                                        <input type="range" min="0" max="100" @if(empty(Auth::user()->losscut_line_xrp)) value="20" @else value="{{Auth::user()->losscut_line_xrp}}" @endif id="loss_cut_limit_xrp" class="range-input" />
                                    </div>
                                </div>
                                <div class="text-center mt-3 mb-4">
                                    <small>スイッチをオンにした瞬間を起点とした<br/>任意の下落率で全ポジションを強制的に決済します</small>
                                </div>
                                <div class="toggle-custom1 d-flex justify-content-end align-items-center mb-4">
                                    <label for="loss_cut_recommend_xrp" class="mb-0 me-4 fs-4">推奨設定</label>
                                    <input type="checkbox" id="loss_cut_recommend_xrp" name="loss_cut_recommend_xrp" data-toggle="toggle" data-on="On" data-off="Off" data-onstyle="primary" data-offstyle="dark">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="toolbar-bottom mt-5 mb-4 mx-auto col-10 col-sm-6 text-center">
        <button class="btn btn-primary btn-lg btn-block btn-rounded btn-gradient" id="save_btn" @if(Auth::user()->approved_losscut == 'no') disabled @endif>保存</button>
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

            const sliderEl = document.querySelector("#loss_cut_limit");
            sliderEl.addEventListener("input", (event) => {
              const tempSliderValue = event.target.value; 
              const progress = (tempSliderValue / sliderEl.max) * 100;
              document.querySelector("#loss_cut_value").textContent = tempSliderValue + "%";
              sliderEl.style.background = `linear-gradient(to right, rgb(89, 68, 152), rgb(232, 63, 77) ${progress}%, transparent ${progress}%)`;
            });

            $('#loss_cut_recommend').on("change", function(c) {
                if($(this).prop("checked")){
                    let new_progress = 25;
                    $("#loss_cut_limit").val(new_progress).change();
                    document.querySelector("#loss_cut_value").textContent = "25%";
                    sliderEl.style.background = `linear-gradient(to right, rgb(89, 68, 152), rgb(232, 63, 77) ${new_progress}%, transparent ${new_progress}%)`;
                }
            });

            const sliderElEth = document.querySelector("#loss_cut_limit_eth");
            sliderElEth.addEventListener("input", (event) => {
                const tempSliderValueEth = event.target.value;
                const progress_eth = (tempSliderValueEth / sliderElEth.max) * 100;
                document.querySelector("#loss_cut_value_eth").textContent = tempSliderValueEth + "%";
                sliderElEth.style.background = `linear-gradient(to right, rgb(89, 68, 152), rgb(232, 63, 77) ${progress_eth}%, transparent ${progress_eth}%)`;
            });
            $('#loss_cut_recommend_eth').on("change", function(c) {
                if($(this).prop("checked")){
                    let new_progress = 25;
                    $("#loss_cut_limit_eth").val(new_progress).change();
                    document.querySelector("#loss_cut_value_eth").textContent = "25%";
                    sliderElEth.style.background = `linear-gradient(to right, rgb(89, 68, 152), rgb(232, 63, 77) ${new_progress}%, transparent ${new_progress}%)`;
                }
            });


            const sliderElXrp = document.querySelector("#loss_cut_limit_xrp");
            sliderElXrp.addEventListener("input", (event) => {
                const tempSliderValueXrp = event.target.value;
                const progress_xrp = (tempSliderValueXrp / sliderElXrp.max) * 100;
                document.querySelector("#loss_cut_value_xrp").textContent = tempSliderValueXrp + "%";
                sliderElXrp.style.background = `linear-gradient(to right, rgb(89, 68, 152), rgb(232, 63, 77) ${progress_xrp}%, transparent ${progress_xrp}%)`;
            });
            $('#loss_cut_recommend_xrp').on("change", function(c) {
                if($(this).prop("checked")){
                    let new_progress = 25;
                    $("#loss_cut_limit_xrp").val(new_progress).change();
                    document.querySelector("#loss_cut_value_xrp").textContent = "25%";
                    sliderElXrp.style.background = `linear-gradient(to right, rgb(89, 68, 152), rgb(232, 63, 77) ${new_progress}%, transparent ${new_progress}%)`;
                }
            });

            sliderEl.style.background = `linear-gradient(to right, rgb(89, 68, 152), rgb(232, 63, 77) {{Auth::user()->losscut_line}}%, transparent {{Auth::user()->losscut_line}}%)`;
            sliderElEth.style.background = `linear-gradient(to right, rgb(89, 68, 152), rgb(232, 63, 77) {{Auth::user()->losscut_line_eth}}%, transparent {{Auth::user()->losscut_line_eth}}%)`;
            sliderElXrp.style.background = `linear-gradient(to right, rgb(89, 68, 152), rgb(232, 63, 77) {{Auth::user()->losscut_line_xrp}}%, transparent {{Auth::user()->losscut_line_xrp}}%)`;

            $('#save_btn').on("click", function(e) {
                e.preventDefault();
                const losscutline = jQuery.trim($("#loss_cut_limit").val());
                const losscutline_eth = jQuery.trim($("#loss_cut_limit_eth").val());
                const losscutline_xrp = jQuery.trim($("#loss_cut_limit_xrp").val());
                let losscut = "off";
                if($('input[name=loss_cut]:checked').val() === 'on'){
                    losscut = "on";
                }
                let losscut_eth = "off";
                if($('input[name=loss_cut_eth]:checked').val() === 'on'){
                    losscut_eth = "on";
                }
                let losscut_xrp = "off";
                if($('input[name=loss_cut_xrp]:checked').val() === 'on'){
                    losscut_xrp = "on";
                }
                const requestData = {};
                requestData.losscut = losscut;
                requestData.losscut_eth = losscut_eth;
                requestData.losscut_xrp = losscut_xrp;
                requestData.losscutline = losscutline;
                requestData.losscutline_eth = losscutline_eth;
                requestData.losscutline_xrp = losscutline_xrp;
                console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: "{{route('ajaxsaverisk')}}",
                    data: requestData
                }).done(function (response) {
                    let result = response.result;
                    if (result === "OK") {
                        alert("保存しました");
                    } else {
                        alert("エラーが発生しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });

        });            
    </script>
@endsection

