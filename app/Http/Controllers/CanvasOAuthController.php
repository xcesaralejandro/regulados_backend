<?php

namespace App\Http\Controllers;

use App\Models\User;
use xcesaralejandro\canvasoauth\DataStructures\AuthenticatedUser;
use xcesaralejandro\canvasoauth\Http\Controllers\CanvasOAuthController as CanvasOAuthControllerBase;
use Illuminate\Http\Request;

class CanvasOAuthController extends CanvasOAuthControllerBase
{

    public function onPermissionGranted(AuthenticatedUser $user, Request $request): mixed
    {
        dd($user, $request->all());
        $user = User::create([
            'name' => $user->standard->name,
            'canvas_user_id' => $user->standard->id
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;
        try {
        } catch (\Exception $e) {
        }
        return redirect()->away("reguladosapp://canvas?status=granted&token=$token");
    }

    public function onPermissionDenied(Request $request): mixed
    {
        return redirect()->away("reguladosapp://canvas?status=denied");
    }

    public function onError(\Exception $exception): mixed
    {
        return redirect()->away("reguladosapp://canvas?status=error&message=" . $exception->getMessage());
    }
}
