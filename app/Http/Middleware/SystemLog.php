<?php namespace App\Http\Middleware;

use Closure;
use App\Start\Dashboard;
use Auth;
use DateTime;

class SystemLog {

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		// Log the current user action to the database.
		Dashboard::log_action();

		// Update the "seen_at" field if the user is currently logged in.
		if (Auth::check()) {
			$DateTime = new DateTime();
			$DateTime->modify('+1 hours');
			$DateTime->format("Y-m-d H:i:s");
			Auth::user()->seen_at = $DateTime;
			Auth::user()->save();
		}

		return $next($request);
	}

}