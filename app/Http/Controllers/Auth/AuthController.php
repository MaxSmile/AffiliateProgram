<?php

namespace AffiliateProgram\Http\Controllers\Auth;

use AffiliateProgram\Models\User;
use AffiliateProgram\Models\Referral;
use AffiliateProgram\Http\Requests;
use Auth;
use Illuminate\Http\Request;
use Route;
use Validator;
use AffiliateProgram\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesAndRegistersUsers, ThrottlesLogins {
        AuthenticatesAndRegistersUsers::postRegister as register;
    }

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/profile';

    /**
     * AuthController constructor. Create a new authentication controller instance.
     */
    public function __construct()
    {
        $this->middleware($this->guestMiddleware(), ['except' => 'logout']);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            $this->throwValidationException($request, $validator);
        }
        // new User or new Referrer-User
        $user = $this->create($request->all());
        // if User registered via referral link - create relation in `referrals` table
        if ($referrerId = $request->get('referrer_id')) {
            $referrerId = \Crypt::decrypt($referrerId);
            // check if Referrer still exists
            if (User::find($referrerId)) {
                Referral::create(['referrer_id' => $referrerId, 'referral_id' => $user->id]);
            } 
        }

        Auth::guard($this->getGuard())->login($user);

        return redirect($this->redirectPath());
    }

}
