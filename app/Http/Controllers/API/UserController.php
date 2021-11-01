<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Actions\Fortify\PasswordValidationRules;


class UserController extends Controller
{
    use PasswordValidationRules;
    
    //API login
    public function login(Request $request)
    {
        try {
            //validasi input
            $request->validate(['email' => 'email|required', 'password' => 'required']);
            //cek credntials (login)
            $credentials = request(['email', 'password']);

            if(!Auth::attempt($credentials)){
                return ResponseFormatter::error(['message'=>'Unauthorized'], ['Authentication Failed credential not found', 500]);
            }
            //jika hash tidak sesuai beri pesan error
            $user = User::where('email', $request->email)->first();
            if(!Hash::check($request->password, $user->passwrod)){
                throw new \Exception('Invalid Credential');
            }

            //jika berhasil maka login
            $tokenResult = $user->createToken('authToken')->plainTextToken;
            return ResponseFormatter::success([
                'access_token'=>$tokenResult,
                'token_type'=> 'Bearer',
                'user'=>$user
            ], 'Authenticated');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error
            ], 'Authentication Failed', 500);
            
        }
    }

    //API Register
    public function register(Request $request)
    {
        try {
            //validasi cek input
            $request -> validate([
                'name'=> ['required', 'string', 'max:225'],
                'email'=>['required', 'string', 'email', 'max:225', 'unique::users'],
                'password'=> $this->passwordRules()
            ]);
            //ambil datanya dan buat
            User::create([
                'name'=>$request->name,
                'email'=>$request->email,
                'address'=>$request->address,
                'houseNumber'=>$request->houseNumber,
                'phoneNumber'=>$request->phoneNumber,
                'city'=>$request->city,
                'password'=>Hash::make($request->password)
            ]);
            //ambil datanya untuk dibuat token biar langsung login
            $user = User::where('email', $request->email)->first();
            //buat tokennya untuk login langusng
            $tokenResult = $user->createToken('authToken')->plainTextToken;
            //kembalikan saat sukses register
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ]);
        } catch (Exception $error) {
            //beri pesan ketika gagal daftar (error dari codingan)
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error,

            ], 'Authentifaction Failed', 500);
        }
    }

    //API Logout
    public function logout(Request $request)
    {
        //ambil token user login
        $token = $request->user()->currentAccessToken()->delete();
        //beri pesan saat berhasil delete tpken
        return ResponseFormatter::success($token, 'Token Revoked');
    }
    //API ambil data profile user
    public function fetch(Request $request)
    {
        //beri pesan saat data user profile diambil
        return ResponseFormatter::success(
            $request->user(), 'Data user berhasil diambil');
    }
    //API updateProfile
    public function updateProfile(Request $request)
    {
        //ambil data semuanya dan masukan ke satu variabel
        $data = $request->all();
        //ambil data dari user yang login
        $user = Auth::user();
        //update data yang telah dimasukan
        $user->update($data);
        //beri pesan saat update berhasil
        return ResponseFormatter::success($user, 'Profile Updated');

    }

    //API updateFoto
    public function updatePhoto(Request $request)
    {
        //cek validasi dari gambar yang dimasukan
        $validator = Validator::make($request->all(), [
            'file' => 'required|image|max:2048'
        ]);
        //cek validasi kalau gagal
        if($validator->fails()){
            //beri pesan gagal
            return ResponseFormatter::error([
                'error' => $validator->errors(),
                'update photo fails', 401
            ]);
        }

        //cek kalo behasil
        if($request->file('file')){
            //simpan file yang dimasukan ke dalam storage
            $file = $request -> file->store('assets/user', 'public');

            //simpan foto yang ada di storage ke database (urlnya) dari si user yang login
            $user = Auth::user();
            //masukan alamtnya ke yang telah dimasukan di dalam storage
            $user->profile_photo_path = $file;
            //update gmabarnya yang telah diganti
            $user->update();
            //beri pesan saar berhasil
            return ResponseFormatter::success([$file], 'File successfully uplouded');
        }
    }
}
