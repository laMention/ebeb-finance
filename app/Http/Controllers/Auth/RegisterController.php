<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            // INFORMATIONS PERSONNELLES
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'prenom' => ['required', 'string', 'max:255'],
            'date_naissance' => ['required', 'date'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'lieu_naissance' => ['nullable', 'string','max:255'],
            'numero_cnps' => ['nullable', 'string','max:255'],
            'numero_cmu' => ['nullable', 'string','max:255'],
            'profession' => ['nullable', 'string','max:255'],
            'telephone' => ['required', 'string','min:8','max:10'],
            'statut' => ['required', 'string','min:8','max:10'],
            'type_carte' => ['nullable', 'string','min:8','max:10'],
            'pays' => ['required', 'string','min:8','max:10'],
            'ville' => ['required', 'string'],
            'situation_familiale' => ['required', 'string',], //
            'quartier' => ['required', 'string'],
            'village' => ['nullable', 'string'],
            'adresse_postale' => ['nullable', 'string'],
            'sexe' => ['nullable', 'string'],
            'nombre_enfants' => ['required', 'numeric'],
    
            // DECLARATION REVENU
            'montant_revenu' => ['required', 'numeric'],

            // INFORMATIONS PROFESSIONNELLES
            'categorie_professionnelle' => ['required', 'string'],
            'metier' => ['required', 'string'],
            'date_debut_activite' => ['required', 'date'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @return User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }
}
