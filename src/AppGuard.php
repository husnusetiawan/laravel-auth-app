<?php
namespace Villabs\AppAuth;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;

class AppGuard implements Guard {

    private $provider;
    private $request;
    private $user;

    function __construct(UserProvider $provider, Request $request) {
        $this->provider = $provider;
        $this->request = $request;
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check(){
        return $this->user() != null;
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest(){
        return $this->user() == null;
    }

    /**
     * Set the current request instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user(){
        if ($this->user)
            return $this->user;

        $token = $this->getToken(); 
        return $this->provider->retrieveByToken(1,$token);
    }

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|string|null
     */
    public function id(){
        $user = $this->user();
        return $user == null? null : $user->id;
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = []){
        $user = $this->provider->retrieveByCredentials($credentials);
        if (!$user)
            return false;
        $validated = $this->provider->validateCredentials($user, $credentials);
        if ($validated)
            $this->setUser($user);
        
        return $validated;
    }

    public function attempt(array $credentials = []){

        if ($this->validate($credentials))
        {
            $token = $this->provider->createToken($this->user, $credentials);
            $this->user->token = $token;
            return true;
        }

        return false;
    }

    /**
     * Set the current user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    public function setUser(Authenticatable $user){
        $this->user = $user;
    }

    public function getToken(){
        $header = $this->request->header("Authorization","");
        if (Str::startsWith($header, 'Bearer '))
            return Str::substr($header, 7);
        
        return null;
    }
}
