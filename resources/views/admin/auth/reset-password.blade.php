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
                            <h4 class="text-center mb-4">パスワード再設定（管理者）</h4>
                            @include('include.errors')
                            @include('include.status')
                            <form action="{{ route('admin.password.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="token" value="{{ $request->route('token') }}">
                                <div class="mb-3">
                                    <label for="email"><strong>メールアドレス</strong></label>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="hello@example.com">
                                    @error('email')
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="password"><strong>パスワード</strong></label>
                                    <input type="password" id="password" name="password" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label for="password_confirmation"><strong>パスワード（確認）</strong></label>
                                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control">
                                </div>
                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-info btn-block">パスワード再設定</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection