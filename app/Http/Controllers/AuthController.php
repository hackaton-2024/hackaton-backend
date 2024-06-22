<?php

namespace App\Http\Controllers;


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required',
                'password' => 'required'
            ]);
        } catch (ValidationException $e) {
            // Handle validation errors
            return response()->json(['error' => 'Моля, попълни празните полета.']);
        }


        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return response()->json(['error' => 'Потребител с този имейл не съществува.']);
        }

        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json(['error' => 'Грешна парола.']);
        }



        $token = JWTAuth::fromUser($user);


        return response()->json(['account' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,

        ], 'accessToken' => $token]);
    }
}
