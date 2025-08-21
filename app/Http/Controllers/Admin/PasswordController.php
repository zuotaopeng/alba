<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    public function showUpdatePassword(){
        return view('admin.password');
    }

    public function updatePassword(Request $request){
        $request->validate([
            'password' =>  ['required', 'confirmed'],
        ]);
    }

}
