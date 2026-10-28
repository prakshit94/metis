<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers;

use App\Mail\SecurityMailTest;
use App\Modules\Core\Controllers\Controller;
use App\Services\SecurityMailSettings;
use App\Services\TwoFactorEmail;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class SecurityMailController extends Controller
{
    private function authorizeAdmin(Request $request, string $permission): void
    {
        abort_unless($request->user()?->isMasterAdmin(), 403, 'Only the master administrator can manage shared mail delivery.');
        abort_unless($request->user()?->can($permission), 403);
        abort_if($request->hasSession() && $request->session()->has('impersonated_by'), 403, 'Stop impersonating before managing mail settings.');
    }

    public function show(Request $request, SecurityMailSettings $settings): JsonResponse
    {
        $this->authorizeAdmin($request, 'security-mail-view');

        return response()->json($settings->publicSettings())->header('Cache-Control', 'no-store');
    }

    public function update(Request $request, SecurityMailSettings $settings, TwoFactorService $twoFactor): JsonResponse
    {
        $this->authorizeAdmin($request, 'security-mail-edit');
        $data = $request->validate([
            'enabled' => 'required|boolean', 'provider' => 'required|in:smtp,postmark,resend,ses,existing',
            'host' => 'nullable|string|max:253', 'port' => 'required|integer|between:1,65535',
            'encryption' => 'required|in:starttls,smtps', 'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:2048', 'api_key' => 'nullable|string|max:2048',
            'aws_key' => 'nullable|string|max:2048', 'aws_secret' => 'nullable|string|max:2048',
            'region' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9-]+$/D'],
            'message_stream' => 'nullable|string|max:128', 'existing_mailer' => 'nullable|string|max:128',
            'from_address' => 'nullable|email:rfc|max:255', 'from_name' => ['nullable', 'string', 'max:255', 'not_regex:/[\r\n]/'],
            'reply_to' => 'nullable|email:rfc|max:255', 'timeout' => 'required|integer|between:1,60',
            'clear_credentials' => 'sometimes|boolean', 'current_password' => 'required|string|max:1024', 'code' => 'nullable|string|max:64',
        ]);
        abort_if(($data['clear_credentials'] ?? false) && $data['enabled'], 422, 'Disable the saved configuration before clearing credentials.');
        $twoFactor->reauthenticate($request);
        $verificationError = DB::transaction(function () use ($request, $data, $settings, $twoFactor) {
            try {
                $twoFactor->authorizeSensitiveChange($request->user(), (string) ($data['code'] ?? ''), $request, 'mail_settings');
            } catch (ValidationException $error) {
                return $error; // Commit failed-code attempts and audit before rendering validation errors.
            }
            $settings->save($data, $request->user()->id);
            $twoFactor->audit($request->user()->id, 'mail_settings_updated', $request);

            return null;
        });
        if ($verificationError) {
            throw $verificationError;
        }

        return response()->json(['message' => $data['enabled'] ? 'Mail configuration saved. Shared OTP delivery is enabled.' : 'Mail configuration saved, but this configuration is disabled. OTP delivery will use the application mailer.'] + $settings->publicSettings())->header('Cache-Control', 'no-store');
    }

    public function test(Request $request, SecurityMailSettings $settings, TwoFactorService $twoFactor, TwoFactorEmail $email): JsonResponse
    {
        $this->authorizeAdmin($request, 'security-mail-test');
        $request->validate(['current_password' => 'required|string|max:1024', 'code' => 'nullable|string|max:64']);
        $twoFactor->reauthenticate($request);
        $twoFactor->authorizeSensitiveChange($request->user(), (string) $request->input('code', ''), $request, 'mail_settings');
        $key = 'security-mail-test:'.$request->user()->id;
        abort_if(RateLimiter::tooManyAttempts($key, 3), 429, 'Wait five minutes before sending another test email.');
        RateLimiter::hit($key, 300);
        abort_unless($email->available(), 422, 'Save an enabled delivery configuration first.');
        try {
            $message = new SecurityMailTest;
            $settings->applySender($message);
            Mail::mailer($settings->resolveMailer())->to($request->user()->email)->send($message);
        } catch (\Throwable) {
            $twoFactor->audit($request->user()->id, 'mail_settings_test_failed', $request);

            return response()->json(['message' => 'Test delivery failed. Check the host, port, TLS, credentials and sender verification.'], 422);
        }
        $twoFactor->audit($request->user()->id, 'mail_settings_test_sent', $request);

        return response()->json(['message' => 'Test email submitted to your account address. Check your inbox and spam folder.'])->header('Cache-Control', 'no-store');
    }
}
