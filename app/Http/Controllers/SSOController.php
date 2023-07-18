<?php

namespace App\Http\Controllers;

use App\SsoClientDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Throwable;

class SSOController extends ResourceController
{
    public function getIndex($email = null)
    {

        if (Auth::check()) {
            return Redirect::route('dashboard.index');
        }

        if ($email) {
            $request = new Request();
            $request->merge(['email' => $email]);
            return $this->postIndex($request, true);
        }

        return View::make('sso.email_request');
    }

    public function postIndex(Request $request, $automated_request = false)
    {
        $request->validate(['email' => 'email']);
        $email = $request->input('email');
        $domain_name = preg_split("/@/", $email)[1];

        // default errors to display
        $error_to_display = [
            "login_error" => Lang::get('sso_client.login_error'),
            "login_domain_error" => Lang::get('sso_client.login_domain_error')
        ];
        // Dont show error if it was an automated request
        // Automated requests are generated using last email to make user take less steps in the login process but maybe not with the desired user input
        if ($automated_request) {
            $error_to_display = ["login_error" => null, "login_domain_error" => null];
        }

        try {
            $domain = SsoClientDomain::where('name', $domain_name)->first();

            if (is_null($domain) && $automated_request)
                return Redirect::route('sso.email_request');
            else if (is_null($domain))
                return Redirect::route('sso.email_request')->with(['error' => $error_to_display['login_domain_error']]);

            $identity_provider = $domain->client['identity_provider'];
        } catch (Throwable $e) {
            return Redirect::route('sso.email_request')->with(['error' => $error_to_display['login_error']]);
        }

        $redirect_uri = URL::to('/sso/login/');

        $url = env("SSO_DOMAIN_NAME") . "oauth2/authorize?"
            . "identity_provider=" . $identity_provider . "&"
            . "redirect_uri=" . $redirect_uri . "/&"
            . "response_type=CODE" . "&"
            . "client_id=" . env("SSO_LOGIN_CLIENT_ID") . "&"
            . "client_secret=" . env("SSO_LOGIN_CLIENT_SECRET") . "&"
            . "scope=email openid";

        return Redirect::away($url);
    }
}
