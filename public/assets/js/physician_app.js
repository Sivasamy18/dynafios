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
    if (signaturePad.isEmpty()) {
        $(".overlay").hide();
        document.getElementById("alert_signature_error_blank").style.display = "block";
        document.getElementById("alert_signature_error").style.display = "none";
        document.getElementById("alert_signature").style.display = "none";
        window.location="#alert_signature_error_blank";
    } else {
        var basePath = "";
        var image=signaturePad.toDataURL();
        var physician_id = document.getElementById("physician_id").value;
        var date_selector = document.getElementById("date_selector").value;
        var c_id = document.getElementById("c_id").value;
        var questions_answer_annually = [];
        var questions_answer_monthly = [];
        var date_range = [];
        if(document.getElementById("questions_answer_annually") != null){
            questions_answer_annually = document.getElementById("questions_answer_annually").value;
        }

        if(document.getElementById("questions_answer_monthly") != null){
            questions_answer_monthly = document.getElementById("questions_answer_monthly").value;
        }

        if(document.getElementById("date_range") != null){
            date_range = document.getElementById("date_range").value;
        }
        
        var saveURL = "physiciansSignature";
        var redirectURL = "signature";
        if(c_id !=0 ){
            saveURL = "physiciansApproveSignature";
            redirectURL = document.getElementById("redirect").value;
        }
        var timeZone = new Date();
        var zoneName = Intl.DateTimeFormat().resolvedOptions().timeZone;
        if(typeof zoneName === "undefined")
        {
            timeZone = '';
            zoneName ='';
        }
        $.post( basePath+"/"+saveURL, { signature : image,date_selector : date_selector ,physician_id : physician_id , contract_id : c_id , timeZone : timeZone , localTimeZone : zoneName, questions_answer_annually : questions_answer_annually, questions_answer_monthly : questions_answer_monthly, date_range : date_range}).done(function( data ) {
            /*alert( "Data Loaded: " + data );*/
            if(data == 1){
                // $(".overlay").hide(); This line is commented to avoid the double submition of the logs by akash.
                document.getElementById("alert_signature_error_blank").style.display = "none";
                document.getElementById("alert_signature_error").style.display = "none";
                document.getElementById("alert_signature").style.display = "block";
                window.location="#alert_signature";
                setTimeout(function () {
                    window.location.href= redirectURL;
                }, 2000);
            }else{
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
