@extends('layouts/_dashboard')
@section('main')
{{ Form::open([ 'class' => 'form form-horizontal' ]) }}
<div class="panel panel-default">
    <div class="panel-heading">
        Signature
    </div>
    <div class="panel-body">
        @if(isset($signature))
            <div id="signature_view" style="text-align: center; position: relative;">
                <img style="width: 50%; height: auto;" src="data:image/png;base64,{{$signature}}"/>

                <a style="float: right; margin-top: -7px; position: absolute; bottom: 0; right: 0;" class="btn btn-primary"
                   href="{{ route('approval.editSignature') }}">
                    Clear
                </a>
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

            <script type="text/javascript" src="{{ asset('assets/js/physician_signature_pad.js') }}"></script>
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
            $("#edit_signature").click(function (){
                $("#signature-pad").show();
                $("#signature_view").hide();
            });
        </script>
@endsection