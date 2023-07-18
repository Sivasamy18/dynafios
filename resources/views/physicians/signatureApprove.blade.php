@php use function App\Start\is_physician; @endphp

@extends('layouts/_dashboard')
@section('main')
    <style>
        #default .main {
            padding: 0px !important;
        }

        #default .main .container {
            padding: 10px 30px !important;
            min-height: 800px !important;
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

    {{ Form::open([ 'class' => 'form form-horizontal','url' => 'signatureApprove','onsubmit'=>'submitLog()' ]) }}
    {{ Form::hidden('p_id', $physician->id, array('id' => 'physician_id')) }}
    {{ Form::hidden('c_id', $contract_id, array('id' => 'c_id')) }}
    {{ Form::hidden('s_id', $signature_id, array('id' => 'c_id')) }}
    {{ Form::hidden('date_selector', $date_selector, array('id' => 'date_selector')) }}
    {{ Form::hidden('type', 0, array('id' => 'c_id')) }}
    {{ Form::hidden('user_type', $user_type, array('id' => 'user_type')) }}
    {{ Form::hidden('timeZone', '', array('id' => 'timeZone')) }}
    {{ Form::hidden('localTimeZone', '', array('id' => 'localTimeZone')) }}
    {{ Form::hidden('h_id', $hospital_id, array('id' => 'h_id')) }}
    {{ Form::hidden('pmt_type_id', $payment_type_id, array('id' => 'pmt_type_id')) }}
    {{ Form::hidden('questions_answer_annually', 0, array('id' => 'questions_answer_annually')) }}
    {{ Form::hidden('questions_answer_monthly', 0, array('id' => 'questions_answer_monthly')) }}
    {{ Form::hidden('date_range', 0, array('id' => 'date_range')) }}

    <div class="panel panel-default">
        <div class="panel-heading">
            Signature
        </div>
        <div class="panel-body">

            @if(isset($msg))
                <div id="alert_signature" class="alert alert-success">
                    {{ $msg }}</div>
                @if(is_physician())
            @section('scripts')
                <script type="text/javascript">
                    $('.save').prop('disabled', true);
                    $(".overlay").show();
                    // setTimeout(function () {
                    //   window.location.href = "{{route('physician.dashboard', [$physician->id,"c_id".$contract_id])}}" ;
                    var originalUrl = "{{route('physician.dashboard', [$physician->id])}}";
                    var paramUrl = originalUrl + "?h_id=" + "{{$hospital_id}}" + "&c_id=" + "{{$contract_id}}";
                    window.location.href = paramUrl;
                    // }, 500);
                </script>
            @endsection
            @else
                @if($payment_type_id === 3 || $payment_type_id === 5)
            @section('scripts')
                <script type="text/javascript">
                    $('.save').prop('disabled', true);
                    $(".overlay").show();
                    // setTimeout(function () {
                    window.location.href = "{{route('practices.onCallEntry', [$practice_id,$contract_id])}}";
                    // }, 500);
                </script>
            @endsection
            @else
            @section('scripts')
                <script type="text/javascript">
                    $('.save').prop('disabled', true);
                    $(".overlay").show();
                    // setTimeout(function () {
                    window.location.href = "{{route('practices.physicianLogEntry', [$practice_id,$contract_id])}}";
                    // }, 500);
                </script>
            @endsection
            @endif
            @endif
            @endif
            @if(isset($signature))
                <div id="signature_view" style="text-align: center; position: relative; height: 75vh">
                    <img style="width: 40%; height: auto;" src="data:image/png;base64,{{$signature}}"/>
                    <div class="m-signature-pad--footer" style="left: 0;">
                        <div class="line"></div>
                        @if(is_physician())
                            <div class="col-xs-2">
                                <p style="width: 700px; display: inline-block;">
                                    By submitting signature. you are verifying that all time logs are accurate and
                                    exact. Please sign as legible as possible and press submit.
                                </p>
                            </div>
                            <a class="btn btn-primary btn-sm btn-submit button"
                               style="margin-left: 7px; display: inline-block; text-align: center;" name="bntClear"
                               id="bntClear"
                               href="{{ route('physicians.signatureApprove_edit', [$physician->id,$contract_id,'']) }}">
                                Clear
                            </a>
                        @endif
                        <div>
                            <button class="btn btn-primary btn-sm btn-submit button save" data-action="save"
                                    type="submit">Submit
                            </button>
                        </div>
                    </div>
                    @else
                        <div id="signature-pad" class="m-signature-pad">
                            <div class="m-signature-pad--body">
                                <canvas></canvas>
                            </div>
                            <div class="m-signature-pad--footer">
                                <div class="line"></div>
                                <div class="col-xs-2"><p style="width: 700px; display: inline-block;">
                                        By submitting signature. you are verifying that all time logs are accurate and
                                        exact. Please sign as legible as possible and press submit.
                                    </p>
                                </div>
                                <div class="description">Sign above</div>
                                <button class="btn btn-primary btn-sm btn-submit button clear" data-action="clear"
                                        type="button">Clear
                                </button>
                                <button class="btn btn-primary btn-sm btn-submit button save" data-action="save"
                                        type="button">Submit
                                </button>
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

                        <script type="text/javascript"
                                src="{{ asset('assets/js/physician_signature_pad.js') }}"></script>
                        <script type="text/javascript" src="{{ asset('assets/js/physician_app.js') }}"></script>
                    @endif
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
                        var timeZone = new Date();
                        var zoneName = Intl.DateTimeFormat().resolvedOptions().timeZone;
                        if (typeof zoneName === "undefined") {
                            timeZone = '';
                            zoneName = '';
                        }
                        $('#timeZone').val(timeZone);
                        $('#localTimeZone').val(zoneName);
                        // setTimeout(function(){
                        document.getElementById('interactive');
                        $(".overlay").hide();
                        // },500);
                    }
                }
                $("#edit_signature").click(function () {
                    $("#signature-pad").show();
                    $("#signature_view").hide();
                });
                $(document).ready(function () {
                    $('#bntClear').attr('href', $('#bntClear').attr('href') + '/' + $('#date_selector').val());
                });

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
