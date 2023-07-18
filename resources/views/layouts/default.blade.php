<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta charset="utf-8"/>

    <meta http-equiv="x-ua-compatible" content="IE=edge,chrome=1"/>
    <meta name="author" content="Concrete Crumbs Design + Development"/>
    <meta name="description" content="DYNAFIOS Dashboard"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ !empty($title) ? $title . ' - ' : '' }} DYNAFIOS</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700" rel="stylesheet">
    <link type="text/css" rel="stylesheet" href="{{ asset('assets/css/font-awesome.min.css') }}"/>
    <link type="text/css" rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}"/>
    <link type="text/css" rel="stylesheet" href="{{ asset('assets/css/bootstrap-theme.min.css') }}"/>
    <link type="text/css" rel="stylesheet" href="{{ asset('assets/css/jasny-bootstrap.min.css') }}"/>
    <link type="text/css" rel="stylesheet" href="{{ asset('assets/css/bootstrap-datetimepicker.min.css') }}"/>
    <link type="text/css" rel="stylesheet" href="{{ asset('assets/css/dashboard.css') }}"/>
    <link type="text/css" rel="stylesheet" href="{{ asset('assets/css/signature-pad.css') }}"/>
    <link type="text/css" rel="stylesheet" href="{{ asset('assets/css/jquery-ui.css') }}"/>
    <link type="text/css" rel="stylesheet" href="{{ asset('assets/css/rangeSlider-flat.css') }}"/>
    <link type="text/css" rel="stylesheet" href="{{ asset('assets/css/metisMenu.min.css') }}"/>
    <link type="image/x-icon" rel="icon" href="{{ asset('assets/favicon.ico') }}"/>
    <link type="text/css" rel="stylesheet" href="{{ asset('assets/css/jquery.dataTables.min.css') }}"/>
    <link type="text/css" rel="stylesheet" href="{{ asset('assets/css/autocomplete.css') }}"/>
    <link type="text/css" rel="stylesheet" href="{{ asset('assets/css/login.css') }}"/>
    <link type="text/css" rel="stylesheet" href="{{ asset('assets/css/selectize.bootstrap3.min.css') }}"/>


    <script type="text/javascript" src="{{ asset('assets/js/modernizr.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/metisMenu.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/jquery.dataTables.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/selectize.min.js') }}"></script>

</head>
<body class="@yield('body-class', 'default')">
@yield('body')

<script type="text/javascript" src="{{ asset('assets/js/jquery.blockui.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/jquery.cycle2.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/jquery.drawer.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/moment.js') }}"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.6.0/moment.js"></script>
<script type="text/javascript" src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/jasny-bootstrap.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/bootstrap-datetimepicker.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/handlebars.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/typeahead.bundle.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/dashboard.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/jquery-ui.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/jquery-ui.multidatespicker.js') }}"></script>

@yield('scripts')
</body>
</html>
