@extends('layouts.auth-admin')

@section('content')
    <div class="row justify-content-center h-100 align-items-center">
        <div class="col-md-6">
            <div class="authincation-content">
                <div class="row no-gutters">
                    <div class="col-xl-12">
                        <div class="auth-form page-r-logo">
                            <div class="text-center mb-4">
                                <img src="{{asset('ui/images/logo_home.png')}}" class="light-logo m-auto" alt="logo">
                            </div>
                            <h4 class="text-center mb-4">ログイン（管理者）</h4>
                            @include('include.status')
                            <form action="{{route('admin.login')}}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="email"><strong>メールアドレス</strong></label>
                                    <input type="email" name="email" id="email" class="form-control">
                                    @error('email')
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="password"><strong>パスワード</strong></label>
                                    <input type="password" name="password" id="password" class="form-control">
                                </div>
                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-info btn-block">ログイン</button>
                                </div>
                            </form>
                            <div class="new-account mt-3">
                                <p><a style="color: currentColor" href="{{route('admin.password.request')}}">パスワードをお忘れの方</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection