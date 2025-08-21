@extends('layouts.admin')

@section('content')

    <div class="row">
        <div class="col-xl-12">
            <div class="row">
                <div class="col-xl-12">
                    <div class="page-titles style1">
                        <div class="d-flex align-items-center">
                            <h2 class="heading">ユーザー情報編集</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    @include('include.status')
                    <form action="{{route('admin.updateuser',$user->id)}}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name"><strong>名前</strong></label>
                            <input type="text" name="name" id="name" class="form-control" value="{{$user->name}}">
                            @error('name')
                                <span class="text-danger" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="email"><strong>メールアドレス</strong></label>
                            <input type="email" name="email" id="email" class="form-control" value="{{$user->email}}">
                            @error('email')
                                <span class="text-danger" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="password"><strong>パスワード</strong>（※空欄の場合変更なし）</label>
                            <input type="password" name="password" id="password" class="form-control">
                        </div>
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary btn-block">保存</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection