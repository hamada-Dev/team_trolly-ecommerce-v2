{{ Form::model($shipping, ['route' => ['shipping.update', $shipping->id], 'method' => 'PUT', 'enctype' => 'multipart/form-data']) }}

@if (isset(auth()->user()->currentPlan) && auth()->user()->currentPlan->enable_chatgpt == 'on')
    <div class="d-flex justify-content-end mb-1">
        <a href="#" class="btn btn-primary me-2 ai-btn" data-size="lg" data-ajax-popup-over="true"
            data-url="{{ route('generate', ['shipping']) }}" data-bs-toggle="tooltip" data-bs-placement="top"
            title="{{ __('Generate') }}" data-title="{{ __('Generate Content With AI') }}">
            <i class="fas fa-robot"></i> {{ __('Generate with AI') }}
        </a>
    </div>
@endif

<div class="row">
    @foreach (config('translation.languages') as $code => $language)
        <div class="form-group col-12">
            {!! Form::label('', __($language['label']) . ' ' . __('Name'), ['class' => 'form-label']) !!}
            {!! Form::text("name[$code]",  $shipping->getTranslations('name')[$code], ['class' => 'form-control name']) !!}
        </div>
    @endforeach
    @foreach (config('translation.languages') as $code => $language)
        <div class="form-group col-12">
            {!! Form::label('', __($language['label']) . ' ' . __('Description'), ['class' => 'form-label']) !!}
            {!! Form::textarea("description[$code]", $shipping->getTranslations('description')[$code], ['class' => 'form-control autogrow', 'rows' => '3']) !!}
        </div>
    @endforeach

    <div class="modal-footer pb-0">
        <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="Update" class="btn btn-primary">
    </div>
</div>
{!! Form::close() !!}
