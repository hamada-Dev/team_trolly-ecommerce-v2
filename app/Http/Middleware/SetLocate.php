<?php

namespace App\Http\Middleware;

use App\Models\{Plan, User};
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class SetLocate
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
        if(auth()->user())
        {
            $user = auth()->user();

            if($user->type == 'admin')
            {

                // if($plan)
                // {
                    $datetime1 = new \DateTime($user->plan_expire_date);
                    $datetime2 = new \DateTime(date('Y-m-d'));

                    $interval = $datetime2->diff($datetime1);
                    $days     = $interval->format('%r%a');

                    if($days <= 0)
                    {
                        $plan = Plan::where('duration','Unlimited')->first();

                        $user->assignPlan($plan->id,$user->id);

                        // return redirect()->route('plan.index')->with('error', __('Your Plan is expired.'));
                    }
                // }
            }
        }
        $lang = (session()->get('lang')) ? session()->get('lang') : 'en';

        if(auth()->check())
        {
            $lang = auth()->user()->language;
        }else{
            $superadmin = User::where('type','super admin')->first();
            $lang = !empty($lang) ? $lang : $superadmin->default_language;
        }

        App::setLocale($lang);
        return $next($request);
    }
}
