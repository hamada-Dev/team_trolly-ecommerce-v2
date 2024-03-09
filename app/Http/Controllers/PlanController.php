<?php

namespace App\Http\Controllers;

use App\Models\{Addon, Plan};
use App\Models\PlanOrder;
use App\Models\Utility;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $objUser = \Auth::user();
        // if (auth()->user()->isAbleTo('Manage Plan'))
        // {
            if ($objUser->type == 'super admin') {
                $orders = PlanOrder::select(
                    [
                        'plan_orders.*',
                        'users.name as user_name',
                    ]
                )->join('users', 'plan_orders.user_id', '=', 'users.id')->orderBy('plan_orders.created_at', 'DESC')->get();
            } else {
                $orders = PlanOrder::select(
                    [
                        'plan_orders.*',
                        'users.name as user_name',
                    ]
                )->join('users', 'plan_orders.user_id', '=', 'users.id')->orderBy('plan_orders.created_at', 'DESC')->where('users.id', '=', $objUser->id)->get();
            }

            $plans = Plan::get();
            $setting = getSuperAdminAllSetting();
            // $admin_payments_setting = getSuperAdminAllSetting();
            return view('plans.index', compact('plans','setting','orders'));

        // }
        // else
        // {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // if (auth()->user()->isAbleTo('Create Plan')) {
        $arrDuration = Plan::$arrDuration;
        $theme = Addon::where('status','1')->get();
        $setting = getSuperAdminAllSetting();
        return view('plans.create', compact('arrDuration','theme','setting'));
    // } else {
    //     return redirect()->back()->with('error', __('Permission denied.'));
    // }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // if (auth()->user()->isAbleTo('Create Plan'))
        // {
            // if (\Auth::guard('admin')->user()->type == 'super admin' || (isset($admin_payments_setting['is_stripe_enabled']) && $admin_payments_setting['is_stripe_enabled'] == 'on'))
            // {
                $validation = [];
                $validation['name'] = 'required|unique:plans';
                $validation['price'] = 'required|numeric|min:0';
                $validation['duration'] = 'required';
                $validation['max_stores'] = 'required|numeric';
                $validation['max_products'] = 'required|numeric';
                $validation['max_users'] = 'required|numeric';
                $validation['storage_limit'] = 'required|numeric';
                $request->validate($validation);


                $post = $request->all();
                if($request->has('themes')){
                    $post['themes'] = implode(',',$request->themes);
                }
                if (Plan::create($post)) {
                    return redirect()->back()->with('success', __('Plan created Successfully!'));
                } else {
                    return redirect()->back()->with('error', __('Something is wrong'));
                }
            // } else {
            //     return redirect()->back()->with('error', __('Please set atleast one payment api key & secret key for add new plan'));
            // }
        // }
        // else
        // {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }

    }

    /**
     * Display the specified resource.
     */
    public function show(Plan $plan)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Plan $plan)
    {
        // if (auth()->user()->isAbleTo('Edit Plan'))
        // {
            $arrDuration = Plan::$arrDuration;
            $theme = Addon::where('status','1')->get();
            $setting = getSuperAdminAllSetting();
            return view('plans.edit',compact('plan','arrDuration', 'theme', 'setting'));
        // }
        // else
        // {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Plan $plan)
    {
        // if (auth()->user()->isAbleTo('Edit Plan'))
        // {
            // if (\Auth::user()->type == 'super admin' || (isset($admin_payments_setting['is_stripe_enabled']) && $admin_payments_setting['is_stripe_enabled'] == 'on'))
            // {
                if ($plan) {
                    if ($plan->price > 0) {
                        $validator = \Validator::make(
                            $request->all(), [
                                'name' => 'required|unique:plans,name,' . $plan->id,
                                'price' => 'required|numeric|min:0',
                                'duration' => 'required',
                                'max_stores' => 'required|numeric',
                                'max_products' => 'required|numeric',
                                'max_users' => 'required|numeric',
                                'storage_limit' => 'required|numeric',
                            ]
                        );
                    } else {
                        $validator = \Validator::make(
                            $request->all(), [
                                'name' => 'required|unique:plans,name,' . $plan->id,
                                'duration' => 'required',
                                'max_stores' => 'required|numeric',
                                'max_products' => 'required|numeric',
                                'max_users' => 'required|numeric',
                                'image' => 'mimes:jpeg,png,jpg,gif,svg,pdf,doc|max:20480',
                            ]
                        );
                    }
                    {

                    }
                    if ($validator->fails()) {
                        $messages = $validator->getMessageBag();

                        return redirect()->back()->with('error', $messages->first());
                    }

                    $post = $request->all();

                    if (!isset($request->enable_domain)) {
                        $post['enable_domain'] = 'off';
                    }
                    if (!isset($request->enable_subdomain)) {
                        $post['enable_subdomain'] = 'off';
                    }
                    if (!isset($request->enable_chatgpt)) {
                        $post['enable_chatgpt'] = 'off';
                    }
                    if (!isset($request->pwa_store)) {
                        $post['pwa_store'] = 'off';
                    }

                    if (!isset($request->shipping_method)) {
                        $post['shipping_method'] = 'off';
                    }

                    if (!isset($request->enable_tax)) {
                        $post['enable_tax'] = 'off';
                    }

                    if($request->has('themes')){
                        $post['themes'] = implode(',',$request->themes);
                    }

                    if ($plan->update($post)) {
                        return redirect()->back()->with('success', __('Plan updated Successfully!'));
                    } else {
                        return redirect()->back()->with('error', __('Something is wrong'));
                    }
                } else {
                    return redirect()->back()->with('error', __('Plan not found'));
                }
            // } else {
            //     return redirect()->back()->with('error', __('Please set atleast one payment api key & secret key for add new plan'));
            // }
        // }
        // else
        // {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Plan $plan)
    {
        //
    }


    public function planPrepareAmount(Request $request)
    {

        $plan = Plan::find(\Illuminate\Support\Facades\Crypt::decrypt($request->plan_id));

        if ($plan) {
            $original_price = number_format($plan->price);
            $coupons = PlanCoupon::where('code', strtoupper($request->coupon))->where('is_active', '1')->first();
            $coupon_id = null;
            if (!empty($coupons)) {
                $usedCoupun = $coupons->used_coupon();
                if ($coupons->limit == $usedCoupun) {
                } else {
                    $discount_value = ($plan->price / 100) * $coupons->discount;
                    $plan_price = $plan->price - $discount_value;
                    $price = $plan->price - $discount_value;
                    $discount_value = '-' . $discount_value;
                    $coupon_id = $coupons->id;
                    return response()->json(
                        [
                            'is_success' => true,
                            'discount_price' => $discount_value,
                            'final_price' => $price,
                            'price' => $plan_price,
                            'coupon_id' => $coupon_id,
                            'message' => __('Coupon code has applied successfully.'),
                        ]
                    );
                }
            } else {
                return response()->json(
                    [
                        'is_success' => true,
                        'final_price' => $original_price,
                        'coupon_id' => $coupon_id,
                        'price' => $plan->price,
                    ]
                );
            }
        }
    }
}
