@extends('layouts/_auth')
@section('main')
<!-- <div > -->
    <div class="drawer">
        <div class="drawer-content" style="display: none">
            <a href="{{ URL::to('overview') }}" target="_blank">DYNAFIOS App Overview</a>
        </div>
        <a class="drawer-handle" href="#">
            <img src="{{ asset('assets/img/auth/drawer-handle.png') }}"/>
        </a>
    </div>
    <div class="drawer drawer-sso">
        <div class="drawer-content" style="display: none">
            <a href="{{ URL::to('/login/organization/login') }}" >Single Sign On</a>
        </div>
        <a class="drawer-handle" href="#"><img src="{{ asset('assets/img/auth/drawer-handle-sso.png') }}"/></a>
    </div>

<!-- </div> -->

{{ Form::open([ 'class' => 'form form-auth login-form' ]) }}
<div class="form-header">
    <h1><span>DYNAFIOS APP</span> Sign In</h1>

    <p>Welcome to your <strong>DYNAFIOS</strong> Dashboard. Please sign in to access your provider information.</p>

</div>
@include('layouts/_flash')
<div class="form-group">
    {{ Form::email('email', Request::old('email'), [ 'class' => 'form-control', 'placeholder' => 'Email' ]) }}
    {!! $errors->first('email', '<p  id="error-message" class="validation-error">:message</p>') !!}
</div>
<div class="form-group">
    {{ Form::password('password', [ 'class' => 'form-control', 'placeholder' => 'Password', 'autocomplete' => 'off']) }}
    {!! $errors->first('password', '<p class="validation-error">:message</p>') !!}
</div>
<div class="links">
    {{ Form::checkbox('remember') }} Remember me | <a href="{{ URL::route('password.remind') }}">Forgot Password?</a>
</div>
<button class="btn btn-default btn-submit" type="submit">
    <i class="fa fa-sign-in fa-fw"></i> Sign In
</button>

<!-- <div class="container-fluid"></div>
<button disabled class="invisible" ></button>
<div class="container-fluid d-flex justify-content-center align-items-center" >
        <button class="text-success center-block btn-sm btn bg-toolbar" name="action" value="single-sign-on" type="submit">
            Log in with your organization
</button>
</div> -->

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