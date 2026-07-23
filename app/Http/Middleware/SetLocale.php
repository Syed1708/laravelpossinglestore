<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    /**
     * Handle an incoming request and set the active application locale.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if there is an active 'locale' key stored in the session.
        // Defaults to 'fr' (French) if empty.
        $locale = Session::get('locale', config('app.locale', 'fr'));
        
        App::setLocale($locale);

        return $next($request);
    }
}