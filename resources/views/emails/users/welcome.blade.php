@extends('layouts/email')
@section('body')
            <p>Dear {{ $name }},</p>
            <p>
                @if(count($hospitals_name)>0)
                {{implode(",",$hospitals_name)}} @if(count($hospitals_name) == 1) is @else are @endif implementing
                the Dynafios APP
                - a Provider Time Keeping app for the submission, approval and processing of payments related to
                provider
                contracts.
                Â  This email is to introduce you to the Dynafios APP and provide you with your initial credentials to
                access the
                system.
                @else
                Welcome to the Dynafios APP - a Provider Time Keeping app for the submission, approval and processing
                of
                payments related to provider contracts.
                Â  This email is to introduce you to the Dynafios APP and provide you with your initial credentials to
                access the
                system.
                @endif
            </p>
            <p>
                You may login at:<br/>
                <a href="{{ URL::route('auth.login') }}">{{ URL::route('auth.login') }}</a>
                <br/>
                <strong>Username:</strong> {{ $email }}<br/>
                <strong>Password:</strong> {{ $password }}
            <p>
            <p>
                For complete instructions regarding the Dynafios APP application and user capabilities, please <a
                    href="{{ URL::to('/assets/pdf/guide.pdf') }}">click here</a>. Someone from the Dynafios APP support
                team
                will be setting up initial training to familiarize you with the system.
            </p>
            <p>
                If you have any questions, please feel free to reach out to us at the link below.
            </p>
            <p>
                <small>Thanks,<br/>The Dynafios APP Support Team<br/>{{ HTML::mailto('support@dynafiosapp.com')
                    }}</small>
            </p>
            @endsection