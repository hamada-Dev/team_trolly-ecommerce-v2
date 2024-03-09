@extends('layouts.app')
@section('page-title')
    {{ __('Landing Page') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Landing Page') }}</li>
@endsection

@php
    $lang = \App\Models\Utility::getValByName('default_language');
    $logo = get_file('uploads/logo');
    $logo_light = \App\Models\Utility::getValByName('logo_light');
    $logo_dark = \App\Models\Utility::getValByName('logo_dark');
    $company_favicon = \App\Models\Utility::getValByName('company_favicon');
    $setting = getSuperAdminAllSetting();
    $color = !empty($setting['color']) ? $setting['color'] : 'theme-3';
    $SITE_RTL = isset($setting['SITE_RTL']) ? $setting['SITE_RTL'] : 'off';
    $meta_image = get_file('uploads/meta/');

@endphp



@push('custom-script')
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Landing Page') }}</li>
@endsection


@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="row">
                <div class="col-xl-3">
                    <div class="card sticky-top" style="top:30px">
                        <div class="list-group list-group-flush" id="useradd-sidenav">

                            @include('landingpage::layouts.tab')


                        </div>
                    </div>
                </div>

                <div class="col-xl-9">
                    {{--  Start for all settings tab --}}


                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-lg-10 col-md-10 col-sm-10">
                                    <h5>{{ __('Plan Section') }}</h5>
                                </div>
                            </div>
                        </div>

                        {{ Form::open(['route' => 'pricing_plan.store', 'method' => 'post', 'enctype' => 'multipart/form-data']) }}
                        <div class="card-body">
                            <div class="row">

                                @foreach (config('translation.languages') as $code => $language)
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {{ Form::label('Title', __($language['label']) . ' ' . __('Title'), ['class' => 'form-label']) }}
                                            {{ Form::text("plan_title[$code]", $settings['plan_title_translation']->getTranslations('value')[$code] ?? $settings['plan_title'], ['class' => 'form-control', 'placeholder' => __('Enter Title')]) }}
                                            @error('mail_host')
                                                <span class="invalid-mail_port" role="alert">
                                                    <strong class="text-danger">{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                @endforeach

                                @foreach (config('translation.languages') as $code => $language)
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {{ Form::label('Heading', __($language['label']) . ' ' . __('Heading'), ['class' => 'form-label']) }}
                                            {{ Form::text("plan_heading[$code]", $settings['plan_heading_translation']->getTranslations('value')[$code] ?? $settings['plan_heading'], ['class' => 'form-control', 'placeholder' => __('Enter Heading')]) }}
                                            @error('screenshots_heading')
                                                <span class="invalid-mail_port" role="alert">
                                                    <strong class="text-danger">{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                @endforeach


                                @foreach (config('translation.languages') as $code => $language)
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            {{ Form::label('Description', __($language['label']) . ' ' . __('Description'), ['class' => 'form-label']) }}
                                            {{ Form::text("plan_description[$code]", $settings['plan_description_translation']->getTranslations('value')[$code] ?? $settings['plan_description'], ['class' => 'form-control', 'placeholder' => __('Enter Description')]) }}
                                            @error('mail_host')
                                                <span class="invalid-mail_port" role="alert">
                                                    <strong class="text-danger">{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                @endforeach


                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <input class="btn btn-print-invoice btn-primary m-r-10" type="submit"
                                value="{{ __('Save Changes') }}">
                        </div>
                        {{ Form::close() }}
                    </div>


                    {{--  End for all settings tab --}}
                </div>
            </div>
        </div>
    </div>
@endsection
