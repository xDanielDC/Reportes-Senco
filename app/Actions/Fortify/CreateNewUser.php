<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;
use Illuminate\Support\Facades\Log;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users', 'alpha_dash'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        // Verificar que el username no exista en AD (opcional pero recomendado)
        try {
            $ldapUser = \App\Ldap\User::where('samaccountname', '=', $input['username'])->first();
            
            if ($ldapUser) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'username' => ['Este usuario ya existe en Active Directory. Use el inicio de sesión normal.'],
                ]);
            }
        } catch (\Exception $e) {
            // Si falla la conexión LDAP, continuar con registro local
            Log::warning('Could not verify username against AD: ' . $e->getMessage());
        }

        return User::create([
            'name' => $input['name'],
            'username' => $input['username'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'is_ldap_user' => false, // ← Usuario local
        ]);
    }
}