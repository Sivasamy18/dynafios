@extends('layouts/email')
@section('body')
    <p>Dear Dr. {{ $name }},</p>
    <p>
        As part of your contract(s) with {{$hospital}} you have been enrolled in the Dynafios APP, for submitting all
        time and activity logs associated with these agreements electronically. Dynafios APP is a pprovider
        time-tracking tool that will allow you to easily submit activity logs associated with your applicable hospital
        contracts each month on a mobile device or via the Dynafios APP website.
    </p>
    <p>
        For complete instructions regarding installation of the Dynafios APP on your mobile device, please click
        here:<br/>
        <a href="{{ URL::to('/assets/pdf/DynafiosApp-PhysicianUserGuide.pdf') }}" download>The Dynafios APP
            Physician Instructional Guide</a>
    </p>
    <p>
        OR follow <a href="{{ URL::to('/assets/pdf/DynafiosApp-PhysicianUserGuide.pdf') }}"
                     download>instructions</a> on how to access the Dynafios APP through our website <a
                href="{{ URL::route('auth.login') }}">{{ URL::route('auth.login') }}</a>
    </p>
    <p>
        Your temporary login credentials are below. You will be required to change your password upon first login.Your
        new password must be between 8 and 20 characters and must contain: lower & upper case letters, numbers and
        atleast one special character (!@#$%&*).
    </p>
    <p>
        <strong>Username:</strong> {{ $email }}<br/>
        <strong>Password:</strong> {{ $password }}
    </p>
    <p>
        We look forward to working with you to make this an easy process to capture and submit all activities related to
        your contracts.Â  If you have any questions, please feel free to reach out to us at the link below.
    </p>
    <p>
        <small>Thanks,<br/>The Dynafios APP Support Team<br/>{{ HTML::mailto('support@dynafiosapp.com') }}</small>
    </p>
    @endsection