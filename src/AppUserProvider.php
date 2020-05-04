<?php
namespace Villabs\AppAuth;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

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
        $user_id = DB::table("tokens")->where("id", $token)->value("user_id");
        return $this->model::where("id", $user_id)->first();
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
        unset($credentials["device"]);
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
        return (Hash::check($credentials["password"], $user->getAuthPassword())) ||
            sha1($credentials["password"]) == $user->getAuthPassword();
    }

    /**
     * Create new token for user
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string|null $device
     * @return array|null
     */
    public function createToken(Authenticatable $user, $device){

        $device = @$device? $device: "default";
        $token = DB::table("tokens")
            ->where("name", $device)
            ->where("user_id", $user->id)
            ->first();

        if (!$token){
            $token = [
                "id" => sha1(rand(0,1000).time()),
                "user_id" => $user->id,
                "name" => $device,
                "ip_address" => Request::ip(),
                "payload" =>  Request::header("user-agent"),
                "last_activity" => Carbon::now()
            ];
            DB::table("tokens")->insert($token);
            $token = (object) $token;
        }
        
        return $token;
    }
    /**
     * Create new token for user
     *
     * @param  string $token
     * @return void
     */
    public function removeToken(String $token){

        DB::table("tokens")
            ->where("id", $token)
            ->delete();
        
    }
}