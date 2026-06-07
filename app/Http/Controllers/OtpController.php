<?php

namespace App\Http\Controllers;

use App\Services\OtpService;
use Illuminate\Http\Request;
// use Illuminate\Validation\ValidationException;

class OtpController extends Controller
{
    public function __construct(
        private OtpService $otpService
    ) {
    }

    /**
     * Envoie un code OTP à un email
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail doit être valide.',
        ]);

        $result = $this->otpService->generateAndSend($validated['email']);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result, 200);
    }

    /**
     * Vérifie un code OTP
     */
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ], [
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail doit être valide.',
            'code.required' => 'Le code OTP est obligatoire.',
            'code.size' => 'Le code OTP doit contenir 6 caractères.',
        ]);

        $result = $this->otpService->verify($validated['email'], $validated['code']);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result, 200);
    }

    /**
     * Valide et supprime un OTP après utilisation
     */
    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail doit être valide.',
        ]);

        if (!$this->otpService->isVerified($validated['email'])) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez d\'abord vérifier votre code OTP.',
            ], 400);
        }

        $this->otpService->validateAndDelete($validated['email']);

        return response()->json([
            'success' => true,
            'message' => 'Email vérifié avec succès.',
        ], 200);
    }

    /**
     * Resend OTP
     */
    public function resend(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail doit être valide.',
        ]);

        $result = $this->otpService->generateAndSend($validated['email']);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Un nouveau code OTP a été envoyé à votre adresse e-mail.',
        ], 200);
    }
}
