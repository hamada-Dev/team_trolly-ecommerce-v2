{!! Form::open([
    'method' => 'POST',
    'route' => ['pages.store'],
    'class' => 'needs-validation',
    'data-validate',
    'novalidate',
]) !!}
<div class="modal-body">
    <div class="row">
        @foreach (config('translation.languages') as $code => $language)
            <div class="form-group col-md-6 col-12">
                {{ Form::label('page_name', __($language['label']) . ' ' . __('Name'), ['class' => 'form-label']) }}
                {{ Form::text("page_name[$code]", null, ['class' => 'form-control', 'placeholder' => __('Page Name')]) }}
            </div>
        @endforeach


        <div class="form-group col-md-6 col-12">
            {!! Form::label('page_slug', __('Slug'), ['class' => 'col-form-label']) !!} <span class="validation-required">*</span>
            {!! Form::text('page_slug', null, ['class' => 'form-control page_slug', 'placeholder' => __('Slug Name'),'required']) !!}
        </div>
    </div>

    @foreach (config('translation.languages') as $code => $language)
        <div class="form-group">
            {{ Form::label('page_content', __($language['label']) . ' ' . __('Content'), ['class' => 'form-label']) }}
            {{ Form::textarea("page_content[$code]", null, [
                'autocomplete' => 'off',
                'class' => 'summernote-simple form-control h-auto',
                'required',
            ]) }}
        </div>
    @endforeach


    @foreach (config('translation.languages') as $code => $language)
        <div class="form-group">
            {{ Form::label('page_meta_title', __($language['label']) . ' ' . __('Meta Title'), ['class' => 'form-label']) }}
            {{ Form::textarea("page_meta_title[$code]", null, [
                'class' => 'form-control',
                'placeholder' => __('Meta Title'),
                'required',
            ]) }}
        </div>
    @endforeach



    @foreach (config('translation.languages') as $code => $language)
        <div class="form-group">
            {{ Form::label('page_meta_description', __($language['label']) . ' ' . __('Meta Description'), ['class' => 'col-form-label']) }}
            {{ Form::textarea("page_meta_description[$code]", null, [
                'autocomplete' => 'off',
                'class' => 'form-control h-auto',
                'placeholder' => __('Meta Description'),
                'rows' => 5,
                'required',
            ]) }}
        </div>
    @endforeach

    <div class="form-group col-md-12">
        {!! Form::label('page_meta_keywords', __('Meta Keywords'), ['class' => 'form-label']) !!}
        <input class="form-control" id="choices-text-remove-button" name="page_meta_keywords[]" type="text"
            value="example" placeholder="Enter something" />
        <small>{{ __('Choose Existing Attribute') }}</small>
    </div>
    <div class="error-message" id="bouncer-error_page_meta_keywords[]"></div>
</div>
<div class="modal-footer">
    {!! Form::button(__('Close'), ['class' => 'btn btn-secondary', 'data-dismiss' => 'modal']) !!}
    {!! Form::button(__('Save'), ['type' => 'submit', 'class' => 'btn btn-primary']) !!}
</div>
{!! Form::close() !!}

<script script src="{{ asset('assets/js/plugins/choices.min.js') }}"></script>
<script>
    $('.modal').on('shown.bs.modal', function() {
        var textRemove = new Choices(
            document.getElementById('choices-text-remove-button'), {
                delimiter: ',',
                editItems: true,
                maxItemCount: 5,
                removeItemButton: true,
                paste: false,
                duplicateItemsAllowed: false,
                editItems: true,
            }
        );
    });
</script>
