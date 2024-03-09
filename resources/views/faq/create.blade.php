{{ Form::open(['route' => 'faqs.store', 'method' => 'post', 'enctype' => 'multipart/form-data']) }}
<div class="row">

    @foreach (config('translation.languages') as $code => $language)
        <div class="form-group col-12">
            {!! Form::label('', __($language['label']) . ' ' . __('Topic'), ['class' => 'form-label']) !!}
            {!! Form::text("topic[$code]", null, ['class' => 'form-control font-style', 'required' => 'required']) !!}
        </div>
    @endforeach
</div>

<div class="modal-footer pb-0">
    <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="Create" class="btn btn-primary">
</div>
</div>
{!! Form::close() !!}
