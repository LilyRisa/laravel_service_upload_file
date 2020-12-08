<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ValidationToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        $header = $request->header('Authorization');
        $token = DB::table('etoken')->where('token', $header)->first();
        //dd($token);
        $check = false;

        if($token != null){
            $check = $this->checkdate($token->time_expire);
        }
        if($check){
            return $next($request)
                ->header('Access-Control-Allow-Origin', 'http://127.0.0.1:3333')
                ->header('Access-Control-Allow-Methods', '*')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Allow-Headers', 'X-CSRF-Token');
        }else{
            return \response()->json(['error' => 'Your token has expired !']);
        }
        
        //return $header;
    }

    protected function checkdate($time_expire){
        $now = strtotime(Carbon::now('Asia/Ho_Chi_Minh'));
        $time_expire = strtotime($time_expire);
        if($time_expire <= $now){
            return false;
        }else{
            return true;
        }
    }
}
