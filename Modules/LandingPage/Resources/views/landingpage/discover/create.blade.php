{{ Form::open(array('route' => 'discover_store', 'method'=>'post', 'enctype' => "multipart/form-data")) }}
    <div class="modal-body">
        @csrf
        <div class="row">

            @foreach (config('translation.languages') as $code => $language)
                <div class="col-md-12">
                    <div class="form-group">
                        {{ Form::label('Heading', __($language['label']) . ' ' . __('Heading'), ['class' => 'form-label']) }}
                        {{ Form::text("discover_heading[$code]", $settings['discover_heading_translation']->getTranslations('value')[$code] ?? $settings['discover_heading'], ['class' => 'form-control', 'placeholder' => __('Enter Heading')]) }}
                    </div>
                </div>
            @endforeach


            @foreach (config('translation.languages') as $code => $language)
                <div class="col-md-12">
                    <div class="form-group">
                        {{ Form::label('Description', __($language['label']) . ' ' . __('Description'), ['class' => 'form-label']) }}
                        {{ Form::text("discover_description[$code]", $settings['discover_description_translation']->getTranslations('value')[$code] ?? $settings['discover_description'], ['class' => 'form-control', 'placeholder' => __('Enter Description')]) }}
                    </div>
                </div>
            @endforeach

            <div class="col-md-12">
                <div class="form-group">
                    {{ Form::label('Logo', __('Logo'), ['class' => 'form-label']) }}
                    <input type="file" name="discover_logo" class="form-control" required="required">
                </div>
            </div>

        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{__('Create')}}" class="btn  btn-primary">
    </div>
{{ Form::close() }}
<script>
    tinymce.init({
      selector: '#mytextarea',
      menubar: '',
    });
</script>
