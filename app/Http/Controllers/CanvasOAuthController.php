<?php

namespace App\Http\Controllers;

use App\Models\CanvasClient;
use App\Models\User;
use xcesaralejandro\canvasoauth\DataStructures\AuthenticatedUser;
use xcesaralejandro\canvasoauth\Http\Controllers\CanvasOAuthController as CanvasOAuthControllerBase;
use Illuminate\Http\Request;
use CanvasHttp\Facades\CanvasHttp;

class CanvasOAuthController extends CanvasOAuthControllerBase
{

    public function onPermissionGranted(AuthenticatedUser $user, Request $request): mixed
    {
        $canvas_token = $user->standard->token->freshToken() ?? null;
        $client = CanvasClient::where('code', $request->state)->first();
        if (!isset($canvas_token)) {
            return redirect()->away("reguladosapp://canvas?status=error&message=User token can't be obtained. Try again.");
        }
        if (!isset($client->url)) {
            return redirect()->away("reguladosapp://canvas?status=error&message=Client not has canvas url configured.");
        }
        $canvas_http = CanvasHttp::client($client->url, $canvas_token);
        $response = $canvas_http->get('/api/v1/users/self')->send()->json();
        $user_with_email = User::where('email', $response['email'])->first();
        $token = null;
        if (isset($user_with_email)) {
            $user_with_email->update([
                'name' => $response['first_name'],
                'surname' => $response['last_name'],
                'canvas_user_id' => $user->standard->id
            ]);
            $token = $user_with_email->createToken('auth_token')->plainTextToken;
        } else {
            $new_user = User::updateOrcreate(
                ['canvas_user_id' => $user->standard->id],
                [
                    'email' => $response['email'],
                    'name' => $response['first_name'],
                    'surname' => $response['last_name'],
                ]
            );
            $token = $new_user->createToken('auth_token')->plainTextToken;
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
