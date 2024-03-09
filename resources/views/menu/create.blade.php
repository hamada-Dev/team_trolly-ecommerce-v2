{{ Form::open(['route' => 'menus.store', 'method' => 'post', 'enctype' => 'multipart/form-data']) }}
<div class="row">
    @foreach (config('translation.languages') as $code => $language)
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('Name', __($language['label']) . ' ' . __('Name'), ['class' => 'form-label']) }}
                {{ Form::text("name[$code]", null, ['class' => 'form-control', 'placeholder' => __('Enter Name')]) }}
                @error('name')
                    <span class="invalid-mail_port" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
    @endforeach
    <div class="modal-footer pb-0">
        <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="Create" class="btn btn-primary">
    </div>
</div>
{!! Form::close() !!}
