<div class="form-wrapper" style="position: relative;">
    {{ Form::open([ 'class' => 'form form-horizontal form-generate-report' ]) }}
    <div class="panel panel-default">
        <div class="panel-heading">{{ $form_title }}</div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-xs-2 control-label">Region</label>
                <div class="col-xs-5">
                    {{ Form::select('region', $regions, Request::old('region', $region_id), [ 'id'=>'region','class' =>'form-control' ]) }}
                </div>
            </div>

            <div class="form-group">
                <label class="col-xs-2 control-label">Organization</label>
                <div class="col-xs-5">
                    {{ Form::select('facility', $facilities, Request::old('facility', $facility), [ 'id'=>'hospital', 'class' =>'form-control' ]) }}
                </div>
            </div>
            @if ( Request::is('*/contractsExpiringReport/*'))
            <div class="form-group">
                <label class="col-xs-2 control-label">Expiring In</label>
                <div class="col-xs-5">
                  {{ Form::text('expiring_in',90, ['class' =>'form-control'])}}
                    <div class="help-block col-xs-10" >
                            Enter Expiring in days between 1 to 365 days.
                          </div>
                </div>

            </div>

            @endif
            @if ( Request::is('*/providerProfileReport/*'))
            <div class="form-group">
                <label class="col-xs-2 control-label">Sort By</label>
                <div class="col-xs-3">{{ Form::radio('sortBy','CONTRACT_START_DATE',true)}} <label class="control-label">Contract Start Date</label> </div>
                <div class="col-xs-2">{{ Form::radio('sortBy','FMV_RATE')}} <label class="control-label">FMV Rate</label> </div>
                <div class="col-xs-3">{{ Form::radio('sortBy','HOURS_WORKED')}} <label class="control-label">Hours Worked</label></div>
                <div class="col-xs-2">{{ Form::radio('sortBy','AMOUNT_PAID')}} <label class="control-label">Amount Paid</label> </div>

            </div>
            @endif
			
			      <div class="form-group" style="border-top: 1px solid #ddd; padding-top: 10px;">
                {!! $report_form !!}
            </div>
        </div>
        <div class="panel-footer clearfix">

            <button class="btn btn-primary btn-sm btn-submit">Submit</button>
        </div>
    </div>
    <input type="hidden" id="current_timestamp" name="current_timestamp" value=" ">
    <input type="hidden" id="current_zoneName" name="current_zoneName" value=" ">
    {{ Form::close() }}
</div>
<style>
.form .panel
{
  border:0px;
}
.form .panel-heading
{
  border-radius:0px;
}
</style>
<script>
$(document).ready(function (){
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
  $("#region").change(function (){
    var value = $(this).val();

    $.ajax({
      /*url: '{{ URL::current() }}?contract_type=' + value,*/
      url: '/getRegionFacilities/' + value,
      dataType: 'json'
    }).done(function (response) {
      $('[name=facility] option').remove();
      $('[name=facility]').append('<option value="0">All</option>');
      $.each(response, function (index, value) {
        $('[name=facility]').append('<option value="' + value.id + '">' + value.name + '</option>');
      });
	  Dashboard.updateReportsForm();
    });
  });

});
</script>
