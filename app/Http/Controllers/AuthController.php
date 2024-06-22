<?php

namespace App\Http\Controllers;


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private function revokeExistingTokens($user)
    {
        $existing_tokens = $user->tokens();

        foreach ($existing_tokens as $token) {
            $token->revoke();
        }
    }
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


        $this->revokeExistingTokens($user);

        $token = JWTAuth::fromUser($user);


        return response()->json(['account' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,

        ], 'accessToken' => $token]);
    }

    public function getAuthenticated()
    {
        if(auth()->user()) {
            return response()->json(auth()->user());
        } else {
            return response()->json('Не сте авторизирани', 400);
        }
    }
}
