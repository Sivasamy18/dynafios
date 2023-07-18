<?php namespace App\Providers;

use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
use View;
use Auth;
use Redirect;
use URL;

class RouteServiceProvider extends ServiceProvider
{

    /**
     * This namespace is applied to the controller routes in your routes file.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
//    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @param \Illuminate\Routing\Router $router
     * @return void
     */
    // public function boot(Router $router)
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            //Adding our original routes file...
            Route::namespace('App\Http\Controllers')
                ->group(app_path('Http/routes.php'));

            //Adding our proper api file
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            //Adding our proper web file
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    protected function configureRateLimiting()
    {
        $executed = RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(env('THROTTLE_RATE_LIMIT', 60))->by(optional($request->user())->id ?: $request->ip());
        });

    }
}
