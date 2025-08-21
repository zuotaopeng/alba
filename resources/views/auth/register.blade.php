@extends('layouts.auth')
@section('style')
<style>
    .authincation-background {
        background-image: url("../ui/images/background_register.png") !important;
        background-size: cover;
    }
    .footer {
        height: 5%;
        background-color: rgba(0,0,0, 0.45) !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    @media (min-width: 992px) {
        .page-r-logo img {
            width: 300px;
        }
    }
</style>
@endsection
@section('content')
    <div class="row justify-content-center h-45" style="background: #2E2F34;">
        <div class="col-md-6">
            <div class="authincation-content">
                <div class="row no-gutters">
                    <div class="col-xl-12">
                        <div class="auth-form page-r-logo">
                            <div class="nav-header mt-4">
                                <a href="{{url('home')}}" class="fs-32 text-white"><i class="la la-angle-left"></i></a>
                            </div>

                            <div>
                                <div class="text-center">
                                    <img src="{{asset('ui/images/logo_home.png')}}" class="logo m-auto" alt="logo">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row justify-content-center background-modal h-50 overflow-scroll">
        <div class="col-md-6 px-5">
            <h4 class="text-center text-white mt-5 mb-4 pb-3 fs-36">新規登録</h4>
            @include('include.status')
            @include('include.errors')
            <form action="{{route('register')}}" method="POST" autocomplete="off" id="frm_register">
                @csrf
                <div class="mb-3">
                    <input type="text" name="name" id="name" class="form-control form-control-custom" placeholder="お名前">
                    @error('name')
                        <span class="text-danger" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="mb-3">
                    <input type="email" name="email" id="email" class="form-control form-control-custom" placeholder="メール">
                    @error('email')
                        <span class="text-danger" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="mb-3">
                    <input type="text" name="phone" id="phone" class="form-control form-control-custom" value="{{old('phone')}}" placeholder="電話番号">
                    @error('phone')
                        <span class="text-danger" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="mb-3">
                    <input type="password" name="password" id="password" class="form-control form-control-custom" placeholder="パスワード">
                    @error('password')
                        <span class="text-danger" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div> 
                <div class="mb-3">
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control form-control-custom" placeholder="パスワード（確認）">
                </div>
                <div class="text-center mt-3 pb-3 mx-auto">
                    <button type="button" id="btn_register" class="btn btn-primary btn-block btn-rounded btn-gradient btn-lg">登録する</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
<script>
    $("#btn_register").on("click", function(){
        // if(!$("#agree_licenses").is(':checked')){
        //     $("#license_alert").html("利用規約・プライバシーポリシーに同意してください。");
        //     return false;
        // }
        $("#frm_register").submit();
    });
</script>
@endsection