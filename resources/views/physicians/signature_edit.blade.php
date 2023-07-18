@extends('layouts/_dashboard')
@section('main')

<style>
    #default .main {
        padding: 0px !important;
    }

    #default .main .container {
        padding: 10px 30px !important;
    }

    .m-signature-pad{
        height: 75vh;
    }

    .m-signature-pad--body
    canvas {
        position: relative;
        /*left: 0;*/
        /*top: 0;*/
        width: 750px !important;
        height: 420px;
        border: 2px solid #e8e8e8;
        border-radius: 4px;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.02) inset;
        margin: 0 auto;
        display: block;
    }

    .m-signature-pad--footer {
     position: absolute;
    }
</style>

{{ Form::open([ 'class' => 'form form-horizontal form-edit-physician' ]) }}
{{ Form::hidden('id', $physician->id, array('id' => 'physician_id')) }}
{{ Form::hidden('date_selector', $date_selector, array('id' => 'date_selector')) }}
{{ Form::hidden('c_id', 0, array('id' => 'c_id')) }}
<div class="panel panel-default">
    <div class="panel-heading">
        Signature
    </div>
    <div class="panel-body">

        <div id="alert_signature" class="alert alert-success" style="display: none;">
            Signature saved successfully.</div>
        <div id="alert_signature_error" class="alert alert-danger" style="display: none;">
            Signature not saved.</div>
        <div id="alert_signature_error_blank" class="alert alert-danger" style="display: none;">
            Please draw a signature first.</div>

            <div id="signature-pad" class="m-signature-pad">
                <div class="m-signature-pad--body">
                    <canvas></canvas>
                </div>
                <div class="m-signature-pad--footer">
                    <div class="description">Sign above</div>
                    <button class="btn btn-primary btn-sm btn-submit button clear" data-action="clear" type="button">Clear</button>
                    <button class="btn btn-primary btn-sm btn-submit button save" data-action="save" type="button">Submit</button>
                </div>
            </div>

            <script type="text/javascript">
                var _gaq = _gaq || [];
                _gaq.push(['_setAccount', 'UA-39365077-1']);
                _gaq.push(['_trackPageview']);

                (function() {
                    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
                })();

            </script>

            <script type="text/javascript" src="{{ asset('assets/js/physician_signature_pad.js') }}"></script>
            <script type="text/javascript" src="{{ asset('assets/js/physician_app.js') }}"></script>
    </div>
    <div class="panel-footer clearfix">

    </div>
</div>
{{ Form::close() }}
@endsection
@section('scripts')
    <script type="text/javascript">
        document.onreadystatechange = function () {
            var state = document.readyState;
            if (state == 'interactive') {
                $(".overlay").show();
            } else if (state == 'complete') {
                // setTimeout(function(){
                    document.getElementById('interactive');
                    $(".overlay").hide();
                // },2000);
            }
        }
    </script>
@endsection