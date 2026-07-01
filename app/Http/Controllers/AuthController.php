<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\UserAccessCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function loginWithCode(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'size:6'],
        ]);
        $user = User::with('program.university')->where('email', $request->email)->first();
        if (!$user || !$user->verifyAccessCode($request->code)) {
            return response()->json(null, 401);
        }
        $user->update(['access_code' => null, 'access_code_expires_at' => null]);
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'access_token' => $token,
            'user' => $user
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(null, 204);
    }

    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'surname' => $request->surname,
            'gender' => $request->gender,
            'custom_gender' => $request->custom_gender,
            'semester' => $request->semester,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'birthdate' => $request->birthdate,
            'program_id' => $request->program_id,
            'avatar' => $request->avatar,
        ]);
        $user->dispatchAccessCodeMail();
        return response()->json(null, 201);
    }

    public function resendCode(Request $request)
    {
        $fields = $request->validate(['email' => ['required', 'email']]);
        $user = User::where('email', $fields['email'])->first();
        if (!$user) {
            return response()->json(null, 200);
        }
        $user->dispatchAccessCodeMail();
        return response()->json(null, 200);
    }
}
