<?php

namespace App\Http\Controllers\Apiv1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\ConnexionRequest;
use App\Http\Requests\DefinirCodePinRequest;
use App\Http\Requests\RenvoyerCodeOtpRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\VerificationOtpRequest;
use App\Models\User;
use App\Services\AlerteGenerator;
use App\Services\InscriptionService;
use App\Services\OtpService;
// use Illuminate\Http\Request;

class AuthController extends BaseController
{
    protected $inscriptionService;
    // private OtpService $otpService;

    public function __construct(InscriptionService $inscriptionService, private OtpService $otpService)
    {
        $this->inscriptionService = $inscriptionService;
    }
    //inscription
    public function inscription(StoreUserRequest $request)
    {
        try {
            $validated = $request->validated();

            // Ce tableau servira à nettoyer le disque en cas d'erreur SQL
            $fichiersUploadees = [];

            // return($validated);     
            $fichiersKyc = [
                'recto' => $request->hasFile('url_recto') ? $request->file('url_recto')->store('identifications', 'public') : null,
                'verso' => $request->hasFile('url_verso') ? $request->file('url_verso')->store('identifications', 'public') : null,
                'selfie' => $request->hasFile('url_selfie') ? $request->file('url_selfie')->store('identifications', 'public') : null,
            ];
            
            // On filtre pour ne garder que les chemins des fichiers réellement enregistrés
            $fichiersUploadees = array_filter($fichiersKyc);

            $user = $this->inscriptionService->inscrire($validated, $fichiersKyc);

            if($user) {
                // Envoyer un code OTP pour verifier si le numéro de télephone est valide
                $otp = $this->otpService->generateAndSend($user);

                $data = [
                    'user' => $user,
                    'otp' => $otp
                ];

                AlerteGenerator::utilisateur('SUCCES',
                    'Nouvel utilisateur inscrit',
                    "Un nouveau compte a été créé : {$user->prenom} {$user->nom} ({$user->telephone}).",
                    "/users/{$user->id}"
                );
            }

            return $this->sendResponse($data,'Inscription réussie. Utilisez le code OTP qui vous a été envoyé pour vérifier si votre numéro de téléphone est valide');
            

        } catch (\Exception $e) {
            //throw $th;
            foreach ($fichiersUploadees as $chemin) {
                \Storage::disk('public')->delete($chemin);
            }
            return $this->throw($e);
        }

    }

    // Verification Otp
    public function verificationOtp(VerificationOtpRequest $request)
    {
        try {
            $validated = $request->validated();

            $data = $this->otpService->verify($validated['telephone'], $validated['code_otp']);

            if (!$data['success']) {
                return $this->sendError($data['message'],$data,400);
            }            

            return $this->sendResponse($data,'Vérification effectuée. Vous pouvez désormais definir un code PIN pour vous connecter et protéger votre compte');


        } catch (\Exception $e) {
            //throw $th;
            return $this->throw($e);

        }

    }

    public function renvoyerCodeOtp(RenvoyerCodeOtpRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('telephone', $validated['telephone'])->first();

        $data = $this->otpService->generateAndSend($user);

        if (!$data['success']) {
            return $this->sendError($data['message'],$data,400);
        }  

        return $this->sendResponse($data,'Un nouveau code OTP a été envoyé');

    }

    // Définition du code PIN
    public function definirCodePIN(DefinirCodePinRequest $request)
    {
        try {
            $validated = $request->validated();

            $this->inscriptionService->definirCodePin($validated);

            // Connecter l'utilisateur ici
            $data = $this->inscriptionService->connecterUtilisateur($validated);

            // if (!$data['success']) {
            //     return $this->sendError($data['message'],$data,400);
            // }

            return $this->sendResponse($data,'Code PIN configuré avec succès, vous êtes maintenant connectés');

        } catch (\Exception $e) {
            //throw $th;
            return $this->throw($e);

        }
    }

    // Connexion utilisateur
    public function connexion(ConnexionRequest $request)
    {
        try {
            $validated = $request->validated();
            // Verifier si le telephone est bon et envoyer un OTP
            $user = User::where('telephone', $validated['telephone'])->first();
            if(!$user){
                return $this->sendError('Ce numéro de téléphone n\'est pas valide ou n\'existe pas',[],400);
            }
            // envoyer OTP
            $otp = $this->otpService->generateAndSend($user);
            
            $data = [
                'user' => $user,
                'otp' => $otp
            ];

            return $this->sendResponse($data,'Utilisez le code OTP qui vous a été envoyé pour vous connecter'); 

        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    // Confimer connexion en renseignant son OTP
    public function confirmerConnexion(VerificationOtpRequest $request)
    {
        try {
            $validated = $request->validated();
            // Verifier si le telephone est bon et envoyer un OTP
            $result = $this->otpService->verify($validated['telephone'], $validated['code_otp']);

            if (!$result['success']) {
                return $this->sendError($result['message'],$result,400);
            }      
            
            $data = $this->inscriptionService->connecterUtilisateur($validated); 


            return $this->sendResponse($data,'Vérification effectuée. Vous êtes maintenant connectés');

        } catch (\Exception $e) {
            //throw $th;
            return $this->throw($e);

        }
    }
    
}
