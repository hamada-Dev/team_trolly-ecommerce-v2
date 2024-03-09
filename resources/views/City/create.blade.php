{{ Form::open(['route' => 'city.store', 'method' => 'post', 'enctype' => 'multipart/form-data']) }}
<div class="row">
    @foreach (config('translation.languages') as $code => $language)
        <div class="form-group col-12">
            {!! Form::label('', __($language['label']) . ' ' . __('Name'), ['class' => 'form-label']) !!}
            {!! Form::text("name[$code]", null, ['class' => 'form-control font-style', 'required' => 'required']) !!}
        </div>
    @endforeach
    <div class="form-group  col-md-12">
        {!! Form::label('', __('Country'), ['class' => 'form-label']) !!}
        {!! Form::select('country_id', $countries, null, [
            'class' => 'form-control',
            'id' => 'country_id',
            'placeholder' => 'Select Option',
        ]) !!}
    </div>
    <div class="form-group col-md-12">
        {{ Form::label('state_id', __('State'), ['class' => 'form-label']) }}
        {{ Form::select('state_id', [], null, ['class' => 'form-control', 'id' => 'state_id', 'required' => 'required', 'placeholder' => 'Select Option']) }}
    </div>
    <div class="modal-footer pb-0">
        <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="Create" class="btn btn-primary">
    </div>
</div>
{!! Form::close() !!}
