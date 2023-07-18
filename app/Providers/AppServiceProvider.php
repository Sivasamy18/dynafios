<?php namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Collective\Html\HtmlFacade as HTML;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;


class AppServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (!app()->environment('production')) {
            Mail::alwaysTo('Dynafiosdemo@gmail.com');
        }

        HTML::macro('active', function ($condition) {
            return $condition ? 'active' : '';
        });

        HTML::macro('hidden', function ($condition) {
            return $condition ? 'display: none' : 'display: block';
        });

        HTML::macro('sort_link', function ($title, $sort, $order, $page, $filter = 0) {
            return '<a href="' . "?sort=$sort&order=$order&filter=$filter&page=$page" . '">' . $title . '</a>';
        });

        Paginator::useBootstrap();

        $environment = App::environment();
        if ($environment === 'local') {
            URL::forceScheme('http');
            $this->app['request']->server->set('HTTP', 'off');
        } else {
            URL::forceScheme('https');
            $this->app['request']->server->set('HTTPS', 'on');
        }
    }

    public function register()
    {

    }

}
