# laravel-auth-app

Custom Guard and UserProvider for authentication multiple token-based

## Installation

1. Require `villabs/app-auth` on composer
2. Add the `Villabs\AppAuth\AppTokenAuthServiceProvider::class` services provider on `app.php` configuration file.
3. Create new guard config on `auth.php`:
 
```php
'guards' => [
    'app' => [
        'driver' => 'app',
        'provider' => 'app-provider',
    ],
  ...
]

'providers' => [
    'app-provider' => [
        'driver' => 'app',
        'model' => App\User::class,
    ],
    ...
 ]
 
 ```
 
 ## Usage

```php
/**
 * @param \Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function getLogin(Request $request)
{

  if (Auth::attempt($request->only(['email', 'password']))) {
      $user = Auth::user();
      $token = $user->token;

      return [
          "success" => true,
          "user" => $user,
          "token" => $token
      ];
  }
  
  return [
      "success" => false
  ];
}
```


```
