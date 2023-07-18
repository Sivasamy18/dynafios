<div class="panel-body">
    <div class="form-group">
        <label class="col-xs-2 control-label">Email</label>

        <div class="col-xs-5">
            {{ Form::text('email', Request::old('email'), [ 'class' => 'form-control' ]) }}
        </div>
        <div class="col-xs-5" >{!! $errors->first($errors->has('emailDeleted') ? 'emailDeleted':'email', '<p id="error-message" class="validation-error">:message</p>') !!}</div>
    </div>
    <div class="form-group">
        <label class="col-xs-2 control-label">First Name</label>

        <div class="col-xs-5">
            {{ Form::text('first_name', Request::old('first_name'), [ 'class' => 'form-control' ]) }}
        </div>
        <div class="col-xs-5">
            {!! $errors->first('first_name', '<p class="validation-error">:message</p>') !!}
        </div>
    </div>
    <div class="form-group">
        <label class="col-xs-2 control-label">Last Name</label>

        <div class="col-xs-5">
            {{ Form::text('last_name', Request::old('last_name'), [ 'class' => 'form-control' ]) }}
        </div>
        <div class="col-xs-5">
            {!! $errors->first('last_name', '<p class="validation-error">:message</p>') !!}
        </div>
    </div>
    <div class="form-group">
        <label class="col-xs-2 control-label">Title</label>

        <div class="col-xs-5">
            {{ Form::text('title', Request::old('title'), [ 'class' => 'form-control' ]) }}
        </div>
        <div class="col-xs-5">
            {!! $errors->first('title', '<p class="validation-error">:message</p>') !!}
        </div>
    </div>
    <div class="form-group">
        <label class="col-xs-2 control-label">Phone</label>
        <div class="col-xs-5">
            {{ Form::text('phone', Request::old('phone'), [ 'class' => 'form-control', 'placeholder' => '(999) 999-9999' ]) }}
        </div>
        <div class="col-xs-5">
            {!! $errors->first('phone', '<p class="validation-error">:message</p>') !!}
        </div>
    </div>
    @if(Request::is('practices') || Request::is('practices/*'))
        <input type="hidden" name="group" value="{{ App\Group::PRACTICE_MANAGER }}">
    @else
        <div class="form-group">
            <label class="col-xs-2 control-label">Group</label>

            <div class="col-xs-5">
                {{ Form::select('group', $groups, Request::old('group'), [ 'class' => 'form-control' ]) }}
            </div>
        </div>
    @endif
</div>
<div class="panel-footer clearfix">
    <button class="btn btn-primary btn-sm btn-submit" type="submit" onclick="return validateEmailField(email_domains)">Submit</button>
</div>

 <script type="text/javascript">
  let email_domains = '{{ env("EMAIL_DOMAIN_REJECT_LIST") }}';
</script>
   