{{ Form::model($city, ['route' => ['city.update', $city->id], 'method' => 'put']) }}
<div class="row">
    @foreach (config('translation.languages') as $code => $language)
        <div class="form-group col-12">
            {!! Form::label('', __($language['label']) . ' ' . __('Name'), ['class' => 'form-label']) !!}
            {!! Form::text("name[$code]", $city->getTranslations('name')[$code], ['class' => 'form-control font-style', 'required' => 'required', 'city' => 'city']) !!}
        </div>
    @endforeach

    <div class="form-group col-md-12">
        {{ Form::label('country', __('Country'), ['class' => 'col-form-label']) }}
        <select class="form-control" id="country_id" name="country_id">
            <option value="" disabled selected>{{ __('Select Country') }}</option>
            @foreach ($countries as $key => $count)
                <option value="{{ $key }}" {{ $country->name == $count ? 'selected' : '' }}>{{ $count }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group col-md-12">
        {{ Form::label('state', __('State'), ['class' => 'col-form-label']) }}
        <select class="form-control" id="state_id" name="state_id">

            <option value="" disabled selected>{{ __('Select State') }}</option>

            @foreach ($states as $key => $count)
                <option value="{{ $key }}" {{ $state->name == $count ? 'selected' : '' }}>{{ $count }}
                </option>
            @endforeach
        </select>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn btn-primary ms-2">
</div>
{{ Form::close() }}
