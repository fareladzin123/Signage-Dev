<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\User;
use App\Aktivasi;
use Carbon\Carbon;
use App\Mail\UserActivation;

class AuthController extends Controller
{
    /**
     * Load login page
     * @return Resource\Views\login.blade.php
     */   
    public function indexLogin() 
    {
        return view('login');
    }

    /**
     * Checking credentials
     * @return Resource\Views\Admin\
     * OR
     * @return Resource\Views\User\
     */
    public function checkLogin(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $userId = Auth::user()->id;
            $status = Aktivasi::where('user_id', $userId)->select('status')->get();

            if ($status[0]->status == true) {
                if (Auth::user()->tipeClient == 'user') {
                    return redirect('/user');
                } else if (Auth::user()->tipeClient == 'admin') {
                    return redirect('/admin');
                }
            } else {
                return redirect('/login')->with ('alert-fail','Tolong aktivasi akun!');
            }
        } else {
            return redirect('/login')->with('alert-fail','Email atau password salah!');
        }
    }

    /**
     * Load register page
     * @return Resource\Views\signUp.blade.php
     */   
    public function indexRegister() 
    {
        return view('signUp');
    }

    /**
     * Store register form
     * @return App\Http\Controllers\AuthController->indexActivation
     */
    public function postRegister(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'username' => 'required',
            'alamat' => 'required',
            'hp' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'repassword' => 'required|same:password',
        ]);

        $data = new User();
        $data->nama = $request->name;
        $data->username = $request->username;
        $data->alamat = $request->alamat;
        $data->hp = $request->hp;
        $data->tipeClient = 1;
        $data->dateTime = Carbon::now();
        $data->email = $request->email;
        $data->password = bcrypt($request->password);
        $data->save();

        $aktivasi = new Aktivasi();
        $aktivasi->user_id = $data->id;
        $aktivasi->tanggal = Carbon::now();
        $aktivasi->kode = str_random(10);
        $aktivasi->status = 0;
        $aktivasi->save();
        
        Mail::to($data->email)->send(new UserActivation($data, $aktivasi)); 
        return redirect('activation');
    }

    /**
     * View page activation
     * @return Resource\Views\aktivasi.blade.php
     */
    public function indexActivation()
    {
        return view('aktivasi');
    }
    
    /**
     * Activate the user
     * @return App\Http\Controllers\AuthController->indexLogin
    */
    public function checkActivation(Request $request)
    {
        $this->validate($request, [
            'kode' => 'required',
        ]);

        $aktivasi = Aktivasi::where('kode', $request->kode)->first();
        if(empty($aktivasi)) {
            return redirect('activation')->with('alert-fail', 'Masukan kode yang benar!');
        } else {
            if($aktivasi->status == 1) {
                return redirect('activation')->with('alert-fail', 'Akun ini sudah diaktivasi sebelumnya!');
            } else {
                if($request->kode == $aktivasi->kode) {
                    $aktivasi->status = 1;
                    $aktivasi->save();
                    
                    return redirect('login')->with('alert-success', 'Akun berhasil di aktivasi');
                } else {
                    return redirect('activation')->with('alert-fail', 'Masukan kode yang benar!');
                }   
            }
        }
    }

    /**
     * Destroy user session
     * @return App\Http\Controllers\AuthController->indexLogin
     */
    public function logout()
    {
        Auth::logout();
        return redirect('login')->with('alert-logout','Berhasil Logout!');
    }
}
