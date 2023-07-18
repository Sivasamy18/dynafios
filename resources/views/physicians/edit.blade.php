@extends('layouts/_physician', ['tab' => 6])
@section('content')
    {{ Form::open([ 'class' => 'form form-horizontal form-edit-physician' ]) }}
    {{ Form::hidden('id', $physician->id) }}
    <div class="panel panel-default">
        <div class="panel-heading">
            Physician Settings
            <!-- physician to multiple hospital by 1254 -->
            <!-- issue :  Deleted Practice Manager, add existing Practice Manager -->
            <a style="float: right; margin-top: -7px" class="btn btn-primary"
               href="{{ route('physicians.editPractice', [$physician->id,$practice->id,$hospital_id]) }}">
                Change Practice
            </a>
            <!-- physician to multiple hospital by 1254 -->
            <a style="float: right; margin-top: -7px; margin-right: 10px" class="btn btn-primary"
               href="{{ route('physicians.interfacedetails', [$physician->id,$practice->id]) }}">
                Interface Details
            </a>
        </div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-xs-2 control-label">Email</label>

                <div class="col-xs-5">
                    {{ Form::text('email', Request::old('email', $physician->email), [ 'class' => 'form-control' ]) }}
                </div>
                <div class="col-xs-5">{!! $errors->first('email', '<p id="error-message" class="validation-error">:message</p>') !!}</div>
            </div>
            <div class="form-group">
                <label class="col-xs-2 control-label">First Name</label>

                <div class="col-xs-5">
                    {{ Form::text('first_name', Request::old('first_name', $physician->first_name), [ 'class' =>
                    'form-control' ]) }}
                </div>
                <div class="col-xs-5">
                    {!! $errors->first('first_name', '<p class="validation-error">:message</p>') !!}
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-2 control-label">Last Name</label>

                <div class="col-xs-5">
                    {{ Form::text('last_name', Request::old('last_name', $physician->last_name), [ 'class' => 'form-control'
                    ]) }}
                </div>
                <div class="col-xs-5">
                    {!! $errors->first('last_name', '<p class="validation-error">:message</p>') !!}
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-2 control-label">NPI</label>

                <div class="col-xs-5">
                    {{ Form::text('npi', Request::old('npi', $physician->npi), [ 'class' => 'form-control' ]) }}
                </div>
                <div class="col-xs-5">{!! $errors->first('npi', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <div class="form-group">
                <label class="col-xs-2 control-label">Phone</label>

                <div class="col-xs-5">
                    {{ Form::text('phone', Request::old('phone', $physician->phone), [ 'class' => 'form-control',
                    'placeholder' => '(999) 999-9999' ]) }}
                </div>
                <div class="col-xs-5">
                    {!! $errors->first('phone', '<p class="validation-error">:message</p>') !!}
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-2 control-label">Specialty</label>

                <div class="col-xs-5">
                    {{ Form::select('specialty', $specialties, Request::old('specialty', $physician->specialty_id), [ 'class'
                    => 'form-control' ]) }}
                </div>
            </div>

        </div>

        <?php
        // below code added for custome invoice support by akash

        // if($note_count < App\InvoiceNote::PHYSICIANCOUNT){
        //     $note_count = App\InvoiceNote::PHYSICIANCOUNT;
        // }
        // if($invoice_type == 1){
        //     if($note_count < App\InvoiceNote::PHYSICIANCOUNT){
        //         $note_count = App\InvoiceNote::PHYSICIANCOUNT;
        //     }
        // } else {
        //     if($note_count < 1){
        //         $note_count = 1;
        //     }
        // }
        use App\InvoiceNote;
        use function App\Start\is_super_user;

        if ($note_count < 1) {
            $note_count = 1;
        }
        ?>
        {{ Form::hidden('note_count',Request::old('note_count',$note_count),['id' => 'note_count']) }}
        <div id="notes">
            @for($i = 0; $i < Request::old('note_count',$note_count); $i++ )
                <div class="form-group invoive-note">
                    <label class="col-xs-2 control-label">Invoice Note {{ $i+1 }}</label>
                    <div class="col-xs-5">
                        {{ Form::textarea("note".($i+1), Request::old("note".($i+1),(isset($invoice_notes[$i+1]) ) ? $invoice_notes[$i+1] : ''), [ 'class' => 'form-control','id' => "note".($i+1),'maxlength' => 50, 'rows' => 2, 'cols' => 54, 'style' => 'resize:none' ]) }}
                    </div>
                    <div class="col-xs-2">
                        <button class="btn btn-primary btn-submit remove-note" type="button"> -</button>
                    </div>
                    <div
                        class="col-xs-3">{!! $errors->first('note'.($i+1), '<p class="validation-error">:message</p>') !!}</div>
                </div>
            @endfor
        </div>
        <button class="btn btn-primary btn-submit add-note" type="button">Add Invoice Note</button>
        @if (is_super_user())
            <div class="form-group">
                <label class="col-xs-2 control-label">Locked</label>

                <div class="col-xs-4">
                    <div id="toggle" class="input-group">
                        <label class="switch">
                            {{ Form::checkbox('locked', 1, Request::old('locked',$user->locked), ['id' => 'locked']) }}
                            <div class="slider round"></div>
                            <div class="text"></div>
                        </label>
                    </div>
                </div>
                <div class="col-xs-5"></div>
            </div>
        @endif
    </div>
    <div class="panel-footer clearfix">
        <button class="btn btn-primary btn-sm btn-submit" type="submit" onclick="return validateEmailField(email_domains)">Submit</button>
    </div>
    </div>
    {{ Form::close() }}
    @include('audits.audit-history', ['audits' => $physician->audits()->orderBy('created_at', 'desc')->with('user')->paginate(50)])

    <script type="text/javascript">
    let email_domains = '{{ env("EMAIL_DOMAIN_REJECT_LIST") }}';
    </script>
@endsection