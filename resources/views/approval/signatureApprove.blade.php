@extends('layouts/_dashboard')
@section('main')
    {{ Form::open([ 'class' => 'form form-horizontal' ]) }}
    {{ Form::hidden('log_ids', $log_ids, array('id' => 'log_ids')) }}
    {{ Form::hidden('rejected', $rejected, array('id' => 'rejected')) }}
    {{ Form::hidden('reason', $rejected_with_reasons, array('id' => 'rejected_with_reasons')) }}
    {{ Form::hidden('manager_types', $manager_types, array('id' => 'manager_types')) }}
    <div class="panel panel-default">
        <div class="panel-heading">
            Signature
        </div>
        <div class="panel-body">

            <div id="alert_signature" class="alert alert-success" style="display: none;">
                Logs approved successfully. </div>
            <div id="alert_signature_error" class="alert alert-danger" style="display: none;">
                Logs not approved.</div>
            @if(isset($signature))
                <div id="signature_view" style="text-align: center; position: relative;">
                    <img style="width: 50%; height: auto;" src="data:image/png;base64,{{$signature->signature_path}}"/>
                    <div class="m-signature-pad--footer">
                        <a class="btn btn-primary btn-sm btn-submit button clear" href="{{route('approval.edit',[$log_ids,$rejected,$rejected_with_reasons,$manager_types])}}">Clear</a>
                        <button class="btn btn-primary btn-sm btn-submit button save" type="button" style="margin-right: 7px;">Submit</button>
                    </div>
                </div>
            @else
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

                <script type="text/javascript" src="{{ asset('assets/js/signature_pad.js') }}"></script>
                <script type="text/javascript" src="{{ asset('assets/js/app.js') }}"></script>
            @endif
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
                setTimeout(function(){
                    document.getElementById('interactive');
                    $(".overlay").hide();
                },2000);
            }
        }
        $( ".save" ).click(function() {
            $( ".save" ).attr("disabled", "disabled");
            $(".overlay").show();
            var basePath = "";
            var hospital = $('#hospital_id').val();
            var saveURL = "userSignature";
            var redirectURL = "getLogsForApproval";
            var log_ids=$('#log_ids').val();
            var signature_id={{$signature->signature_id}};
            var role=$('#role').val();
            var rejected=$('#rejected').val();
            var reason=$('#rejected_with_reasons').val();
            var manager_types = $('#manager_types').val();
            var timeZone = new Date();
            var zoneName = Intl.DateTimeFormat().resolvedOptions().timeZone;
            if(typeof zoneName === "undefined")
            {
                timeZone = '';
                zoneName ='';
            }
            $.post( basePath+"/"+saveURL, { signature_id : signature_id, log_ids : log_ids, role : role, rejected : rejected, reason :reason , manager_types:manager_types, timeZone : timeZone , localTimeZone : zoneName}).done(function( data ) {
                /*alert( "Data Loaded: " + data );*/
                if(data == 1){
                    $(".overlay").hide();
                    document.getElementById("alert_signature_error").style.display = "none";
                    document.getElementById("alert_signature").style.display = "block";
                    window.location="#alert_signature";
                    setTimeout(function () {
                        window.location.href= basePath+"/"+redirectURL;
                    }, 2000);
                }else{
                    $( ".save" ).removeAttr("disabled");
                    $(".overlay").hide();
                    document.getElementById("alert_signature_error").style.display = "block";
                    document.getElementById("alert_signature").style.display = "none";
                    window.location="#alert_signature";
                }

            });
        });
        $("#edit_signature").click(function (){
            $("#signature-pad").show();
            $("#signature_view").hide();
        });

        $.ajaxSetup({
             headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
           }
          });
    </script>
@endsection