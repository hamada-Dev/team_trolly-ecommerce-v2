{{Form::model($productAttribute, array('route' => array('product-attributes.update', $productAttribute->id), 'method' => 'PUT', 'enctype' => 'multipart/form-data')) }}

<div class="row">
    @foreach (config('translation.languages') as $code => $language)
    <div class="form-group col-12">
        {!! Form::label('', __($language['label']) . ' ' . __('Name'), ['class' => 'form-label']) !!}
        {!! Form::text("name[$code]", $productAttribute->getTranslations('name')[$code], ['class' => 'form-control font-style', 'required' => 'required',]) !!}
    </div>
    @endforeach
    <div class="modal-footer pb-0">
        <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="Update" class="btn btn-primary">
    </div>
</div>
{!! Form::close() !!}
