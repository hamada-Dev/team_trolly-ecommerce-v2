<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\PlanCoupon;
class PlanCouponController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        // if (\Auth::user()->isAbleTo('Manage Coupon'))
        // {
            $coupons = PlanCoupon::get();

            return view('plan-coupon.index', compact('coupons'));
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
        //
        // if (\Auth::user()->isAbleTo('Create Coupon'))
        // {
            return view('plan-coupon.create');
        // }
        // else
        // {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
      // if (\Auth::user()->isAbleTo('Create Coupon'))
        // {
            $validator = Validator::make(
                $request->all(), [
                                   'name' => 'required',
                                   'discount' => 'required|numeric',
                                   'limit' => 'required|numeric',
                                   'code' => 'required',
                                   ]
                                );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $plancoupon           = new PlanCoupon();
            $plancoupon->name     = $request->name;
            $plancoupon->discount = $request->discount;
            $plancoupon->limit    = $request->limit;
            $plancoupon->code     = strtoupper($request->code);
            $plancoupon->save();

            return redirect()->back()->with('success', __('Coupon successfully created.'));
        // }
        // else
        // {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PlanCoupon $planCoupon)
    {
        //
        //
        // if (\Auth::user()->isAbleTo('Edit Coupon'))
        // {
            return view('plan-coupon.edit', compact('planCoupon'));
        // }
        // else
        // {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,  PlanCoupon $planCoupon)
    {
        //
        // if (\Auth::user()->isAbleTo('Edit Coupon'))
        // {
            $validator = Validator::make(
                $request->all(), [
                                   'name' => 'required',
                                   'discount' => 'required|numeric',
                                   'limit' => 'required|numeric',
                                   'code' => 'required',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $plancoupon           = PlanCoupon::find($planCoupon->id);
            $plancoupon->name     = $request->name;
            $plancoupon->discount = $request->discount;
            $plancoupon->limit    = $request->limit;
            $plancoupon->code     = $request->code;

            $plancoupon->save();

            return redirect()->back()->with('success', __('Coupon successfully updated.'));
        // }
        // else
        // {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PlanCoupon $planCoupon)
    {
        //
        // if (\Auth::user()->isAbleTo('Delete Coupon'))
        // {
            $planCoupon->delete();

            return redirect()->back()->with('success', __('Coupon successfully deleted.'));
        // }
        // else
        // {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }
}
