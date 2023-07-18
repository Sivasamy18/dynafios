<?php

namespace App\Http\Controllers;

use App\SsoClient;
use App\SsoClientDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use function App\Start\is_super_user;

class SSOClientController extends ResourceController
{
    public function getIndex()
    {
        $request = new Request();
        if (!is_super_user())
            App::abort(403);


        $options = [
            'filter' => $request->input('filter', 1),
            'sort' => $request->input('sort', 1),
            'order' => $request->input('order'),
            'sort_min' => 1,
            'sort_max' => 1,
            'appends' => ['sort', 'order', 'filter'],
            'field_names' => ['id', 'label', 'identity_provider', 'domain'],
            'per_page' => 9999
        ];

        $data = $this->query('SsoClient', $options, function ($query, $options) {
            return $query;
        });

        $data['table'] = View::make('sso_clients/partials/table')->with($data)->render();

        if ($request->ajax()) {
            return Response::json($data);
        }

        return View::make('sso_clients/index')->with($data);
    }

    public function getCreate()
    {
        return View::make('sso_clients.create');
    }

    public function getShow($id)
    {
        // return Redirect::to('/sso_clients/'.$id.'/edit');
        return Redirect::route('sso_clients.edit', [$id]);
    }


    public function getEdit($id)
    {
        if (!is_super_user()) App::abort(403);

        $data = SsoClient::findOrFail($id);
        $sso_client = $data->toArray();
        $sso_client['client_domains'] = $data->domain->toArray();

        return View::make('sso_clients.edit')->with($sso_client);
    }

    public function postEdit($id, Request $request)
    {
        if (!is_super_user()) App::abort(403);

        $validations = [
            'client_name' => 'required|unique:sso_clients,client_name,' . $id,
            'label' => 'required',
            'identity_provider' => 'required',
            'domains' => 'array'
        ];

        if (is_array($request->domains)) {
            foreach ($request->domains as $domain_id => $domain) {
                $validations = array_merge($validations, ['domains.' . $domain_id => 'regex:/^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/']);
            }
        }
        $request->validate($validations);

        $sso_client = SsoClient::findOrFail($id);
        $sso_client->client_name = $request->input('client_name');
        $sso_client->label = $request->input('label');
        $sso_client->identity_provider = $request->input('identity_provider');

        if (!$sso_client->save()) {
            return Redirect::back()
                ->with(['error' => Lang::get('sso_clients.edit_error')])
                ->withInput();
        }

        SsoClientDomain::where('sso_client_id', $sso_client->id)->delete();
        $domain_list = $request->input('domains');

        if (is_array($domain_list)) {
            // delete IDs that are not in the list ( not updated nor created ).
            SsoClientDomain::whereNotIn('id', array_keys($domain_list));

            // Update the IDs that are in the list
            foreach ($domain_list as $id => $domain_name) {
                if ($domain_name != '') {
                    $client_domain = new SsoClientDomain();
                    $client_domain->sso_client_id = $sso_client->id;
                    $client_domain->name = $domain_name;
                    $client_domain->save();
                }
            }
        }

        return Redirect::route('sso_clients.index')->with([
            'success' => Lang::get('sso_client.edit_success')
        ]);


    }

    public function getDelete($id)
    {
        if (!is_super_user()) App::abort(403);

        SsoClient::where('id', $id)->delete();
        return Redirect::route('sso_clients.index')->with([
            'success' => Lang::get('sso_client.delete_success')
        ]);
    }

    public function postCreate(Request $request)
    {
        if (!is_super_user()) App::abort(403);

        $validated = $request->validate([
            'client_name' => 'required|unique:sso_clients,client_name',
            'label' => 'required',
            'identity_provider' => 'required',
            'domains' => 'array',
            'domains.*' => 'unique:sso_client_domains,name|regex:/^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/'
        ]);

        $sso_client = new SsoClient();
        $sso_client->client_name = $request->input('client_name');
        $sso_client->label = $request->input('label');
        $sso_client->identity_provider = $request->input('identity_provider');

        if (!$sso_client->save()) {
            return Redirect::back()
                ->with(['error' => Lang::get('sso_clients.create_error')])
                ->withInput();
        }

        $domain_list = $request->input('domains');

        if (is_array($domain_list)) {
            foreach ($domain_list as $domain_name) {
                if ($domain_name != '') {
                    $client_domain = new SsoClientDomain();
                    $client_domain->sso_client_id = $sso_client->id;
                    $client_domain->name = $domain_name;
                    $client_domain->save();
                }
            }
        }

        return Redirect::route('sso_clients.index')->with([
            'success' => Lang::get('sso_client.create_success')
        ]);

    }
}

?>
