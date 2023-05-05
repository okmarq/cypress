<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * @throws AuthenticationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'string|required|min:8',
        ]);

        if (
            Auth::attempt([
                'email' => $fields['email'],
                'password' => $fields['password'],
            ])
        ) {
            $user = User::find(auth()->id());
            $token = $user->createToken('Cypress')->plainTextToken;

            $response = [
                'user' => $user,
                'token' => $token
            ];

            response($response, 200);

            return redirect()->route('admin.home');
        }

        throw new AuthenticationException(
            'Your credentials does not match our record.'
        );
    }

    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete();

        Auth::logout();
        Session::flush();

        $response = [
            'message' => 'Logged out'
        ];

        response($response, 200);

        return redirect()->route('admin.home');
    }
}
