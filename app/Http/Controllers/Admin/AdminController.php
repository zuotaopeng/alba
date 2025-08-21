<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AdminController extends Controller
{
    public function showUserList(Request $request){
        $pagesize = 20;
        $users_query = DB::table('users');
        $name = $request->input('name');
        $email = $request->input('email');
        if(!empty($name)){
            $users_query->where('name','LIKE',"%$name%");
        }
        if(!empty($email)){
            $users_query->where('email','LIKE',"%$email%");
        }
        $users = $users_query->paginate($pagesize);
        session()->flashInput($request->input());
        return view('admin.userlist',compact('users'));
    }

    public function showRegister(){
        return view('admin.register');
    }

    public function register(Request $request){
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:15'],
            'staff' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        $user = new User();
        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');
        $phone = $request->input('phone');
        $staff = $request->input('staff');
        $user->name = $name;
        $user->email = $email;
        $user->phone = $phone;
        $user->staff = $staff;
        $user->pass_plain = $password;
        $user->password = Hash::make($password);
        $user->save();
        return redirect(route('admin.userlist'))->with('status','登録しました。');
    }

    public function showUpdateUser($id){
        $user = User::findOrFail($id);
        return view('admin.edit',compact('user'));
    }

    public function updateUser(Request $request,$id){
        $user = User::findOrFail($id);
        $request->validate([
            'name' => 'required',
            'email' => 'required|unique:users,email,'.$id
        ]);
        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');
        $user->name = $name;
        $user->email = $email;
        if(!empty($password)){
            $user->password = Hash::make($password);
            $user->pass_plain = $password;
        }
        $user->save();
        return redirect(route('admin.userlist'))->with('status','更新しました。');
    }

    public function deleteUser($id){
        $user = User::findOrFail($id);
        $user->delete();
        return redirect(route('admin.userlist'))->with('status','削除しました。');
    }

    public function showUserBalance($id){
        $user = User::findOrFail($id);
        return view('admin.balance',compact('user'));
    }


    public function ajaxUpdateApproved(Request $request){
        $request->validate([
            'currency' => 'required',
            'user_id' => 'required',
            'approved' => 'required'
        ]);
        $currency = $request->input('currency');
        $user_id = $request->input('user_id');
        $approved = $request->input('approved');
        $user = User::findOrFail($user_id);
        if($currency == 'btc'){
            $user->approved_btc = $approved;
        }else if($currency == 'login'){
            $user->approved = $approved;
        }else if($currency == 'eth'){
            $user->approved_eth = $approved;
        }else if($currency == 'xrp'){
            $user->approved_xrp = $approved;
        }else if($currency == 'ltc'){
            $user->approved_ltc = $approved;
        }else if($currency == 'bch'){
            $user->approved_bch = $approved;
        }else if($currency == 'oversea'){
            $user->approved_oversea = $approved;
        }else if ($currency == 'losscut'){
            $user->approved_losscut = $approved;
        }
        $user->save();
        if($user->approved == 'no'){
            $user->approved_btc = 'no';
            $user->approved_eth = 'no';
            $user->approved_xrp = 'no';
            $user->approved_ltc = 'no';
            $user->approved_bch = 'no';
            $user->approved_oversea = 'no';
            $user->save();
        }

        return response()->json(['data'=>'success']);
    }

    public function ajaxUpdateStaffMemo(Request $request){
        $request->validate([
            'user_id' => 'required',
            'category' => 'required'
        ]);
        $user_id = $request->input('user_id');
        $user = User::findOrFail($user_id);
        $category = $request->input('category');
        if($category == 'staff'){
            $user->staff = $request->input('content');
        }else if($category == 'memo'){
            $user->memo = $request->input('content');
        }
        $user->save();
        return response()->json(['data'=>'success']);
    }


}
