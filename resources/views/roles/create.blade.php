{{ Form::open(['route' => 'roles.store', 'method' => 'post']) }}

<div class="row">
    <div class="form-group">
        {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}
        <div class="form-icon-user">
            {{ Form::text('name', null, ['class' => 'form-control', 'placeholder' => __('Enter Role Name')]) }}
        </div>

        @error('name')
            <span class="invalid-name" role="alert">
                <strong class="text-danger">{{ $message }}</strong>
            </span>
        @enderror
    </div>

    <div class="form-group">
        @if (!empty($permissions))
            <h6 class="my-3">{{ __('Assign Permission to Roles') }} </h6>
            <table class="table  mb-0" id="dataTable-1">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" class="align-middle checkbox_middle form-check-input" name="checkall"
                                id="checkall">
                        </th>
                        <th>{{ __('Module') }} </th>
                        <th>{{ __('Permissions') }} </th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $modules = [
                            'user',
                        ];
                    @endphp
                    @foreach ($modules as $module)
                        <tr>
                            <td><input type="checkbox" class="align-middle ischeck form-check-input" name="checkall"
                                    data-id="{{ str_replace(' ', '', $module) }}"></td>
                            <td><label class="ischeck form-label"
                                    data-id="{{ str_replace(' ', '', $module) }}">{{ ucfirst($module) }}</label>
                            </td>
                            <td>
                                <div class="row">
                                    @if (in_array($module . ' manage' , (array) $permissions))
                                        @if ($key = array_search($module . ' manage', $permissions))
                                            <div class="col-md-3 custom-control custom-checkbox">
                                                {{ Form::checkbox('permissions[]', $key, false, ['class' => 'form-check-input isscheck isscheck_' . str_replace(' ', '', $module), 'id' => 'permission' . $key]) }}
                                                {{ Form::label('permission' . $key, 'Manage', ['class' => 'form-label font-weight-500']) }}<br>
                                            </div>
                                        @endif
                                    @endif
                                    @if (in_array($module . ' create'  , (array) $permissions))
                                        @if ($key = array_search($module . ' create', $permissions))
                                            <div class="col-md-3 custom-control custom-checkbox">
                                                {{ Form::checkbox('permissions[]', $key, false, ['class' => 'form-check-input isscheck isscheck_' . str_replace(' ', '', $module), 'id' => 'permission' . $key]) }}
                                                {{ Form::label('permission' . $key, 'Create', ['class' => 'form-label font-weight-500']) }}<br>
                                            </div>
                                        @endif
                                    @endif
                                    @if (in_array($module . ' edit' , (array) $permissions))
                                        @if ($key = array_search($module . ' edit', $permissions))
                                            <div class="col-md-3 custom-control custom-checkbox">
                                                {{ Form::checkbox('permissions[]', $key, false, ['class' => 'form-check-input isscheck isscheck_' . str_replace(' ', '', $module), 'id' => 'permission' . $key]) }}
                                                {{ Form::label('permission' . $key, 'Edit', ['class' => 'form-label font-weight-500']) }}<br>
                                            </div>
                                        @endif
                                    @endif
                                    @if (in_array($module . ' delete' , (array) $permissions))
                                        @if ($key = array_search($module . ' delete', $permissions))
                                            <div class="col-md-3 custom-control custom-checkbox">
                                                {{ Form::checkbox('permissions[]', $key, false, ['class' => 'form-check-input isscheck isscheck_' . str_replace(' ', '', $module), 'id' => 'permission' . $key]) }}
                                                {{ Form::label('permission' . $key, 'Delete', ['class' => 'form-label font-weight-500']) }}<br>
                                            </div>
                                        @endif
                                    @endif
                                    @if (in_array($module . ' profile manage'  , (array) $permissions))
                                        @if ($key = array_search($module . ' profile manage', $permissions))
                                            <div class="col-md-3 custom-control custom-checkbox">
                                                {{ Form::checkbox('permissions[]', $key, false, ['class' => 'form-check-input isscheck isscheck_' . str_replace(' ', '', $module), 'id' => 'permission' . $key]) }}
                                                {{ Form::label('permission' . $key, 'Show', ['class' => 'form-label font-weight-500']) }}<br>
                                            </div>
                                        @endif
                                    @endif
                                    @if (in_array( $module . ' reset password' , (array) $permissions))
                                        @if ($key = array_search($module . ' reset password', $permissions))
                                            <div class="col-md-3 custom-control custom-checkbox">
                                                {{ Form::checkbox('permissions[]', $key, false, ['class' => 'form-check-input isscheck isscheck_' . str_replace(' ', '', $module), 'id' => 'permission' . $key]) }}
                                                {{ Form::label('permission' . $key, 'Upgrade', ['class' => 'form-label font-weight-500']) }}<br>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn  btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
</div>
{{ Form::close() }}

<script>
    $(document).ready(function() {
        $("#checkall").click(function() {
            $('input:checkbox').not(this).prop('checked', this.checked);
        });
        $(".ischeck").click(function() {
            var ischeck = $(this).data('id');
            $('.isscheck_' + ischeck).prop('checked', this.checked);
        });
    });
</script>
