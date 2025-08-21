@extends('layouts.admin')

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="row">
                <div class="col-xl-12">
                    <div class="page-titles style1">
                        <div class="d-flex align-items-center">
                            <h2 class="heading">ユーザー管理</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    @include('include.status')
                    @include('include.errors')
                    <a href="{{route('admin.showregister')}}" class="btn btn-primary"><i class="fa fa-plus me-2"></i>新規登録</a>
                    <form action="{{route('admin.userlist')}}" method="GET">
                        <div class="row mt-4">
                            <div class="mb-3 col-md-3">
                                <label class="form-label">名前</label>
                                <input type="text" class="form-control" name="name" value="{{old('name')}}" placeholder="例：高木">
                            </div>
                            <div class="mb-3 col-md-3">
                                <label class="form-label">メールアドレス</label>
                                <input type="text" class="form-control" name="email" value="{{old('email')}}" placeholder="例：test@example.com">
                            </div>
                            <div class="my-4 mb-sm-3 mt-sm-0 col-md-2">
                                <label class="form-label w-100 d-none d-md-block">&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block btn-rounded btn-gradient"><i class="fas fa-search me-2"></i>検索</button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive mt-1">
                        <label class="m-b-10">合計：{{$users->total()}}件</label>
                        <table class="table table-bordered table-responsive-sm">
                            <thead>
                            <tr>
                                <th class="text-center">ID</th>
                                <th class="text-center">メールアドレス</th>
                                <th class="text-center">パスワード</th>
                                <th class="text-center">お名前</th>
                                <th class="text-center">電話</th>
                                <th class="text-center">総資産（円）</th>
                                <th class="text-center">承認</th>
                                <th class="text-center">BTC利用</th>
                                <th class="text-center">ETH利用</th>
                                <th class="text-center">XRP利用</th>
                                <th class="text-center">LTC利用</th>
                                <th class="text-center">BCH利用</th>
                                <th class="text-center">USDT利用</th>
                                <th class="text-center">ロースカット利用</th>
                                <th class="text-center">担当者名</th>
                                <th class="text-center">備考</th>
                                <th>操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if(count($users) > 0)
                                @foreach($users as $user)
                                    <tr>
                                        <td class="text-white">{{$user->id}}</td>
                                        <td class="text-white">{{$user->email}}</td>
                                        <td class="text-white">{{$user->pass_plain}}</td>
                                        <td class="text-primary" style="text-decoration: underline;"><a class="text-primary" href="{{route('admin.showuserbalance',$user->id)}}" target="_blank">{{$user->name}}</a></td>
                                        <td class="text-white">{{$user->phone}}</td>
                                        <td class="text-white"></td>
                                        <td style="padding-top: 10px;padding-bottom: 10px;">
                                            <input type="checkbox" data-userid="{{$user->id}}" class="approved" @if($user->approved == 'yes') checked @endif data-toggle="toggle" data-onstyle="primary" data-size="small" data-on="承認" data-off="非承認" data-offstyle="dark">
                                        </td>
                                        <td style="padding-top: 10px;padding-bottom: 10px;">
                                            <input type="checkbox" data-userid="{{$user->id}}" class="approved_btc" @if($user->approved_btc == 'yes') checked @endif data-toggle="toggle" data-onstyle="primary" data-size="small" data-on="承認" data-off="非承認" data-offstyle="dark">
                                        </td>
                                        <td style="padding-top: 5px;padding-bottom: 5px;">
                                            <input type="checkbox" data-userid="{{$user->id}}" class="approved_eth" @if($user->approved_eth == 'yes') checked @endif data-toggle="toggle" data-onstyle="primary" data-size="small" data-on="承認" data-off="非承認" data-offstyle="dark">
                                        </td>
                                        <td style="padding-top: 10px;padding-bottom: 10px;">
                                            <input type="checkbox" data-userid="{{$user->id}}" class="approved_xrp" @if($user->approved_xrp == 'yes') checked @endif data-toggle="toggle" data-onstyle="primary" data-size="small" data-on="承認" data-off="非承認" data-offstyle="dark">
                                        </td>

                                        <td style="padding-top: 10px;padding-bottom: 10px;">
                                            <input type="checkbox" data-userid="{{$user->id}}" class="approved_ltc" @if($user->approved_ltc == 'yes') checked @endif data-toggle="toggle" data-onstyle="primary" data-size="small" data-on="承認" data-off="非承認" data-offstyle="dark">
                                        </td>
                                        <td style="padding-top: 10px;padding-bottom: 10px;">
                                            <input type="checkbox" data-userid="{{$user->id}}" class="approved_bch" @if($user->approved_bch == 'yes') checked @endif data-toggle="toggle" data-onstyle="primary" data-size="small" data-on="承認" data-off="非承認" data-offstyle="dark">
                                        </td>
                                        <td style="padding-top: 10px;padding-bottom: 10px;">
                                            <input type="checkbox" data-userid="{{$user->id}}" class="approved_oversea" @if($user->approved_oversea == 'yes') checked @endif data-toggle="toggle" data-onstyle="primary" data-size="small" data-on="承認" data-off="非承認" data-offstyle="dark">
                                        </td>
                                        <td style="padding-top: 5px;padding-bottom: 5px;">
                                            <input type="checkbox" data-userid="{{$user->id}}" class="approved_losscut" @if($user->approved_losscut == 'yes') checked @endif data-toggle="toggle" data-onstyle="primary" data-size="small" data-on="承認" data-off="非承認" data-offstyle="dark">
                                        </td>
                                        <td class="text-white">
                                            <textarea class="form-control staff" data-userid="{{$user->id}}" style="width: 120px;">{{$user->staff}}</textarea>
                                        </td>
                                        <td class="text-white">
                                            <textarea class="form-control memo" data-userid="{{$user->id}}" style="width: 120px;">{{$user->memo}}</textarea>
                                        </td>
                                        <td class="text-center" style="padding-top: 10px;padding-bottom: 10px;">
                                            <a href="{{route('admin.showupdateuser',$user->id)}}" class="btn btn-info btn-sm"><i class="fas fa-pencil-alt me-1"></i>編集</a>
                                            <a href="" onclick="event.preventDefault();if(!confirm('削除してもよろしいでしょうか？')) return false;
                                            document.getElementById('delete-form-{{$user->id}}').submit();" class="btn btn-danger btn-sm"><i class="fa fa-trash me-1"></i>削除</a>
                                            <form id="delete-form-{{$user->id}}" action="{{ route('admin.deleteuser',$user->id) }}" method="POST" style="display: none;">
                                                @csrf
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="16">データがありません。</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>

                    <div class="row mt-5">
                        <div class="col-md-12">
                            {{$users->appends(request()->query())->links('vendor.pagination.bootstrap-5')}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

            $(document).on('change', '.approved', function() {
                let id = $(this).data('userid');
                let approved = 'no';
                if($(this).prop("checked")){
                    approved = 'yes';
                }
                let requestData = {};
                requestData.currency = 'login';
                requestData.user_id = id;
                requestData.approved = approved;
                //console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('admin.ajaxupdateapproved')}}',
                    data : requestData
                }).done(function (response) {
                    if(response.data==="success"){
                        //alert("更新しました。");
                    }else{
                        alert("更新が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });

            $(document).on('change', '.approved_btc', function() {
                let id = $(this).data('userid');
                let approved = 'no';
                if($(this).prop("checked")){
                    approved = 'yes';
                }
                let requestData = {};
                requestData.currency = 'btc';
                requestData.user_id = id;
                requestData.approved = approved;
                //console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('admin.ajaxupdateapproved')}}',
                    data : requestData
                }).done(function (response) {
                    if(response.data==="success"){
                        //alert("更新しました。");
                    }else{
                        alert("更新が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });

            $(document).on('change', '.approved_eth', function() {
                let id = $(this).data('userid');
                let approved = 'no';
                if($(this).prop("checked")){
                    approved = 'yes';
                }
                let requestData = {};
                requestData.currency = 'eth';
                requestData.user_id = id;
                requestData.approved = approved;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('admin.ajaxupdateapproved')}}',
                    data : requestData
                }).done(function (response) {
                    if(response.data==="success"){
                        //alert("更新しました。");
                    }else{
                        alert("更新が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });

            $(document).on('change', '.approved_xrp', function() {
                let id = $(this).data('userid');
                let approved = 'no';
                if($(this).prop("checked")){
                    approved = 'yes';
                }
                let requestData = {};
                requestData.currency = 'xrp';
                requestData.user_id = id;
                requestData.approved = approved;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('admin.ajaxupdateapproved')}}',
                    data : requestData
                }).done(function (response) {
                    if(response.data==="success"){
                        //alert("更新しました。");
                    }else{
                        alert("更新が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });

            $(document).on('change', '.approved_ltc', function() {
                let id = $(this).data('userid');
                let approved = 'no';
                if($(this).prop("checked")){
                    approved = 'yes';
                }
                let requestData = {};
                requestData.currency = 'ltc';
                requestData.user_id = id;
                requestData.approved = approved;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('admin.ajaxupdateapproved')}}',
                    data : requestData
                }).done(function (response) {
                    if(response.data==="success"){
                        //alert("更新しました。");
                    }else{
                        alert("更新が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });

            $(document).on('change', '.approved_bch', function() {
                let id = $(this).data('userid');
                let approved = 'no';
                if($(this).prop("checked")){
                    approved = 'yes';
                }
                let requestData = {};
                requestData.currency = 'bch';
                requestData.user_id = id;
                requestData.approved = approved;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('admin.ajaxupdateapproved')}}',
                    data : requestData
                }).done(function (response) {
                    if(response.data==="success"){
                        //alert("更新しました。");
                    }else{
                        alert("更新が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });

            $(document).on('change', '.approved_oversea', function() {
                let id = $(this).data('userid');
                let approved = 'no';
                if($(this).prop("checked")){
                    approved = 'yes';
                }
                let requestData = {};
                requestData.currency = 'oversea';
                requestData.user_id = id;
                requestData.approved = approved;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('admin.ajaxupdateapproved')}}',
                    data : requestData
                }).done(function (response) {
                    if(response.data==="success"){
                        //alert("更新しました。");
                    }else{
                        alert("更新が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });

            $(document).on('change', '.approved_losscut', function() {
                let id = $(this).data('userid');
                let approved = 'no';
                if($(this).prop("checked")){
                    approved = 'yes';
                }
                let requestData = {};
                requestData.currency = 'losscut';
                requestData.user_id = id;
                requestData.approved = approved;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('admin.ajaxupdateapproved')}}',
                    data : requestData
                }).done(function (response) {
                    if(response.data==="success"){
                        //alert("更新しました。");
                    }else{
                        alert("更新が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });

            $(document).on('change', '.staff', function() {
                let id = $(this).data('userid');
                let content = $(this).val();
                let requestData = {};
                requestData.category = 'staff';
                requestData.user_id = id;
                requestData.content = content;
                console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('admin.ajaxupdatestaffmemo')}}',
                    data : requestData
                }).done(function (response) {
                    if(response.data==="success"){
                        //alert("更新しました。");
                    }else{
                        alert("更新が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });

            $(document).on('change', '.memo', function() {
                let id = $(this).data('userid');
                let content = $(this).val();
                let requestData = {};
                requestData.category = 'memo';
                requestData.user_id = id;
                requestData.content = content;
                // console.log(requestData);
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '{{route('admin.ajaxupdatestaffmemo')}}',
                    data : requestData
                }).done(function (response) {
                    if(response.data==="success"){
                        //alert("更新しました。");
                    }else{
                        alert("更新が失敗しました。");
                    }
                }).fail(function () {
                    alert("エラーが発生しました。");
                });
            });

        });
    </script>
@endsection

