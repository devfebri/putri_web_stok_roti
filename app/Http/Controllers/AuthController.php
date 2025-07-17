<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserAuthVerifyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\ApiResponser;

class AuthController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    public function proses_login(Request $request) : RedirectResponse
    {
        // dd($request->all());
        if (Auth::attempt(['username' => $request->username, 'password' => $request->password]) ) {
            // dd(Auth::user()->role);
            return redirect(route(auth()->user()->role . '_dashboard'))->with('pesan', 'Selamat datang kembali "' . auth()->user()->name . '"');
        } else {
            return redirect('/login')->with('gagal', 'Periksa Username dan Password anda');
        }
    }

    public function loginApi(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);
        if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
            // dd(Auth::user()->role);
            $token = Auth::user()->createToken('auth_token')->plainTextToken;
            $user=array_merge(Auth::user()->toArray(), ['token' => $token]);
            return response()->json([
                'status' => true,
                'message' => 'Login Berhasil',
                'data' => $user
            ],200);
        }
        return response()->json([
            'status' => true,
            'message' => 'Periksa Username dan Password anda'
        ],401);
        
    }

    public function logout(Request $request) : RedirectResponse
    {
        Auth::logout();
        return redirect('/login');
    }

}
