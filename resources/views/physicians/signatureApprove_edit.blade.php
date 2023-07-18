@php use function App\Start\is_physician; @endphp
@extends('layouts/_dashboard')
@section('main')
    <style>
        #default .main {
            padding: 0px !important;
        }

        #default .main .container {
            padding: 10px 30px !important;
        }

        .m-signature-pad {
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

        .line {
            margin: 5px 0;
            height: 2px;
            background: repeating-linear-gradient(90deg, #808080 0 7px, #0000 0 12px)
            /*10px red then 2px transparent -> repeat this!*/
        }
    </style>

    {{ Form::open([ 'class' => 'form form-horizontal form-edit-physician','onsubmit'=>'submitLog()' ]) }}
    {{ Form::hidden('id', $physician->id, array('id' => 'physician_id')) }}
    {{ Form::hidden('c_id', $contract_id, array('id' => 'c_id')) }}
    {{ Form::hidden('date_selector', $date_selector, array('id' => 'date_selector')) }}
    {{ Form::hidden('questions_answer_annually', 0, array('id' => 'questions_answer_annually')) }}
    {{ Form::hidden('questions_answer_monthly', 0, array('id' => 'questions_answer_monthly')) }}
    {{ Form::hidden('date_range', 0, array('id' => 'date_range')) }}

    @if(is_physician())
        {{ Form::hidden('redirect', route('physician.dashboard', [$physician->id,"c_id".$contract_id]), array('id' => 'redirect')) }}
    @else
        {{ Form::hidden('redirect', route('practices.physicianLogEntry', [$practice_id,$contract_id]), array('id' => 'redirect')) }}
    @endif

    <div class="panel panel-default">
        <div class="panel-heading">
            Signature
        </div>
        <div class="panel-body">
            <div id="alert_signature" class="alert alert-success" style="display: none;">
                Logs approved successfully.
            </div>
            <div id="alert_signature_error" class="alert alert-danger" style="display: none;">
                Logs not approved.
            </div>
            <div id="alert_signature_error_blank" class="alert alert-danger" style="display: none;">
                Please draw a signature first.
            </div>

            <div id="signature-pad" class="m-signature-pad">
                <div class="m-signature-pad--body">
                    <canvas></canvas>
                </div>
                <div class="col-xs-12" style="padding: 0px;">
                    <div class="line" style="margin-top: 70px"></div>
                    <div class="col-xs-4">
                        <p style="padding-left:70px; width: 700px; display: inline-block; font-size: 14px; text-align:centre">
                            By submitting signature. you are verifying that all time logs are accurate and exact. Please
                            sign as legible as possible and press submit.
                        </p>
                    </div>
                    <div class="m-signature-pad--footer">

                        <div class="description" style="margin-top: 0.6em !important;">Sign above</div>
                        <button class="btn btn-primary btn-sm btn-submit button clear" data-action="clear"
                                type="button">Clear
                        </button>
                        <button class="btn btn-primary btn-sm btn-submit button save" data-action="save" type="button">
                            Submit
                        </button>
                    </div>
                </div>
            </div>

            <script type="text/javascript">
                var _gaq = _gaq || [];
                _gaq.push(['_setAccount', 'UA-39365077-1']);
                _gaq.push(['_trackPageview']);

                (function () {
                    var ga = document.createElement('script');
                    ga.type = 'text/javascript';
                    ga.async = true;
                    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                    var s = document.getElementsByTagName('script')[0];
                    s.parentNode.insertBefore(ga, s);
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
        function submitLog() {
            $('.save').prop('disabled', true);
            $(".overlay").show();
        }

        document.onreadystatechange = function () {
            var state = document.readyState;
            if (state == 'interactive') {
                $(".overlay").show();
            } else if (state == 'complete') {
                setTimeout(function () {
                    document.getElementById('interactive');
                    $(".overlay").hide();
                }, 2000);
            }
        }

        var questions_answer_annually = [];
        var questions_answer_monthly = [];
        var sessionStringAnnually = sessionStorage.getItem('annually_questions');
        var sessionStringMonthly = sessionStorage.getItem('monthly_questions');
        var sessionStringDateRange = sessionStorage.getItem('date_range');

        if (sessionStringAnnually && sessionStringAnnually != undefined) {
            var results = JSON.parse(sessionStringAnnually);
            results.forEach(element => {
                questions_answer_annually.push(element);
            });
        }
        if (sessionStringMonthly && sessionStringMonthly != undefined) {
            var results = JSON.parse(sessionStringMonthly);
            results.forEach(element => {
                questions_answer_monthly.push(element);
            });
        }

        $('#questions_answer_annually').val(JSON.stringify(questions_answer_annually));
        $('#questions_answer_monthly').val(JSON.stringify(questions_answer_monthly));
        $('#date_range').val(sessionStringDateRange);
        // sessionStorage.clear();
    </script>
@endsection
