<?php

namespace App\Guards;

use App\Services\JWTService;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Authenticatable;

class JWTGuard implements Guard
{
    use GuardHelpers;

    /**
     * The JWT service instance
     */
    protected $jwt;

    /**
     * The request instance
     */
    protected $request;

    /**
     * Create a new authentication guard
     */
    public function __construct(JWTService $jwt, Request $request, UserProvider $provider)
    {
        $this->jwt = $jwt;
        $this->request = $request;
        $this->provider = $provider;
    }

    /**
     * Get the currently authenticated user
     */
    public function user()
    {
        if (! is_null($this->user)) {
            return $this->user;
        }

        $token = $this->request->bearerToken();

        if ($token && ($user = $this->jwt->getUserFromToken($token))) {
            return $this->user = $user;
        }

        return null;
    }

    /**
     * Validate a users credentials
     */
    public function validate(array $credentials = [])
    {
        if (empty($credentials['email']) || empty($credentials['password'])) {
            return false;
        }

        $user = $this->provider->retrieveByCredentials($credentials);

        if (! $user) {
            return false;
        }

        return $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * Set the current user
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;

        return $this;
    }
}
