<div class="form-wrapper" style="position: relative;">
    {{ Form::open([ 'class' => 'form form-horizontal form-generate-report' ]) }}
    <div class="panel panel-default">
        <div class="panel-heading">{{ $form_title }}</div>
        <div class="panel-body">
           

        </div>
        <div class="panel-footer clearfix">
            <button class="btn btn-primary btn-sm btn-submit">Submit</button>
        </div>
    </div>
    <input type="hidden" id="current_timestamp" name="current_timestamp" value=" ">
    <input type="hidden" id="current_zoneName" name="current_zoneName" value=" ">
    {{ Form::close() }}
</div>
<script>
    
$(document).ready(function () {
    function formatAMPM(date) {
                    var month = date.getMonth()+1;
                    var day = date.getDate();
                    var year = date.getFullYear();
                    var hours = date.getHours();
                    var minutes = date.getMinutes();
                    var ampm = hours >= 12 ? 'PM' : 'AM';
                    month = month > 9 ? month : '0'+month;
                    day = day > 9 ? day : '0'+day;
                    hours = hours % 12;
                    hours = hours ? hours : 12; // the hour '0' should be '12'
                    minutes = minutes < 10 ? '0'+minutes : minutes;
                    var strTime = month+'/'+day+'/'+year+' '+hours + ':' + minutes + ' ' + ampm;
                    return strTime;
    }
    $(".form-generate-report").submit(function(){

       var timeZone = formatAMPM(new Date());
       var zoneName = Intl.DateTimeFormat().resolvedOptions().timeZone;

       if(typeof zoneName === "undefined")
        {
            timeZone = '';
            zoneName ='';
        }
       $("#current_timestamp").val(timeZone);
       $("#current_zoneName").val(zoneName);
});

});

</script>
