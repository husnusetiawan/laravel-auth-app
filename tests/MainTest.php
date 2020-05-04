<?php

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\User;
class MainTest extends Orchestra\Testbench\TestCase
{

    function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations();
        $this->artisan('migrate', ['--database' => 'testbench']);
        
    }

    function testSetUser()
    {
        $user = new User;
        $user->forceFill([
            "email" => "test",
            "name" => "test",
            "password" => Hash::make('123456')
        ]);
        $user->save();

        Auth::guard("app")->setUser($user);
        $lastUser = Auth::guard("app")->user();
        $this->assertEquals($lastUser->token->id, $user->token->id );

        // test seconds user

        $user2 = new User;
        $user2->forceFill([
            "email" => "test2",
            "name" => "test",
            "password" => Hash::make('123456')
        ]);
        $user2->save();

        Auth::guard("app")->setUser($user2);
        $lastUser = Auth::guard("app")->user();
        $this->assertEquals($lastUser->token->id, $user2->token->id );

    }


    /**
     * @depends testSetUser
     */
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

    function testAttemptSha1()
    {
        DB::table("users")->insert([
            "email" => "test",
            "name" => "test",
            "password" => sha1("123456")
        ]);
        $attempt = Auth::guard("app")->attempt(["email" => "test", "password" =>"123456"]);
        $this->assertTrue($attempt);
    }

    /**
     * @depends testAttempt
     */
    function testAttemptSameDevice()
    {
        DB::table("users")->insert([
            "email" => "test",
            "name" => "test",
            "password" => Hash::make('123456')
        ]);
        $attempt = Auth::guard("app")->attempt(["email" => "test", "password" =>"123456"]);
        $this->assertTrue($attempt);
        $lastUser = Auth::guard("app")->user();

        // login again
        Auth::guard("app")->attempt(["email" => "test", "password" =>"123456"]);
        $user = Auth::guard("app")->user();
        
        $this->assertEquals($lastUser->token->id, $user->token->id );
    }

    function testCheck()
    {

        DB::table("users")->insert([
            "id" => 2,
            "email" => "test",
            "name" => "test",
            "password" => Hash::make('123456')
        ]);

        $token = sha1(rand(0,100) . time());

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

    function testLogout(){
      
        DB::table("users")->insert([
            "email" => "test",
            "name" => "test",
            "password" => Hash::make('123456')
        ]);
        $attempt = Auth::guard("app")->attempt(["email" => "test", "password" =>"123456"]);
        $this->assertTrue($attempt);
        
        $token = Auth::guard("app")->getToken();
        $request = new Request;
        $request->headers->set('Authorization', 'Bearer '. $token);
        Auth::guard("app")->setRequest($request);

        $check = Auth::guard("app")->check();
        $this->assertTrue($check);

        Auth::guard("app")->logout();

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
            'model' => User::class
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
