<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use LdapRecord\Container;
use Illuminate\Support\Facades\Log;

class AuthenticateUserHybrid
{
    /**
     * Autenticar usuario (Híbrido: Local + LDAP)
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->only('username', 'password');
        $username = $credentials['username'];
        $password = $credentials['password'];

        // 1. Buscar usuario en base de datos local (MySQL)
        $user = User::on('mysql')  // ← AGREGAR ESTO
                    ->where('username', $username)
                    ->orWhere('email', $username)
                    ->first();

        // 2. Si existe y NO es usuario LDAP, autenticar localmente
        if ($user && !$user->is_ldap_user) {
            if (Hash::check($password, $user->password)) {
                Auth::login($user, $request->boolean('remember'));
                return $user;
            }
            
            throw ValidationException::withMessages([
                'username' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // 3. Si es usuario LDAP o no existe, intentar autenticación LDAP
        try {
            $ldapConnection = Container::getDefaultConnection();
            
            // Buscar usuario en Active Directory
            $ldapUser = \App\Ldap\User::where('samaccountname', '=', $username)->first();
            
            if (!$ldapUser) {
                throw ValidationException::withMessages([
                    'username' => ['Usuario no encontrado en Active Directory.'],
                ]);
            }

            // Intentar autenticar contra AD
            if (!$ldapConnection->auth()->attempt($ldapUser->getDn(), $password)) {
                throw ValidationException::withMessages([
                    'username' => ['Contraseña incorrecta.'],
                ]);
            }

            // 4. Autenticación exitosa - Sincronizar/Crear usuario local
            $user = $this->syncOrCreateUserFromLdap($ldapUser);
            
            Auth::login($user, $request->boolean('remember'));
            
            return $user;

        } catch (\LdapRecord\LdapRecordException $e) {
            Log::error('LDAP Authentication Error: ' . $e->getMessage());
            
            throw ValidationException::withMessages([
                'username' => ['Error al conectar con Active Directory. Intente más tarde.'],
            ]);
        }
    }

    /**
     * Sincronizar o crear usuario desde LDAP
     */
    protected function syncOrCreateUserFromLdap($ldapUser): User
    {
        $guid = $ldapUser->getConvertedGuid();
        
        // Buscar por GUID primero (en MySQL)
        $user = User::on('mysql')  // ← AGREGAR ESTO
                    ->where('guid', $guid)
                    ->first();
        
        if (!$user) {
            // Buscar por username (en MySQL)
            $user = User::on('mysql')  // ← AGREGAR ESTO
                        ->where('username', $ldapUser->getFirstAttribute('samaccountname'))
                        ->first();
        }

        $userData = [
            'name' => $ldapUser->getFirstAttribute('cn'),
            'username' => $ldapUser->getFirstAttribute('samaccountname'),
            'email' => $ldapUser->getFirstAttribute('mail') ?? $ldapUser->getFirstAttribute('samaccountname') . '@' . env('LDAP_DOMAIN', 'domain.com'),
            'guid' => $guid,
            'domain' => env('LDAP_DOMAIN', 'AD'),
            'is_ldap_user' => true,
        ];

        if ($user) {
            // Actualizar usuario existente
            $user->update($userData);
        } else {
            // Crear nuevo usuario
            $userData['password'] = Hash::make(uniqid()); // Password aleatorio (no se usa)
            $user = User::create($userData);
        }

        return $user;
    }
}