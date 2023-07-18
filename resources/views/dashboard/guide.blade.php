@extends('layouts/default')
@section('body')
<div class="wrapper">
<nav class="navbar navbar-default navbar-inverse navbar-static-top" style="height: 70px !important;">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse"
                    data-target="#bs-example-navbar-collapse-1">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="{{ URL::to('/') }}"><img src="{{ asset('assets/img/guide/trace.png') }}"/></a>
        </div>
        <div class="navbar-right">
            <p style="color: #fff; font-size: 18px; margin-top: 22px">DYNAFIOS App Instructional Guide</p>
        </div>
    </div>
</nav>
<section class="content container">
<header class="header">
    <img src="{{ asset('assets/img/guide/header-logo.png') }}"/>

    <div class="links">
        <a class="link" href="https://itunes.apple.com/us/app/trace-by-dynafios/id657793782?mt=8">
            <img src="{{ asset('assets/img/guide/app-store.png') }}"/>
        </a>
        <a class="link" href="https://play.google.com/store/apps/details?id=imd.trace">
            <img src="{{ asset('assets/img/guide/google-play.png') }}"/>
        </a>
    </div>
</header>
<div class="buttons">
    <a class="button" href="{{ URL::route('dashboard.index') }}"><i class="fa fa-arrow-circle-left"></i>Back to
        Dashboard</a>
    <a class="button" href="#your-profile">Your Profile</a>
    <a class="button" href="#keeping-track">Keeping Track</a>
    <a class="button" href="#navigation">Navigation</a>

    <div class="clearfix"></div>
</div>
<div style="padding-left: 50px; position: relative;">
<h2 style="color: #f68a1f">Getting Started</h2>

<p>
    To get started, please have your smart device in hand and <span style="color: red">ensure that you are
        connected to the internet or have mobile service when setting up!</span>
</p>

<p>You will need your App Store ID or other credentials to download the app onto your device.</p>

<h3>Installing DYNAFIOS App on your Device</h3>

<p>
    The DYNAFIOS App is designed for the most popular smart mobile devices:<br/>
    <strong>iOS Devices:</strong> Including iPhone, iPad and the latest generation of iPods<br/>
    <strong>Android Devices:</strong> Any mobile phone or tablet running Android OS2.2 or greater.
</p>

<div class="media">
        <span class="pull-left">
          <img src="{{ asset('assets/img/guide/download-icon.png') }}" style="margin-right: 40px;"/>
        </span>

    <div class="media-body">
        <h4 class="media-heading">Installing on iOS</h4>

        <p>
            To install DYNAFIOS on your iOS device, please tap on the button below or go to the App Store on your
            device and search for DYNAFIOS.
        </p>
        <h4 class="media-heading">Installing on Android</h4>

        <p>
            To install DYNAFIOS on your Android device, please tap on the button below or go to Google Play on
            your device and search for DYNAFIOS.
        </p>
    </div>
</div>
<h3>Log into the DYNAFIOS System</h3>

<p>
    After you have installed the DYNAFIOS application on your device, the next step is to "Login" using your login
    credentials.
</p>

<p>
    These credentials will be sent to the email you provided during the registration process. You will receive
    your password automatically once you have been added to the DYNAFIOS system as an authorized user.
</p>

<p>
    Enter your full email address and password and select Login.<br/>
    Your device must have internet access when logging in. In case you have problems accessing the system,
    please contact us.
</p>

<div class="media">
        <span class="pull-left">
          <img src="{{ asset('assets/img/guide/stay-logged-in.png') }}" style="margin-right: 40px;"/>
        </span>

    <div class="media-body">
        <p><strong>
                NOTE: <span style="color: red">We recommend checking the stay logged in checkbox.</span> This
                will ensure logs entered inside a building or hospital will be kept until your phone is in an
                active Wi-Fi or resumes cellular service.
            </strong></p>
    </div>
</div>
<h3 id="navigation">Navigation</h3>

<p>
DYNAFIOS has two key sections, "Page Title" and "Navigation Bar".
</p>

<div class="media">
        <span class="pull-left">
          <img src="{{ asset('assets/img/guide/diagram.png') }}" style="margin-right: 40px"/>
        </span>

    <div class="media-body">
        <h4 class="media-heading">Page Title</h4>

        <p>
            The Page Title gives you information about the current page of the App.
        </p>
        <h4 class="media-heading">Navigation Bar</h4>

        <p>
            The Navigation Bar is used to switch quickly and easily through the pages. It is always visible and
            you can find it on the bottom of the screen.
        </p>
    </div>
</div>
<h3 id="keeping-track">Submitting Logs</h3>

<p>
    Log in to the DYNAFIOS application and go to the New Log page, using the <strong>Navigation Bar</strong>.
</p>

<h3>Clinical Co-Management, Medical Directorship and Medical Education Logs</h3>

<p>
<ol>
    <li>Select the contract</li>
    <li>Select the action of management duty type</li>
    <li>Choose the action, or other if the action is not listed</li>
    <li>Select the date the action was performed</li>
    <li>Set the duration, minimum 1 hour and maximum 8 hours</li>
    <li>Click Save located on the top right side of your screen</li>
</ol>
</p>
<h3>On Call Logs</h3>

<p>
<ol>
    <li>Select the On Call contract</li>
    <li>Select the activity</li>
    <li>Select "Choose your days" On Call and "TAP HERE" to add dates</li>
    <li>A calendar will be displayed</li>
    <li>Select the dates by tapping on one or multiple on call dates</li>
    <li>Click Save located on the top right side of your screen</li>
</ol>
</p>
<h3>Log Submission Notes:</h3>

<p>
<ul>
    <li>
        After saving a log, you will receive an email notification indicating whether or not the log was
        successfully saved.
    </li>
    <li>
        Once you have submitted a log, it cannot be deleted. If you have saved a log and need to have it
        corrected, please contact your Practice Manager or Hospital Administrator.
    </li>
    <li>
        Logs cannot be saved with future dates.
    </li>
    <li>
        After saving your logs we recommend that you check the Recent Log section to ensure that it was saved.
    </li>
</ul>
</p>
<h4>Checking Logs</h4>

<p>
    You can check your logs on the Home Page. The DYNAFIOS App counts all your logs and gives you a list of the
    last three logs.
</p>

<div class="media">
        <span class="pull-left">
          <img src="{{ asset('assets/img/guide/time-log.png') }}" style="margin-right: 40px"/>
        </span>

    <div class="media-body">
        <h4 class="media-heading">Recent Log</h4>

        <p>
            The lower section on the home page gives you a list of your three last saved logs in full detail.
        </p>
        <h4 class="media-heading">Log Statistics</h4>

        <p>
        DYNAFIOS will display a running count of your logs. To check how many logs you have submitted so far,
            check the Logs Statistics section on the home page.
        </p>
        <h4 class="media-heading">Unsaved Logs</h4>

        <p>
            These are logs you have saved while offline. These have not yet been posted to your user account.
            (Please read "Working Offline" below).
        </p>
        <h4 class="media-heading">Saved Logs</h4>

        <p>
            Successfully saved logs.
        </p>
    </div>
</div>
<h3 id="your-profile">My Profile</h3>

<p>
    My Profile is where you can check your profile's set up: General Data and Contracts Details.
</p>
<h4><em>Navigate to My Profile page using the Navigation Bar.</em></h4>

<p style="color: red"><em>
        When you first login we recommend you go to this page and check to ensure all of your details are correct.
        If you find incorrect data please contact your Practice Manager or Hospital Administrator as soon as
        possible.
    </em></p>

<h3>General Data</h3>

<p>
    General Data contains the following information:
</p>
<ul>
    <li>First and Last Name</li>
    <li>NPI (National Provider Identifier)</li>
    <li>Phone Number</li>
    <li>Specialty</li>
    <li>Practice</li>
    <li>Hospital</li>
</ul>
<h3>Contract Details</h3>

<p>
    While on the My Profile page, tap the Contracts button on top. This section shows
    you details about your contracts. The types include at least one of the following:
</p>
<ul>
    <li>On Call</li>
    <li>Clinical Co-Management</li>
    <li>Medical Education</li>
    <li>Medical Directorship</li>
</ul>
<h3>Change Password</h3>

<p>
    While still on the My Profile page, click on the Settings button. Here you can change your password. Fill
    in the fields below and click the Submit button.
</p>

<h3>Working Offline</h3>

<p>
    Working offline means working without access to the internet.
</p>

<p>
    If you do not have access to the internet but have logged into DYNAFIOS while connected to the internet and
    marked the "stay logged in" box, DYNAFIOS will save your logs on your device until you connect to the internet.
</p>
<ul>
    <li>Log in with a working connection or wifi network</li>
    <li>Choose "stay logged in" box on the Login page during your workday</li>
    <li>Enter logs any time you wish</li>
    <li>Once on a working wifi connection or data plan your offline logs save</li>
</ul>
<h3>Logout</h3>

<p>
    Click the Logout button on the Navigation Bar.
</p>

<p style="text-align: center; margin-top: 40px;">
    Please reach out if you have questions or problems. You can create a service ticket from inside the DYNAFIOS
    App or email us at <a href="mailto:support@dynafiosapp.com">support@dynafiosapp.com</a>.
</p>
<a href="#top" style="position: absolute; right: 0; bottom: 0;">
    <img src="{{ asset('assets/img/guide/return.png') }}"/>
</a>
</div>
</section>
</div>
<link type="text/css" rel="stylesheet" href="{{ asset('assets/css/guide.min.css') }}"/>
@endsection