@extends('layouts.app')

@section('page-title')
    {{ __('Plan') }}
@endsection

@php
    $logo = asset(Storage::url('uploads/profile/'));
@endphp

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">{{ __('Plan') }}</li>
@endsection

@section('action-button')
{{-- @permission('Create Plan')--}}
    @if (isset($setting['is_stripe_enabled']) && $setting['is_stripe_enabled'] == 'on' || isset($setting['is_paystack_enabled']) && $setting['is_paystack_enabled'] == 'on' || isset($setting['is_razorpay_enabled']) && $setting['is_razorpay_enabled'] == 'on' || isset($setting['is_mercado_enabled']) && $setting['is_mercado_enabled'] == 'on' || isset($setting['is_skrill_enabled']) && $setting['is_skrill_enabled'] == 'on' || isset($setting['is_paymentwall_enabled']) && $setting['is_paymentwall_enabled'] == 'on' || isset($setting['is_paypal_enabled']) && $setting['is_paypal_enabled'] == 'on' || isset($setting['is_flutterwave_enabled']) && $setting['is_flutterwave_enabled'] == 'on' || isset($setting['is_paytm_enabled']) && $setting['is_paytm_enabled'] == 'on' || isset($setting['is_mollie_enabled']) && $setting['is_mollie_enabled'] == 'on' || isset($setting['is_coingate_enabled']) && $setting['is_coingate_enabled'] == 'on' || isset($setting['is_sspay_enabled']) && $setting['is_sspay_enabled'] == 'on' || isset($setting['is_toyyibpay_enabled']) && $setting['is_toyyibpay_enabled'] == 'on' || isset($setting['is_bank_transfer_enabled']) && $setting['is_bank_transfer_enabled'] == 'on' || isset($setting['is_paytabs_enabled']) && $setting['is_paytabs_enabled'] == 'on')
        <div class="text-end d-flex all-button-box justify-content-md-end justify-content-center">
            <a href="#" class="btn btn-sm btn-primary" data-ajax-popup="true" data-size="lg" data-title="Add Plan"
            data-url="{{ route('plan.create') }}" data-toggle="tooltip" title="{{ __('Create Plan') }}">
            <i class="ti ti-plus"></i>
            </a>
        </div>
     @endif
   {{-- @endpermission --}}
@endsection

@section('content')

<div class="row mb-4">
    @foreach ($plans as $plan)
        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="plan_card">
                <div class="card price-card price-1 wow animate__fadeInUp" data-wow-delay="0.2s" style="
                                    visibility: visible;
                                    animation-delay: 0.2s;
                                    animation-name: fadeInUp;
                                  ">
                    <div class="card-body">
                        <span class="price-badge text-dark f-w-600 text-start f-16 ps-0 mb-2">{{ $plan->name }}</span>
                        @if (\Auth::user()->type == 'admin' && \Auth::user()->plan_id == $plan->id)
                            <div class="d-flex flex-row-reverse m-0 p-0 plan-active-status bg-primary">
                                <span class="d-flex align-items-center ">

                                    <span class="m-2">{{ __('Active') }}</span>
                                </span>
                            </div>
                        @endif

                        <div class="text-end position-absolute" style="top: 35px; right:0;">
                            <div class="">
                                @if (\Auth::user()->type != 'admin')
                                {{-- @permission('Edit Plan') --}}
                                    <div class="d-inline-flex align-items-center">
                                        <a class="btn btn-sm btn-icon  bg-light-secondary me-2" data-url="{{ route('plan.edit',$plan->id) }}" data-title="{{__('Edit Plan')}}" data-ajax-popup="true" data-size="lg" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('Edit') }}">
                                            <i  class="ti ti-edit f-20"></i>
                                        </a>
                                    </div>
                                {{-- @endpermission --}}
                                @endif
                            </div>
                        </div>
                        <h3 class="mb-3 f-w-600 text-start text-success">
                            {{ !empty($setting['CURRENCY']) ? $setting['CURRENCY'] : '$' }}{{ $plan->price . ' / ' . __(\App\Models\Plan::$arrDuration[$plan->duration]) }}</small>
                        </h3>
                        @if ($plan->description)
                                <p class="text-start">
                                    {{ $plan->description }}<br />
                                </p>
                        @endif
                        <div class="row mb-0">
                            <div class="col-4 text-center">
                                @if ($plan->max_products == '-1')
                                    <span class="h5 mb-0">{{ __('Unlimited') }}</span>
                                @else
                                    <span class="h5 mb-0">{{ $plan->max_products }}</span>
                                @endif
                                <span class="d-block text-sm">{{ __('Products') }}</span>
                            </div>
                            <div class="col-4 text-center">
                                    <span class="h5 mb-0">
                                        @if ($plan->max_stores == '-1')
                                            <span class="h5 mb-0">{{ __('Unlimited') }}</span>
                                        @else
                                            <span class="h5 mb-0">{{ $plan->max_stores }}</span>
                                        @endif
                                    </span>
                                <span class="d-block text-sm">{{ __('Store') }}</span>
                            </div>
                            <div class="col-4 text-center">
                                <span class="h5 mb-0">
                                    @if ($plan->max_users == '-1')
                                        <span class="h5 mb-0">{{ __('Unlimited') }}</span>
                                    @else
                                        <span class="h5 mb-0">{{ $plan->max_users }}</span>
                                    @endif
                                </span>
                                <span class="d-block text-sm">{{ __('Users') }}</span>
                            </div>
                        </div>
                        <div class="plan-card-detail text-center">
                            <ul class="list-unstyled d-inline-block my-4">
                                @if ($plan->enable_domain == 'on')
                                    <li class="d-flex align-items-center">
                                        <span class="theme-avtar">
                                        <i class="text-success ti ti-circle-plus"></i></span>{{ __('Custom Domain') }}
                                    </li>
                                @else
                                    <li class="text-danger d-flex align-items-center">
                                            <span class="theme-avtar">
                                            <i class="text-danger ti ti-circle-plus"></i></span>{{ __('Custom Domain') }}
                                    </li>
                                @endif
                                @if ($plan->enable_subdomain == 'on')
                                    <li class="d-flex align-items-center">
                                        <span class="theme-avtar">
                                        <i class="text-success ti ti-circle-plus"></i></span>{{ __('Sub Domain') }}
                                    </li>
                                @else
                                    <li class="text-danger d-flex align-items-center">
                                            <span class="theme-avtar">
                                            <i class="text-danger ti ti-circle-plus"></i></span>{{ __('Sub Domain') }}
                                    </li>
                                @endif
                                @if ($plan->enable_chatgpt == 'on')
                                    <li class="d-flex align-items-center">
                                        <span class="theme-avtar">
                                        <i class="text-success ti ti-circle-plus"></i></span>{{ __('Chatgpt') }}
                                    </li>
                                @else
                                    <li class="text-danger d-flex align-items-center">
                                            <span class="theme-avtar">
                                            <i class="text-danger ti ti-circle-plus"></i></span>{{ __('Chatgpt') }}
                                    </li>
                                @endif
                                @if ($plan->pwa_store == 'on')
                                    <li class="d-flex align-items-center">
                                        <span class="theme-avtar">
                                            <i class="text-success ti ti-circle-plus"></i
                                        ></span>
                                            {{ __('Progressive Web App (PWA)') }}
                                    </li>
                                @else
                                    <li class="text-danger d-flex align-items-center">
                                        <span class="theme-avtar">
                                        <i class="text-danger ti ti-circle-plus"></i></span>{{ __('Progressive Web App (PWA)') }}
                                </li>
                                @endif
                                @if ($plan->shipping_method == 'on')
                                    <li class="d-flex align-items-center">
                                        <span class="theme-avtar">
                                            <i class="text-success ti ti-circle-plus"></i
                                        ></span>
                                            {{ __('Shipping Method') }}
                                    </li>
                                @else
                                    <li class="text-danger d-flex align-items-center">
                                        <span class="theme-avtar">
                                        <i class="text-danger ti ti-circle-plus"></i></span>{{ __('Shipping Method') }}
                                </li>
                                @endif
                                @if ($plan->enable_tax == 'on')
                                    <li class="d-flex align-items-center">
                                        <span class="theme-avtar">
                                        <i class="text-success ti ti-circle-plus"></i></span>{{ __('Enable Tax') }}
                                    </li>
                                @else
                                    <li class="text-danger d-flex align-items-center">
                                            <span class="theme-avtar">
                                            <i class="text-danger ti ti-circle-plus"></i></span>{{ __('Enable Tax') }}
                                    </li>
                                @endif
                                @if ($plan->storage_limit != '0.00')
                                    <li class="d-flex align-items-center">
                                        <span class="theme-avtar">
                                            <i class="text-success ti ti-circle-plus"></i
                                        ></span>
                                            {{ $plan->storage_limit}}{{ __('MB Storage') }}
                                    </li>
                                @else
                                    <li class="text-danger d-flex align-items-center">
                                        <span class="theme-avtar">
                                        <i class="text-danger ti ti-circle-plus"></i></span>{{ __('0 MB Storage') }}
                                </li>
                                @endif
                            </ul>

                        </div>

                        <div class="row">
                            @if (\Auth::user()->type != 'super admin')
                                @if($plan->price <= 0)
                                    <div class="col-12">
                                        <p class="server-plan font-bold text-center bg-primary mb-0 btn btn-primary w-100 text-success">
                                            {{ __('Unlimited') }}
                                        </p>
                                    </div>
                                @elseif (\Auth::user()->plan == $plan->id && date('Y-m-d') < \Auth::user()->plan_expire_date && \Auth::user()->is_trial_done != 1)
                                    <h5 class="h6 my-4">
                                        {{ __('Expired : ') }}
                                        {{ \Auth::user()->plan_expire_date? \App\Models\Utility::dateFormat(\Auth::user()->plan_expire_date): __('Unlimited') }}
                                    </h5>
                                @elseif(\Auth::user()->plan == $plan->id && !empty(\Auth::user()->plan_expire_date) && \Auth::user()->plan_expire_date < date('Y-m-d'))
                                    <div class="col-12">
                                        <p class="server-plan font-bold text-center bg-primary mb-0 btn btn-primary w-100 text-success">
                                            {{ __('Expired') }}
                                        </p>
                                    </div>
                                @else
                                    <div class="{{ $plan->id == 1 ? 'col-12' : 'col-8' }}">
                                        <a href="{{ route('stripe', \Illuminate\Support\Facades\Crypt::encrypt($plan->id)) }}"
                                           class="btn  btn-primary d-flex justify-content-center align-items-center ">{{ __('Subscribe') }}
                                            <i class="fas fa-arrow-right ms-2"></i></a>

                                    </div>
                                @endif
                            @endif
                            @if (\Auth::user()->type != 'super admin' && \Auth::user()->plan != $plan->id)
                                @if ($plan->id != 1)
                                    @if (\Auth::user()->requested_plan != $plan->id)
                                        <div class="col-4">
                                            <a href="{{ route('send.request',[\Illuminate\Support\Facades\Crypt::encrypt($plan->id)]) }}"
                                               class="btn btn-primary btn-icon"
                                               data-title="{{ __('Send Request') }}"  data-bs-toggle="tooltip" data-bs-placement="top"
                                               title="{{ __('Send Request') }}">
                                                <span class="btn-inner--icon"><i class="fas fa-share"></i></span>
                                            </a>
                                        </div>
                                    @else
                                        <div class="col-4">
                                            <a href="{{  route('request.cancel',\Auth::user()->id) }}"
                                               class="btn btn-icon m-0 btn-danger"
                                               data-title="{{ __('Cancle Request') }}"  data-bs-toggle="tooltip" data-bs-placement="top"
                                               title="{{ __('Cancel Request') }}">
                                                <span class="btn-inner--icon"><i class="fas fa-times"></i></span>
                                            </a>
                                        </div>
                                    @endif
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body table-border-style">
                <h5></h5>
                <div class="table-responsive">
                    <table class="table mb-0 dataTable ">
                        <thead>
                        <tr>
                            <th> {{ __('Order Id') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Plan Name') }}</th>
                            <th> {{ __('Price') }}</th>
                            <th> {{ __('Payment Type') }}</th>
                            <th> {{ __('Status') }}</th>
                            <th> {{ __('Coupon') }}</th>
                            <th> {{ __('Invoice') }}</th>
                            @if(\Auth::user()->type == "super admin")
                            <th> {{ __('Action') }}</th>
                            @endif
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td>{{ $order->order_id }}</td>
                                <td>{{ $order->created_at->format('d M Y') }}</td>
                                <td>{{ $order->user_name }}</td>
                                <td>{{ $order->plan_name }}</td>
                                <td>{{ GetCurrency() . $order->price }}</td>
                                <td>{{ $order->payment_type }}</td>
                                <td>
                                    @if ($order->payment_status == 'succeeded')
                                        <i class="mdi mdi-circle text-primary"></i>
                                        {{ ucfirst($order->payment_status) }}
                                    @else
                                        <i class="mdi mdi-circle text-danger"></i>
                                        {{ ucfirst($order->payment_status) }}
                                    @endif
                                </td>
                                <td>{{ !empty($order->total_coupon_used)? (!empty($order->total_coupon_used->coupon_detail)? $order->total_coupon_used->coupon_detail->code: '-'): '-' }}
                                </td>

                                <td class="text-center">
                                    @if ($order->receipt != 'free coupon' && $order->payment_type == 'STRIPE')
                                        <a href="{{ $order->receipt }}" title="Invoice" target="_blank"
                                           class=""><i class="fas fa-file-invoice"></i> </a>
                                    @elseif($order->receipt == 'free coupon')
                                        <p>{{ __('Used') . '100 %' . __('discount coupon code.') }}</p>
                                    @elseif($order->payment_type == 'Manually')
                                        <p>{{ __('Manually plan upgraded by super admin') }}</p>
                                    @elseif ($order->payment_type == 'Bank Transfer')
                                        <a href="{{ asset($order->receipt) }}" class="btn  btn-outline-primary" target="_blank">
                                            <i class="fas fa-file-invoice"></i>
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                @if(\Auth::user()->type == "super admin")
                                    <td class="text-center">
                                        @if ($order->payment_status == 'Pending' && $order->payment_type == 'Bank Transfer')
                                            <button class="btn btn-sm btn-primary me-2"
                                                data-url="{{ route('order.show', $order->id) }}" data-size="lg"
                                                data-ajax-popup="true" data-title="{{ __('Payment Status') }}" title="{{ __('Details') }}">
                                                <i class="ti ti-caret-right" data-bs-toggle="tooltip" title="edit"></i>
                                            </button>
                                        @endif
                                        {!! Form::open(['method' => 'DELETE', 'route' => ['bank_transfer.destroy', $order->id], 'class' => 'd-inline']) !!}
                                        <button type="button" class="btn btn-sm btn-danger show_confirm">
                                            <i class="ti ti-trash text-white py-1" data-bs-toggle="tooltip"
                                                title="Delete"></i>
                                        </button>
                                        {!! Form::close() !!}
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
