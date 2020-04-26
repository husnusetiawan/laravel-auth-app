<?php
namespace Villabs\AppAuth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class AppTokenAuthServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../migrations');

        Auth::extend('app', function ($app, $name, array $config) {
            // Return an instance of Illuminate\Contracts\Auth\Guard...

            return new AppGuard(Auth::createUserProvider($config['provider']), 
                $app->make("request"));
        });

        Auth::provider('app', function ($app, array $config) {
            // Return an instance of Illuminate\Contracts\Auth\UserProvider...
            if (!@$config["model"]){
                throw new \Exception("set model in provider auth config");
            }

            $interfaces = class_implements($config["model"]);
            if ( !($interfaces && in_array(Authenticatable::class, $interfaces) ) ) {
                throw new \Exception("model must implement Illuminate\Contracts\Auth\Authenticatable");
            }

            return new AppUserProvider($config["model"]);
        });
    }
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

    }
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
