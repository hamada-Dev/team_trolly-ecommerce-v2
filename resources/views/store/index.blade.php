@extends('layouts.app')

@section('page-title', __('Users'))

@section('action-button')
    <div class="text-end d-flex all-button-box justify-content-md-end justify-content-center">
        <a href="#" class="btn btn-sm btn-primary" data-ajax-popup="true" data-size="md" data-title="Create New User"
            data-url="{{ route('stores.create') }}" data-toggle="tooltip" title="{{ __('Create New User') }}">
            <i class="ti ti-plus"></i>
        </a>
    </div>
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Users') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header card-body table-border-style">
                    <h5></h5>
                    <div class="table-responsive">
                        <table class="table dataTable">
                            <thead>
                                <tr>
                                    <th>{{ __('User Name') }}</th>
                                    <th>{{ __('Email') }}</th>
                                    <th>{{ __('Store') }}</th>
                                    <th>{{ __('Plan') }}</th>
                                    <th class="text-end">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    <tr>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->countStore($user->id) }}</td>
                                        <td>{{ !empty($user->currentPlan->name) ? $user->currentPlan->name : '-' }}</td>
                                        <td class="text-end">
                                            @if(auth()->user()->type == 'super admin')
                                                <button class="btn p-0" data-url=""  data-bs-original-title="{{ __('Login As Admin')}}">
                                                    <a class="btn btn-sm btn-secondary me-2" href="{{ route('login.with.admin',$user->id) }}">
                                                        <i class="ti ti-replace py-1" data-bs-toggle="tooltip" title="Login As Admin"> </i>
                                                    </a>
                                                </button>

                                                <button class="btn btn-sm btn-info me-2"
                                                    data-url="{{ route('stores.link', $user->id) }}" data-size="md"
                                                    data-ajax-popup="true" data-title="{{ __('Store Links') }}">
                                                    <i class="ti ti-unlink py-1" data-bs-toggle="tooltip" title="Store Links"></i>
                                                </button>
                                            @endif

                                            @if (auth()->user()->type == 'super admin')
                                                <button class="btn btn-sm btn-primary me-2"
                                                    data-url="{{ route('stores.edit', $user->id) }}" data-size="md"
                                                    data-ajax-popup="true" data-title="{{ __('Edit User') }}">
                                                    <i class="ti ti-pencil py-1" data-bs-toggle="tooltip" title="edit user"></i>
                                                </button>
                                            @endif

                                            @if (auth()->user()->type == 'super admin')
                                                <button class="btn btn-sm btn-warning me-2"
                                                    data-url="{{ route('plan.upgrade', $user->id) }}" data-size="md"
                                                    data-ajax-popup="true" data-title="{{ __('Upgrade Plan') }}">
                                                    <i class="ti ti-trophy py-1" data-bs-toggle="tooltip" title="upgrade plan"></i>
                                                </button>
                                            @endif

                                            @if (auth()->user()->type == 'super admin')
                                            <button class="btn btn-sm btn-secondary me-2"
                                                data-url="{{ route('stores.reset.password', \Crypt::encrypt($user->id)) }}" data-size="md"
                                                data-ajax-popup="true" data-title="{{ __('Reset Password') }}">
                                                <i class="ti ti-key py-1" data-bs-toggle="tooltip" title="reset password"></i>
                                            </button>
                                            @endif

                                            @if (auth()->user()->type == 'super admin')
                                            {!! Form::open(['method' => 'DELETE', 'route' => ['stores.destroy', $user->id], 'class' => 'd-inline']) !!}
                                            <button type="button" class="btn btn-sm btn-danger show_confirm">
                                                <i class="ti ti-trash text-white py-1" data-bs-toggle="tooltip"
                                                    title="Delete"></i>
                                            </button>
                                            {!! Form::close() !!}
                                            @endif
                                        </td>
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
