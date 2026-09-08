<?php

namespace App\Http\Controllers;

use App\Models\University;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function me()
    {
        $user = User::with('program.university')
            ->findOrFail(Auth::id())
            ->makeVisible([
                'gender',
                'custom_gender',
                'preferred_start_time'
            ]);
        return response()->json($user, 200);
    }
}
