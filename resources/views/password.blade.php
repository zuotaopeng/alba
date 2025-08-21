@section('title', 'パスワード変更')

@extends('layouts.app')

@section('style')
<style>

</style>
@endsection

@section('content')
    <div class="row p-3">
        {{--  <div class="col-12">
            <div class="row">
                <div class="col-xl-12">
                    <div class="page-titles style1">
                        <div class="d-flex align-items-center">
                            <h2 class="heading">パスワード変更</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>  --}}

        <div class="col-12 p-5">
            <form method="POST" action="{{route('updatepassword')}}">
                @include('include.errors')
                @include('include.status')
                @csrf
                <div class="row">
                    <div class="col-lg-12 mb-3">
                        <div class="mb-3">
                            <label class="text-label form-label" for="old_password">現在のパスワード</label>
                            <input type="password" name="old_password" id="old_password" class="form-control form-control-custom-3">
                        </div>
                    </div>
                    <div class="col-lg-12 mb-3">
                        <div class="mb-3">
                            <label class="text-label form-label" for="password">新しいパスワード</label>
                            <input type="password" name="password" id="password" class="form-control form-control-custom-3">
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="text-label form-label" for="password_confirmation">新しいパスワード（確認）</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control form-control-custom-3">
                        </div>
                    </div>
                </div>
                        
                <div class="mt-5 mx-auto col-sm-6">
                    <button class="btn btn-primary btn-lg btn-block btn-rounded btn-gradient" type="submit">変更する</button>
                </div>
            </form>
        </div>
    </div>
@endsection