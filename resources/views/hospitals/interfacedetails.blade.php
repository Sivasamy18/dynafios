@extends('layouts/_hospital', [ 'tab' => 7 ])
@section('content')
    {{ Form::open([ 'class' => 'form form-horizontal' ]) }}
    {{ Form::hidden('hospital_id', $hospital->id) }}
    {{ Form::hidden('interface_type_id', $interfaceType) }}
    <div class="panel panel-default">
        <div class="panel-heading">
            Hospital Interface Settings
            <a style="float: right; margin-top: -7px" class="btn btn-primary"
               href="{{ route('hospitals.edit', [$hospital->id]) }}">
                Back
            </a>
        </div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-xs-2 control-label">Interface Type</label>
                <div class="col-xs-5">
                    {{ Form::select('interface_type_id', $interfaceTypes, Request::old('interface_type_id', $interfaceType), [ 'class' => 'form-control'
                    ]) }}
                </div>
            </div>

            <!-- LAWSON INTERFACE FIELD NAMES -->
            <div id="lawsonFieldNames" class="hidden">
                <div class="form-group">
                    <label class="col-xs-2 control-label">Protocol</label>
                    <div class="col-xs-5">
                        {{ Form::select('protocol', array('ftp' => 'FTPS', 'sftp' => 'SFTP'), Request::old('protocol', $interfaceDetailsLawson->protocol), [ 'class' => 'form-control'
                        ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('protocol', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">Host</label>
                    <div class="col-xs-5">
                        {{ Form::text('host', Request::old('host', $interfaceDetailsLawson->host), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('host', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">Port</label>
                    <div class="col-xs-5">
                        {{ Form::text('port', Request::old('port', $interfaceDetailsLawson->port), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('port', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">Username</label>
                    <div class="col-xs-5">
                        {{ Form::text('username', Request::old('username', $interfaceDetailsLawson->username), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('username', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">Password</label>
                    <div class="col-xs-5">
                        {{ Form::text('password', Request::old('password', $interfaceDetailsLawson->password), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('password', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">APCINVOICE Filename Prefix</label>
                    <div class="col-xs-5">
                        {{ Form::text('apcinvoice_filename', Request::old('apcinvoice_filename', $interfaceDetailsLawson->apcinvoice_filename), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('apcinvoice_filename', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">APCDISTRIB Filename Prefix</label>
                    <div class="col-xs-5">
                        {{ Form::text('apcdistrib_filename', Request::old('apcdistrib_filename', $interfaceDetailsLawson->apcdistrib_filename), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('apcdistrib_filename', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">API Authorization Username</label>
                    <div class="col-xs-5">
                        {{ Form::text('api_username', Request::old('api_username', $interfaceDetailsLawson->api_username), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('api_username', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">API Authorization Password</label>
                    <div class="col-xs-5">
                        {{ Form::text('api_password', Request::old('api_password', $interfaceDetailsLawson->api_password), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('api_password', '<p class="validation-error">:message</p>') !!}</div>
                </div>
            </div>

            <!-- IMAGENOW INTERFACE FIELD NAMES -->
            <div id="imagenowFieldNames" class="hidden">
                <div class="form-group">
                    <label class="col-xs-2 control-label">Protocol</label>
                    <div class="col-xs-5">
                        {{ Form::select('protocol_imagenow', array('FTPS' => 'FTPS', 'SFTP' => 'SFTP', 'EMAIL' => 'Email'), Request::old('protocol_imagenow', $interfaceDetailsImageNow->protocol), [ 'class' => 'form-control'
                        ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('protocol_imagenow', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">Host</label>
                    <div class="col-xs-5">
                        {{ Form::text('host_imagenow', Request::old('host_imagenow', $interfaceDetailsImageNow->host), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('host_imagenow', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">Port</label>
                    <div class="col-xs-5">
                        {{ Form::text('port_imagenow', Request::old('port_imagenow', $interfaceDetailsImageNow->port), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('port_imagenow', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">Username</label>
                    <div class="col-xs-5">
                        {{ Form::text('username_imagenow', Request::old('username_imagenow', $interfaceDetailsImageNow->username), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('username_imagenow', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">Password</label>
                    <div class="col-xs-5">
                        {{ Form::text('password_imagenow', Request::old('password_imagenow', $interfaceDetailsImageNow->password), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('password_imagenow', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">Email</label>
                    <div class="col-xs-5">
                        {{ Form::text('email', Request::old('email', $interfaceDetailsImageNow->email), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('email', '<p id="error-message" class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">API Authorization Username</label>
                    <div class="col-xs-5">
                        {{ Form::text('api_username_imagenow', Request::old('api_username_imagenow', $interfaceDetailsImageNow->api_username_imagenow), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('api_username_imagenow', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">API Authorization Password</label>
                    <div class="col-xs-5">
                        {{ Form::text('api_password_imagenow', Request::old('api_password_imagenow', $interfaceDetailsImageNow->api_password_imagenow), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('api_password_imagenow', '<p class="validation-error">:message</p>') !!}</div>
                </div>
            </div>

        <div class="panel-footer clearfix">
            <button class="btn btn-primary btn-sm btn-submit" type="submit">Submit</button>
        </div>
    </div>
    {{ Form::close() }}
@endsection
@section('scripts')
<script type="text/javascript">
    $(function () {

        $( "select[name|='interface_type_id']" ).change(function() {
                if($( "select[name|='interface_type_id']" ).val()==='1'){
                    $( "#lawsonFieldNames" ).removeClass("hidden").addClass("show");
                }
                else {
                    $( "#lawsonFieldNames" ).removeClass("show").addClass("hidden");
                }
                if($( "select[name|='interface_type_id']" ).val()==='2'){
                    $( "#imagenowFieldNames" ).removeClass("hidden").addClass("show");
                }
                else {
                    $( "#imagenowFieldNames" ).removeClass("show").addClass("hidden");
                }
        });

        if($( "select[name|='interface_type_id']" ).val()==='1'){
            $( "#lawsonFieldNames" ).removeClass("hidden").addClass("show");
        }
        else {
            $( "#lawsonFieldNames" ).removeClass("show").addClass("hidden");
        }
        if($( "select[name|='interface_type_id']" ).val()==='2'){
            $( "#imagenowFieldNames" ).removeClass("hidden").addClass("show");
        }
        else {
            $( "#imagenowFieldNames" ).removeClass("show").addClass("hidden");
        }

    })
</script>
@endsection