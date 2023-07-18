@extends('layouts/_auth')
@section('main')
{{ Form::open([ 'class' => 'form form-auth login-form' ]) }}
<div class="form-header">
    <h1><span>DYNAFIOS</span> <br/> Organizational Sign In</h1>
</div>
@include('layouts/_flash')
<p>Welcome to your <strong>DYNAFIOS</strong> Dashboard. Please provide email address.</p>
<div class="form-group">
    <div class="input-group">
        <span class="input-group-addon"><i class="fa fa-user fa-fw"></i> </span>
        {{ Form::email('email', Request::old('email'), [ 'class' => 'form-control', 'placeholder' => 'Email' ]) }}
    </div>
</div>
<div class="links">
    <a href="{{ URL::route('auth.login') }}">Return to Sign In</a>
</div>
{{ Form::button('Continue', [ 'class' => 'btn btn-default btn-submit', 'type' => 'submit']) }}
{!! $errors->first('email', '<p id="error-message" class="validation-error">:message</p>') !!}
{{ Form::close() }}
@endsection
@section('featured')
<div class="cycle-slideshow-container">
    <div class="cycle-slideshow"
         data-cycle-fx="scrollHorz"
         data-cycle-timeout="5000"
         data-cycle-slides="> .slides > .slide"
         data-cycle-prev="> .cycle-prev"
         data-cycle-next="> .cycle-next"
         data-cycle-pager=".cycle-pager"
         data-cycle-pager-template="<a></a>">
        <div class="slides">
            <div class="slide">
                <div class="col-xs-6">
                    <img src="{{ asset('assets/img/auth/dynafios-dashboard.png') }}" alt="DYNAFIOS Interactive Dashboard"/>
                </div>
                <div class="col-xs-6">
                    <h1>
                        The <em>Dynafios APP</em> Dashboards
                        <small>For Health Systems and Practices</small>
                    </h1>
                    <p>
                        The Dynafios APP delivers interactive dashboards and<br>
                        reporting that allows system users the ability to quickly<br>
                        understand and manage what is going on with provider <br>
                        activities and payments.
                    </p>
                </div>
            </div>
            <div class="slide">
                <div class="col-xs-6">
                    <img src="{{ asset('assets/img/auth/global-access.png') }}" alt="DYNAFIOS Global Access"/>
                </div>
                <div class="col-xs-6">
                    <h1>
                        The <em>Dynafios APP</em> Analytics
                        <small>For Administrators and Providers</small>
                    </h1>
                    <p>
                        The Dynafios APP is built with robust analytics<br>
                        to help users understand where dollars are being<br>
                        spent, effectiveness of provider agreements and the<br>
                        ability to do compliance audits...just for starters!
                    </p>
                </div>
            </div>
            <div class="slide">
                <div class="col-xs-6">
                    <img src="{{ asset('assets/img/auth/dynafios-support.png') }}" alt="DYNAFIOS Support"/>
                </div>
                <div class="col-xs-6">
                    <h1>
                        The <em>Dynafios APP</em> Support
                        <small>For Administrators and Providers</small>
                    </h1>
                    <p>
                        We want to be there for you!  Whether a user is<br>
                        having problems logging in or simply has a question,<br>
                        please let us know by contacting the support team at<br>
                        <a href="mailto:support@dynafiosapp.com">support@dynafiosapp.com</a>.
                    </p>
                    <a class="btn btn-default" href="mailto:support@dynafiosapp.com"><i class="fa fa-envelope fa-fw"></i> Get
                        Help</a>
                </div>
            </div>
            <div class="slide">
                <div class="col-xs-6">
                    <img src="{{ asset('assets/img/auth/dynafios-app.png') }}" alt="DYNAFIOS App"/>
                </div>
                <div class="col-xs-6">
                    <h1>
                        The <em>Dynafios APP</em> Mobility
                        <small>For Administrators and Providers</small>
                    </h1>
                    <p>
                    An easy-to-use mobile application, the Dynafios APP<br>
                    can be accessed from any device by a provider to easily<br>
                    enter time, activities and effort specific to their<br> 
                    contractual arrangements.
                    </p>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <a class="cycle-prev cycle-control" href="#"></a>
        <a class="cycle-next cycle-control" href="#"></a>
        
        <div class="cycle-pager"></div>
    </div>
</div>

@endsection