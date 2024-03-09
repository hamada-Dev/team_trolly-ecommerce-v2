{{Form::model($Testimonial, array('route' => array('testimonial.update', $Testimonial->id), 'method' => 'PUT', 'enctype' => 'multipart/form-data')) }}
<div class="row">
    @foreach (config('translation.languages') as $code => $language)
        <div class="form-group col-12">
            {!! Form::label('', __($language['label']) . ' ' . __('Title'), ['class' => 'form-label']) !!}
            {!! Form::text("title[$code]", $Testimonial->getTranslations('title')[$code], ['class' => 'form-control font-style', 'required' => 'required']) !!}
        </div>
    @endforeach

    @foreach (config('translation.languages') as $code => $language)
        <div class="form-group col-12">
            {!! Form::label('', __($language['label']) . ' ' . __('Description'), ['class' => 'form-label']) !!}
            {!! Form::textarea("description[$code]", $Testimonial->getTranslations('description')[$code], ['class' => 'form-control autogrow', 'rows' => '3']) !!}
        </div>
    @endforeach
    <div class="form-group  col-md-6">
        {!! Form::label('', __('Category'), ['class' => 'form-label']) !!}
        {!! Form::select('maincategory_id', $main_categorys, null, ['class' => 'form-control', 'data-role' => 'tagsinput', 'id' => 'maincategory_id']) !!}
    </div>
    <div class="form-group  col-md-6 subcategory_id_div" data_val='{{ $Testimonial->subcategory_id }}'>
        {!! Form::label('', __('Subcategory'), ['class' => 'form-label']) !!}
        <span>
            {!! Form::select('subcategory_id', $sub_categorys, null, ['class' => 'form-control', 'data-role' => 'tagsinput', 'id' => 'subcategory-dropdown']) !!}
        </span>
    </div>
    <div class="form-group  col-md-6 product_id_div" data_val='{{ $Testimonial->product_id }}'>
        {!! Form::label('', __('Product'), ['class' => 'form-label']) !!}
        <span>
            {!! Form::select('product_id', $product, null, ['class' => 'form-control', 'data-role' => 'tagsinput', 'id' => 'product_id']) !!}
        </span>
    </div>
    <div class="form-group  col-md-6">
        {!! Form::label('', __('Rating'), ['class' => 'form-label']) !!}
        {!! Form::select('rating_no', ['1' => 1,'2' => 2,'3' => 3,'4' => 4,'5' => 5,], null, ['class' => 'form-control', 'data-role' => 'tagsinput', 'id' => 'rating_no']) !!}
    </div>

    <div class="form-group col-md-4">
        {!! Form::label('', __('Status'), ['class' => 'form-label']) !!}
        <div class="form-check form-switch">
            <input type="hidden" name="status" value="0">
            <input type="checkbox" name="status" class="form-check-input input-primary" id="customCheckdef1" value="1"
                checked>
            <label class="form-check-label" for="customCheckdef1"></label>
        </div>
    </div>

    <div class="modal-footer pb-0">
        <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="Update" class="btn btn-primary">
    </div>
</div>
{!! Form::close() !!}
