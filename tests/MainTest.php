<?php

use Illuminate\Http\Request;

class MainTest extends Orchestra\Testbench\TestCase
{

    function setUp()
    {
        parent::setUp();

        $this->loadLaravelMigrations();
        $this->artisan('migrate', ['--database' => 'testbench']);
        
    }

    function testAttempt()
    {
        DB::table("users")->insert([
            "email" => "test",
            "name" => "test",
            "password" => Hash::make('123456')
        ]);
        $attempt = Auth::guard("app")->attempt(["email" => "test", "password" =>"123456"]);
        $this->assertTrue($attempt);
    }

    function testCheck()
    {

        DB::table("users")->insert([
            "id" => 2,
            "email" => "test",
            "name" => "test",
            "password" => Hash::make('123456')
        ]);

        $token = Hash::make(rand(0,100) . time());

        DB::table("tokens")->insert([
            "id" => $token,
            "user_id" => 2
        ]);
        
        $request = new Request;
        $request->headers->set('Authorization', 'Bearer '. $token);
        Auth::guard("app")->setRequest($request);
        
        $check = Auth::guard("app")->check();
        $this->assertTrue($check);

        $request = new Request;
        $request->headers->set('Authorization', 'Bearer INVALID_TOKEN');
        Auth::guard("app")->setRequest($request);
        
        $check = Auth::guard("app")->check();
        $this->assertFalse($check);
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        
        $app['config']->set('auth.guards.app', [
            'driver' => 'app',
            'provider' => 'app',
        ]);
        $app['config']->set('auth.providers.app', [
            'driver' =>  'app',
            'model' => \Illuminate\Foundation\Auth\User::class
        ]);
    }

    /**
     * Resolve application HTTP Kernel implementation.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function resolveApplicationHttpKernel($app)
    {
        $app->singleton('Illuminate\Contracts\Http\Kernel', 'Acme\Testbench\Http\Kernel');
    }

    protected function getPackageProviders($app)
    {
        return ['Villabs\AppAuth\AppTokenAuthServiceProvider'];
    }
}