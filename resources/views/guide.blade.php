@extends('layouts.auth')

@section('style')
<style>
    .authincation {
        background-color: rgb(50, 54, 59);
    }
    .authincation-background {
        background-image: none !important;
    }
    .copyright-color {
        color: #fff !important;
    }

    .carousel-indicators [data-bs-target] {
        background-color: rgb(152,152,152);
        border: none;
        border-radius: 50% !important;
        width: 14px !important;
        height: 14px !important;
        opacity: 1 !important;
    }

    .carousel-indicators .active {
        background: linear-gradient(To bottom, rgb(89, 68, 152), rgb(182, 69, 116) 60%, rgb(232, 63, 77));
    }

    .carousel-inner {
        background: linear-gradient(To right, rgb(89, 68, 152), rgb(182, 69, 116) 70%, rgb(232, 63, 77));
        border-radius: 16px;
        padding: 2px;
    }
    .carousel-item {
        background: rgb(50, 54, 59);;
        border-radius: 16px;
    }
    p {
        color: #fff !important;
    }
    
    .item-content {
        border: none;
        border-radius: 8px;
        height: 200px;
        background: linear-gradient(135deg, rgb(38, 39, 43),rgb(52, 57, 63) 40%);
        box-shadow: 7px 6px 13px 0px #00000080, -5px -5px 8px 0px #ffffff20;
    }
    .item-content-photo {
        background: linear-gradient(To right, rgb(89, 68, 152), rgb(182, 69, 116) 70%, rgb(232, 63, 77));
        border-radius: 8px;
        padding: 1px;
        height: 100%;
    }
    .item-content-photo-inner {
        background: linear-gradient(135deg, rgb(38, 39, 43),rgb(52, 57, 63) 40%);;
        border: 1px solid;
        border-radius: 8px;
        height: 100%;
        text-align: center;
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        flex-direction: column;
    }
    .item-content-photo-inner img {
        width: auto;
        height: 100px;
    }

    .auth-form {
        padding: 1.8rem !important;
    }
    .guide-body {
        height: calc(100vh - 160px);
        overflow: scroll;
    }
</style>
@endsection

@section('content')
    <div class="row justify-content-center h-100">
        <div class="col-md-6">
            <div class="row no-gutters">
                <div class="col-xl-12">
                    <div class="auth-form page-r-logo mb-5">
                        <div class="nav-header mt-4">
                            <div class="d-flex justify-content-between">
                                <a href="{{url('/')}}" class="fs-32 text-white"><i class="la la-angle-left"></i></a>
                                <a href="{{url('/')}}" class="fs-28 text-white text-decoration-underline">SKIP</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row no-gutters guide-body">
                <div class="col-xl-12 d-flex justify-content-center">
                    <div class="guide_content">
                        <div class="">
                            <div id="guideSlide" class="carousel slide" data-bs-ride="carousel" data-bs-interval="false" data-bs-wrap="false">
                                <div class="carousel-indicators">
                                    <button type="button" data-bs-target="#guideSlide" data-bs-slide-to="0" @if($tab == '1') class="active" aria-current="true" @endif aria-label="Slide 1"></button>
                                    <button type="button" data-bs-target="#guideSlide" data-bs-slide-to="1" @if($tab == '2') class="active" aria-current="true" @endif aria-label="Slide 2"></button>
                                </div>
                                <div class="carousel-inner">
                                    <div class="carousel-item @if($tab == '1') active @endif">
                                        <p class="text-center mt-4 fs-3 mb-0">ようこそRAGNAROKへ</p>
                                        <p class="text-center mb-5 fs-3">登録が完了しました</p>

                                        <div class="d-flex justify-content-center mb-5">
                                            <div class="w-50 p-4 item-content mb-5">
                                                <div class="item-content-photo">
                                                    <div class="item-content-photo-inner">
                                                        <div class="d-block">
                                                            <img src="{{asset('ui/images/guide_user.png')}}">
                                                        </div>
                                                        <span class="mt-2 fs-4">〇〇様</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="carousel-item @if($tab == '2') active @endif">
                                        <p class="text-center mt-4 fs-3 mb-0">連絡を忘れずに！</p>
                                        <p class="text-center mb-5 fs-3">アクセスできません！</p>

                                        <div class="d-flex justify-content-center mb-5">
                                            <div class="w-50 p-4 item-content mb-5">
                                                <div class="item-content-photo">
                                                    <div class="item-content-photo-inner">
                                                        <div class="d-block">
                                                            <img src="{{asset('ui/images/guide_lock.png')}}">
                                                        </div>
                                                        <span class="mt-2 fs-4 text-muted">LOCK</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="">
                            <div class="text-center pt-4 mx-5" id="btn_slide1_extend">
                                <p class="fs-4">アカウント承認が完了するとアクセスが可能になります。</p>
                                <p class="fs-4">担当者へLINE・お電話にて登録が完了した事をお伝えください。</p>

                                <button class="btn btn-primary btn-rounded btn-lg btn-gradient w-50" type="button" data-bs-target="#guideSlide" data-bs-slide="next">次へ</button>
                            </div>
                            <div class="text-center pt-4  mx-5" id="btn_slide2_extend" style="display: none">
                                <p class="fs-4">承認後に連絡がきましたらトップ画面より再度ログインをお願いします。</p>
                                <p class="fs-4">承認がないとアクセスができませんのでご注意ください。</p>                                    
                                <a href="{{ url('/') }}" class="btn btn-primary btn-rounded btn-lg btn-gradient w-50">TOPへ</a>
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
var guideSlide = document.getElementById('guideSlide')

guideSlide.addEventListener('slide.bs.carousel', function (obj) {
    $("#btn_slide1_extend").show();
    $("#btn_slide2_extend").hide();
    if(obj.to == 1) {
        $("#btn_slide2_extend").show();
        $("#btn_slide1_extend").hide();
    }
})
</script>
@endsection