<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TwoFactorController extends Controller
{
    public function status(Request $request, TwoFactorService $twoFactor): JsonResponse
    {
        return response()->json($twoFactor->status($request->user()))->header('Cache-Control', 'no-store');
    }

    public function setup(Request $request, TwoFactorService $twoFactor): JsonResponse
    {
        $request->validate(['current_password' => 'required|string|max:1024', 'method' => 'sometimes|in:authenticator,email', 'replace' => 'sometimes|boolean', 'code' => 'nullable|string|max:64']);
        $twoFactor->reauthenticate($request);

        if ($request->boolean('replace')) {
            $twoFactor->authorizeSensitiveChange($request->user(), (string)$request->input('code', ''), $request, 'switch');
        }
        return response()->json($twoFactor->setup($request->user(), $request, $request->input('method', 'authenticator'), $request->boolean('replace')))->header('Cache-Control', 'no-store');
    }

    public function email(Request $request, TwoFactorService $twoFactor): JsonResponse
    {
        $data = $request->validate(['action' => 'required|in:confirm,disable,recovery,mail_settings,user_security,switch', 'current_password' => 'required|string|max:1024']);
        $twoFactor->reauthenticate($request);
        $twoFactor->sendSettingsEmail($request->user(), $data['action'], $request);

        return response()->json(['message' => 'Verification email sent. The code expires in five minutes.'])->header('Cache-Control', 'no-store');
    }

    public function update(Request $request, TwoFactorService $twoFactor): JsonResponse
    {
        $data = $request->validate(['action' => 'required|in:confirm,disable,recovery',
            'current_password' => 'required|string|max:1024', 'code' => 'required|string|max:64']);
        $twoFactor->reauthenticate($request);

        return response()->json($twoFactor->change($request->user(), $data['action'], trim($data['code']), $request))
            ->header('Cache-Control', 'no-store');
    }
}
