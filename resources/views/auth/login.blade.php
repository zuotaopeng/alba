@extends('layouts.auth')
@section('style')
<style>
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
    <div class="row justify-content-center align-items-center h-45" style="background: #2E2F34;">
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
    <div class="row justify-content-center h-50 background-modal">
        <div class="col-md-6 px-5">
            <h4 class="text-center text-white mt-5 mb-4 pb-3 fs-36">Login</h4>
            @include('include.status')
            <form action="{{route('login')}}" method="POST" autocomplete="off">
                @csrf
                <div class="mb-3">
                    <input type="email" name="email" id="email" class="form-control form-control-custom" style="padding-left: 5px;" placeholder="メールアドレス">
                    @error('email')
                        <span class="text-danger" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="mb-3">
                    <input type="password" name="password" id="password" class="form-control form-control-custom" style="padding-left: 5px;" placeholder="パスワード">
                </div>

                <div class="text-center mt-4 mx-auto">
                    <button type="submit" class="btn btn-primary btn-block btn-rounded btn-gradient btn-lg">ログイン</button>
                </div>
                
                <div class="new-account mt-3 text-center">
                    <p class="text-white">※パスワードをお忘れですか？再発行は<a style="color: #ffffff" href="{{route('password.request')}}">こちら</a></p>
                </div>
            </form>
        </div>

        {{--  <div class="footer footer-auth background-modal">
            <div class="copyright">
                <p class="text-white copyright-color fw-bold">©RAGNAROK</p>
            </div>
        </div>  --}}
    </div>
@endsection