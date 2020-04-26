<?php
namespace Villabs\AppAuth;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Hash;

class AppUserProvider implements UserProvider{

    private $model;
    private $columnName;

    function __construct(String $model) {
        $this->model = $model;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier){
        return $this->model::where("id", $identifier)->first();
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token){
        return $this->model::leftJoin("tokens","users.id","=","user_id")
            ->whereNotNull("tokens.id")
            ->where("tokens.id", $token)->first();
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token){
        // not supported
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials){
        unset($credentials["password"]);
        return $this->model::where($credentials)
            ->first();
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials){
        return (Hash::check($credentials["password"], $user->getAuthPassword()));
    }

    /**
     * Create new token for user
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return array|null
     */
    public function createToken(Authenticatable $user, array $credentials){
        $token = [
            "id" => Hash::make(rand(0,1000).time()),
            "user_id" => $user->id,
            "name" => @$credentials["device"]? $credentials["device"]: "default",
            "ip_address" => Request::ip(),
            "payload" =>  Request::header("user-agent"),
            "last_activity" => time()
        ];
        
        if (DB::table("tokens")->insert($token))
            return $token;
        
        return null;
    }
}