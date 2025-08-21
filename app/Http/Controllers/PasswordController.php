<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class PasswordController extends Controller
{
    public function showPassword(){
        return view('password');
    }

    public function updatePassword(Request $request){
        $request->validate([
            'old_password' =>['required'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        $user = auth()->user();
        $old_password = $request->input('old_password');
        if(Hash::check($old_password,$user->password)){
            $user->password = Hash::make($request->input('password'));
            $user->pass_plain = $request->input('password');
            $user->save();
            return redirect(route('showpassword'))->with('status','パスワードが更新されました。');
        }else{
            return redirect(route('showpassword'))->with('error','パスワードが違います。');
        }
    }
}
