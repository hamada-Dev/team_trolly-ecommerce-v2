
{{ Form::open(['route' => 'main-category.store', 'method' => 'post', 'enctype' => 'multipart/form-data']) }}

@if (isset(auth()->user()->currentPlan) && auth()->user()->currentPlan->enable_chatgpt == 'on')
<div class="d-flex justify-content-end mb-1">
    <a href="#" class="btn btn-primary me-2 ai-btn" data-size="lg" data-ajax-popup-over="true" data-url="{{ route('generate',['category']) }}" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('Generate') }}" data-title="{{ __('Generate Content With AI') }}">
        <i class="fas fa-robot"></i> {{ __('Generate with AI') }}
    </a>
</div>
@endif

<div class="row">
    @foreach (config('translation.languages') as $code => $language)
        <div class="form-group col-12">
            {!! Form::label('', __($language['label']) .' '. __('Title'), ['class' => 'form-label']) !!}
            {!! Form::text("name[$code]", null, ['class' => 'form-control name']) !!}
        </div>
    @endforeach
    <div class="form-group col-md-6">
        {!! Form::label('', __('Image'), ['class' => 'form-label']) !!}
        <label for="upload_image" class="image-upload bg-primary pointer w-100">
            <i class="ti ti-upload px-1"></i> {{ __('Choose file here') }}
        </label>
        <input type="file" name="image" id="upload_image" class="d-none">
    </div>
    <div class="form-group col-md-6">
        {!! Form::label('', __('Icon'), ['class' => 'form-label']) !!}
        <label for="upload_icon_image" class="image-upload bg-primary pointer w-100">
            <i class="ti ti-upload px-1"></i> {{ __('Choose file here') }}
        </label>
        <input type="file" name="icon_image" id="upload_icon_image" class="d-none">
    </div>

    <div class="form-group col-md-4">
        {!! Form::label('', __('Trending'), ['class' => 'form-label']) !!}
        <div class="form-check form-switch">
            <input type="hidden" name="trending" value="0">
            <input type="checkbox" name="trending" class="form-check-input input-primary" id="customCheckdef1trending" value="1">
            <label class="form-check-label" for="customCheckdef1trending"></label>
        </div>
    </div>
    <div class="form-group col-md-4">
        {!! Form::label('', __('Status'), ['class' => 'form-label']) !!}
        <div class="form-check form-switch">
            <input type="hidden" name="status" value="0">
            <input type="checkbox" class="form-check-input status" name="status"
                id="status" value="1">
            <label class="form-check-label" for="status"></label>
        </div>
    </div>
    <div class="modal-footer pb-0">
        <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="Create" class="btn btn-primary">
    </div>
</div>
{!! Form::close() !!}
