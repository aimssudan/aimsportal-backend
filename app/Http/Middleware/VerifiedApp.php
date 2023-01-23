<?php

namespace App\Http\Middleware;

use App\Models\VerifiedApplication;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifiedApp
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if(!$request->has('app_token'))
        {
            //Log::info(["checkToken-none"=>$request->all()]);
            return response()->error('Unauthorized', 403);

        }

        $token = VerifiedApplication::where('app_token',$request->app_token)->first();
        if(!$token)
        {
            //Log::info(["checkToken-nomatch"=>$request->all()]);
            return response()->error('Unauthorized', 403);
        }
        if ($token->disabled) return response()->error('Unauthorized', 403);

        
        $token_ip_addresses = $token->ip_addresses;
        if ($token_ip_addresses) {
            if(!in_array($request->ip(),$token_ip_addresses))
            {
                //Log::info(["checkToken-badip"=>$request->all()]);
                return response()->error('Unauthorized', 403);
                
            }
        }
        
        return $next($request);
    }
}