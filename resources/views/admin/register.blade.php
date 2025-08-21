@extends('layouts.admin')

@section('content')

    <div class="row">
        <div class="col-xl-12">
            <div class="row">
                <div class="col-xl-12">
                    <div class="page-titles style1">
                        <div class="d-flex align-items-center">
                            <h2 class="heading">ユーザー登録</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    @include('include.status')
                    <form action="{{route('admin.register')}}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name"><strong>名前</strong></label>
                            <input type="text" name="name" id="name" class="form-control" value="{{old('name')}}" placeholder="高木　太郎">
                            @error('name')
                                <span class="text-danger" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="email"><strong>メールアドレス</strong></label>
                            <input type="email" name="email" id="email" class="form-control" value="{{old('email')}}" placeholder="hello@example.com">
                            @error('email')
                                <span class="text-danger" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="phone"><strong>電話番号</strong></label>
                            <input type="text" name="phone" id="phone" class="form-control" value="{{old('phone')}}" placeholder="080-1234-5678">
                            @error('phone')
                                <span class="text-danger" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="password"><strong>パスワード</strong></label>
                            <input type="password" name="password" id="password" class="form-control">
                            @error('phone')
                                <span class="text-danger" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation"><strong>パスワード（確認用）</strong></label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="phone"><strong>担当者</strong></label>
                            <input type="text" name="staff" id="staff" class="form-control" value="{{old('staff')}}" placeholder="佐藤 太郎">
                            @error('staff')
                                <span class="text-danger" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary btn-block">登録</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection