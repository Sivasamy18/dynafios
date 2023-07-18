var wrapper = document.getElementById("signature-pad"),
    clearButton = wrapper.querySelector("[data-action=clear]"),
    saveButton = wrapper.querySelector("[data-action=save]"),
    canvas = wrapper.querySelector("canvas"),
    signaturePad;

// Adjust canvas coordinate space taking into account pixel ratio,
// to make it look crisp on mobile devices.
// This also causes canvas to be cleared.
function resizeCanvas() {
    // When zoomed out to less than 100%, for some very strange reason,
    // some browsers report devicePixelRatio as less than 1
    // and only part of the canvas is cleared then.
    var ratio =  Math.max(window.devicePixelRatio || 1, 1);
    canvas.width = canvas.offsetWidth * ratio;
    canvas.height = canvas.offsetHeight * ratio;
    canvas.getContext("2d").scale(ratio, ratio);
}

window.onresize = resizeCanvas;
resizeCanvas();

signaturePad = new SignaturePad(canvas);

clearButton.addEventListener("click", function (event) {
    signaturePad.clear();
});

saveButton.addEventListener("click", function (event) {
    $(".overlay").show();
    $( ".save" ).attr("disabled", "disabled");
    if (signaturePad.isEmpty()) {
        $( ".save" ).removeAttr("disabled");
        $(".overlay").hide();
        document.getElementById("alert_signature_error_blank").style.display = "block";
        document.getElementById("alert_signature_error").style.display = "none";
        document.getElementById("alert_signature").style.display = "none";
        window.location="#alert_signature_error_blank";
    } else {
        var basePath = "";
        // var hospital = $('#hospital_id').val();
        var image=signaturePad.toDataURL();
        var saveURL = "userSignature";
        if($('#log_ids').length && $('#rejected').length && $('#rejected_with_reasons').length) {
            var redirectURL = "getLogsForApproval";
            var log_ids = $('#log_ids').val();
            // var role = $('#role').val();
            var rejected = $('#rejected').val();
            var reason = $('#rejected_with_reasons').val();
            var manager_types = $('#manager_types').val();
        }else{
            var log_ids = 0;
            // var role = 0;
            var rejected = 0;
            var reason = 0;
            var manager_types = 0;
            var redirectURL = "veiwUserSignature";
        }

        var timeZone = new Date();
        var zoneName = Intl.DateTimeFormat().resolvedOptions().timeZone;
        if(typeof zoneName === "undefined")
        {
            timeZone = '';
            zoneName ='';
        }

        $.post( basePath+"/"+saveURL, { signature : image, log_ids : log_ids, rejected : rejected, reason:reason , manager_types:manager_types, timeZone : timeZone , localTimeZone : zoneName}).done(function( data ) {
            /*alert( "Data Loaded: " + data );*/
            if(data == 1){
                $(".overlay").hide();
                document.getElementById("alert_signature_error_blank").style.display = "none";
                document.getElementById("alert_signature_error").style.display = "none";
                document.getElementById("alert_signature").style.display = "block";
                window.location="#alert_signature";
                setTimeout(function () {
                    window.location.href= basePath+"/"+redirectURL;
                }, 2000);
            }else{
                $( ".save" ).removeAttr("disabled");
                $(".overlay").hide();
                document.getElementById("alert_signature_error_blank").style.display = "none";
                document.getElementById("alert_signature_error").style.display = "block";
                document.getElementById("alert_signature").style.display = "none";
                window.location="#alert_signature";
            }

        });
        /*$.post( "save.php",{base64:image } function( data ) {
         //$( ".result" ).html( data );
         alert(data);
         });*/
        // window.open(signaturePad.toDataURL());
    }
});

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
