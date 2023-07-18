@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-laptop icon"></i> 
        <span> SSO Client: {{ $client_name }} </span>
    </h3>
    <div class="btn-group btn-group-sm">
        <a class="btn btn-default" href="{{ URL::route('sso_clients.index') }}"><i class="fa fa-arrow-circle-left fa-fw"></i> Back</a>
    </div>
</div>

@include('layouts/_flash')
{{ Form::open([ 'class' => 'form form-horizontal form-create-ssoclient' ]) }}
<div class="panel panel-default">
    <div class="panel-heading">SSO Client Settings</div>
    <div class="panel-body">

        <div class="form-group">
            <label class="col-xs-2 control-label">Client Name</label>
            <div class="col-xs-5">
                {{ Form::text('client_name', Request::old('client_name',$client_name), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('client_name', '<p class="validation-error">:message</p>') !!}</div>
        </div>

        <div class="form-group">
            <label class="col-xs-2 control-label">SSO Button Label</label>

            <div class="col-xs-5">
                {{ Form::text('label', Request::old('label', $label), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('label', '<p class="validation-error">:message</p>') !!}</div>
        </div>

        <div class="form-group">
            <label class="col-xs-2 control-label">Identity Provider Name (AWS Cognito)</label>

            <div class="col-xs-5">
                {{ Form::text('identity_provider', Request::old('identity_provider', $identity_provider), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('identity_provider', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        
        <div id="domains">
            @foreach($client_domains as $domain)
                <div class="form-group client-domain">
                    <label class="col-xs-2 control-label" data-domain-id="{{$domain['id']}}" >Domain</label>
                    <div class="col-xs-5" >
                        {{ Form::textarea("domains[".$domain['id']."]", Request::old("domain".$domain['id'], $domain['name']), [ 'class' => 'form-control','id' => "domain".$domain['id'],'maxlength' => 50, 'rows' => 2, 'cols' => 54, 'style' => 'resize:none' ]) }}
                    </div>
                    <div class="col-xs-2"><button class="btn btn-primary btn-submit remove-client-domain" id="button-1" type="button"> - </button></div>
                    @if ($errors->any())
                        @foreach ($errors->toArray() as $key=>$error)
                            @if( $key == 'domains.'.$domain['id'])
                                <div class="col-xs-5">{!! $errors->first( $key , '<p class="validation-error">:message</p>') !!}</div>
                                @break
                            @endif
                        @endforeach
                    @endif
                </div>
            @endforeach
        </div>

        <a class="btn btn-default btn-delete" href="{{ URL::route('sso_clients.delete', $id) }}">
            <i class="fa fa-trash-o fa-fw"></i> Delete
        </a>    
        <button class="btn btn-primary btn-submit add-client-domain" type="button">Add Domain</button>

    </div>
</div>
<button class="btn btn-primary btn-submit" type="submit">Submit</button>
{{ Form::close() }}
@endsection
@section('scripts')
<script type="text/javascript">
    $(function () {
        // $("input[name=npi]").inputmask({ mask: '9999999999' });
        // $("input[name=expiration]").inputmask({ mask: '99/99/9999' });
    })
</script>
@endsection