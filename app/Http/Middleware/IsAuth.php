<?php

namespace App\Http\Middleware;

use Closure;
use \App\Sessions;
use App\Models\User;

class IsAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if( ! $request->hasHeader('X-Token-Secure') ) {
            return response('error parameters', 404);
        }
        
        $id = User::select('id')->where('api_token', '=', $request->header('X-Token-Secure') )->first();
        
        if ( $id ) {
            $request->merge(['account_id' => $id->toArray()['id']]);
            return $next($request);
        }
        
        return response('error parameters', 404);
    }
}
