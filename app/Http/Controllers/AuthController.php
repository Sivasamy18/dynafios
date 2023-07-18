<?php

namespace App\Http\Controllers;

use App\User;
use App\Group;
use App\Physician;
use App\SsoClientDomain;
use App\Http\Controllers\Validations\AuthValidation;
use GuzzleHttp\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use function App\Start\is_super_user;

class AuthController extends BaseController
{
    protected $requireAuth = true;
    protected $requireAuthOptions = [
        'only' => ['getLogout']
    ];
    const UNSUCCESSFUL_LOGIN_ATTEMPTS_LIMIT = 10;

    public function getLogin()
    {
        if (Auth::check()) {
            $id = Auth::user()->id;
            $user = User::find($id);
        if ($user->hasRole('Physician')) {
            // if (Auth::user()->group_id == Group::Physicians) {
                $physician = Physician::where('email', '=', Auth::user()->email)->first();
                if ($physician) {
                    return Redirect::route('physician.dashboard', $physician->id);
                } else {
                    return View::make('auth/login');
                }
            }
            return Redirect::route('dashboard.index');
        }

        return View::make('auth/login');
    }

    public function postLogin()
    {
        $credentials = Request::except(array('_token', 'remember'));
        $user = User::where('email', '=', $credentials['email'])->first();

        $validation = new AuthValidation();
        if (!$validation->validateLogin($credentials)) {
            return Redirect::back()
                ->withErrors($validation->messages())
                ->withInput();
        }

        // SSO login mode was selected from the login window
        $email = Request::input('email');
        if (Request::input('action') == 'single-sign-on') {
            return Redirect::route('sso.email_request_with_email', [$email]);
        }

        // Domains that are set up for Single Sign on should not be allowed to log normally
        // Emails belonging to these domains will be automatically redirected to SSO login
        $domain_name = preg_split("/@/", $email)[1];
        $domain = SsoClientDomain::where('name', $domain_name)->first();
        if ($user) {
            if (is_super_user() && !is_null($domain)) {
                return Redirect::route('sso.email_request_with_email', [$email]);
            }
        }

        // Old process
        if ($user) {
            if ($user->getLocked() == 1) {
                return Redirect::back()->with([
                    'error' => Lang::get("auth.account_locked")
                ])->withInput();
            }
        }

        // $validation = new AuthValidation();
        // if (!$validation->validateLogin($credentials)) {
        //     return Redirect::back()
        //         ->withErrors($validation->messages())
        //         ->withInput();
        // }

        if (!Auth::attempt($credentials, Request::input('remember'))) {
            if ($user) {
                $unsuccessful_login_attempts = $user->getUnsuccessfulLoginAttempts();
                $user->setUnsuccessfulLoginAttempts($unsuccessful_login_attempts + 1);
                if (($unsuccessful_login_attempts + 1) >= self::UNSUCCESSFUL_LOGIN_ATTEMPTS_LIMIT) {
                    $user->setLocked(1);
                }
                $user->save();
            }
            return Redirect::back()->with([
                'error' => Lang::get("auth.invalid_credentials")
            ])->withInput();
        }

        if (Auth::user()->group_id == Group::Physicians) {
            $physician = Physician::where('email', '=', Auth::user()->email)->first();
            if ($physician) {
                $user->setUnsuccessfulLoginAttempts(0);
                $user->save();
                return Redirect::route('physician.dashboard', $physician->id);
            } else {
                return Redirect::back()->with([
                    'error' => Lang::get("auth.invalid_physician")
                ])->withInput();
            }
        }
        $user->setUnsuccessfulLoginAttempts(0);
        $user->save();
        return Redirect::route('dashboard.index');
    }

    public function ssoPostLogin(User $user = null)
    {
        $credentials = [
            "email" => $user->email,
            "password" => $user->password_text,
        ];

        if (!Auth::attempt($credentials)) {
            return Redirect::back()->with([
                'error' => Lang::get("sso_client.local_user_error")
            ])->withInput();
        }

        $today = date("Y-m-d");
        $password_expiration_date = $user->password_expiration_date;

        if ($password_expiration_date <= $today)
            $user->password_expiration_date = date('Y-m-d', strtotime($today . '+100 years'));
        if ($user->getLocked() == 1)
            $user->setLocked(0);

        $user->setUnsuccessfulLoginAttempts(0);
        $user->save();


        if ($user->hasRole('Physician')) {
            $physician = Physician::where('email', '=', Auth::user()->email)->first();
            if ($physician)
                return Redirect::route('physician.dashboard', $physician->id);
            return Redirect::back()->with(['error' => Lang::get("auth.invalid_physician")])->withInput();
        }

        return Redirect::route('dashboard.index');
    }


    public function getLogout()
    {
        if (Auth::check()) {
            Auth::logout();
            Session::forget('existing_user_id');
            Session::forget('user_is_switched');
        }
        if (!Session::get('success')) {
            return Redirect::route('auth.login');
        } else {
            return Redirect::route('auth.login')->with(['success' => Session::get('success')]);
        }
    }


    public function ssoLogin()
    {

        // Get code out of query string
        $authCode = Request::input('code');
        $redirect_uri = URL::to('/sso/login/');

        // Send code to /oauth2/token
        $data = $this->exchangeCodeForTokens($authCode, $redirect_uri . '/');
        if (!array_key_exists('error', $data)) {

            // Put cognito_access_code in the session
            Request::session()->put(
                'cognito_access_token',
                $data['access_token']
            );

            // Get some info about them & make sure they're in the db
            $detailsData = $this->getUserDetails($data['access_token']);

            // Put the refresh_token in the database with the user info
            $user = User::where('email', '=', $detailsData['email'])->first();

            if ($user) return $this->ssoPostLogin($user);

            return Redirect::route('auth.login')->with(['error' => Lang::get("auth.invalid_account")]);

        } else {
            // invalid_grant is basically the only reasonable error code
            // https://docs.aws.amazon.com/cognito/latest/developerguide/token-endpoint.html#post-token-negative
            if ($data['error'] === "invalid_grant") {
                $attemptedUrl = $this->desiredUrlExtract(Request::input('state'));
                return route('login', ["desired_url" => urlencode($attemptedUrl)]);
            } else {
                abort(500, "Something went wrong logging you in.");
            }
        }
    }

    /**
     * Takes the authentication code provided by Cognito
     * and returns a access and refresh token from Cognito.
     *
     * @param String $authCode
     * @param String $appId Cognito App ID
     * @param String $redirectUrl Cognito App redirect_url
     * @return Array An array containing access_token and refresh_token
     */
    public function exchangeCodeForTokens($authCode, $redirectUrl)
    {
        $client = new Client([
            'base_uri' => env("SSO_DOMAIN_NAME"),
        ]);
        $redirect_uri = URL::to('/sso/login/');

        $response = $client->post('/oauth2/token', [
            'headers' => ["Content-Type" => "application/x-www-form-urlencoded"],
            'form_params' => [
                'client_id' => env("SSO_LOGIN_CLIENT_ID"),
                'client_secret' => env("SSO_LOGIN_CLIENT_SECRET"),
                'grant_type' => 'authorization_code',
                'code' => $authCode,
                'redirect_uri' => $redirect_uri . '/',
            ]
        ]);


        return json_decode($response->getBody(), true);
    }

    public function showLoginForm()
    {
        $redirect_uri = URL::to('/sso/login/');
        $url = '"' . env("SSO_DOMAIN_NAME") . 'login?'
            . 'client_id="' . env("SSO_LOGIN_CLIENT_ID")
            . '&client_secret=' . env("SSO_LOGIN_CLIENT_SECRET")
            . '".&response_type=code&scope=aws.cognito.signin.user.admin+email+openid+phone+profile&'
            . 'redirect_uri="' . $redirect_uri . '/"';
        return redirect($url);
    }

    /**
     * Takes a cognito access token and returns
     * information about the user it is assigned to.
     *
     * @param String $access_token
     * @return Array An array containing user details such as email,
     * given_name, family_name.
     */
    public function getUserDetails($access_token)
    {
        $client = new Client([
            'base_uri' => env("SSO_DOMAIN_NAME"),
        ]);

        $response = $client->get('/oauth2/userInfo', [
            'headers' => ["Authorization" => "Bearer " . $access_token],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Extracts the desired_url from the state parameter
     * following a Cognito login.
     *
     * @param String $stateParam
     * @return String $desiredUrl
     */
    public function desiredUrlExtract($stateParam)
    {
        $decoded = urldecode($stateParam);

        $explodedArray = explode("desired_url=", $decoded);

        if (count($explodedArray) !== 2) {
            return URL::to('/');
        } else {
            $desiredUrl = $explodedArray[1];

            return $desiredUrl;
        }
    }
}
