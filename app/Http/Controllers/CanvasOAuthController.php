<?php

namespace App\Http\Controllers;

use xcesaralejandro\canvasoauth\DataStructures\AuthenticatedUser;
use xcesaralejandro\canvasoauth\Http\Controllers\CanvasOAuthController as CanvasOAuthControllerBase;
use Illuminate\Http\Request;

class CanvasOAuthController extends CanvasOAuthControllerBase
{

    public function onPermissionGranted(AuthenticatedUser $user, Request $request): mixed
    {
        return redirect()->away("reguladosapp://login?status=success&token=workss.xd");
    }

    public function onPermissionDenied(Request $request): mixed
    {
        return redirect()->away("reguladosapp://canvas?status=rejected");
    }

    public function onError(\Exception $exception): mixed
    {
        return redirect()->away("reguladosapp://canvas?status=error");
    }
}
