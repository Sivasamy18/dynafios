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
            @if ( Request::is('*/spendYTDEffectivenessReport'))

            <div class="form-group">
                <label class="col-xs-2 control-label">Payment Type</label>
                <div class="col-xs-5">
                    {{ Form::select('payment_type', $payment_types, Request::old('payment_type', $payment_types), [ 'id'=>'payment_type', 'class' =>'form-control' ]) }}
                </div>
            </div>

            <div class="form-group">
                <label class="col-xs-2 control-label">Contract Type</label>
                <div class="col-xs-5">
                    {{ Form::select('contract_type', $contract_types, Request::old('contract_type', $contract_types), [ 'id'=>'contract_type', 'class' =>'form-control' ]) }}
                </div>
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
  /*Change of option in region dropdown*/
  $("#region").change(function (){
    var value = $(this).val();

    $.ajax({
      url: '/getRegionFacilitiesContractTypes/' + value,
      dataType: 'json'
    }).done(function (response) {
      $('[name=facility] option').remove();
      $('[name=facility]').append('<option value="0">All</option>');
      $.each(response['facility'], function (index, value) {
        $('[name=facility]').append('<option value="' + value.id + '">' + value.name + '</option>');
      });
      $('[name=payment_type] option').remove();
      $('[name=payment_type]').append('<option value="0">All</option>');
      $.each(response['payment_types'], function (index, value) {
        $('[name=payment_type]').append('<option value="' + value.id + '">' + value.name + '</option>');
      });
      $('[name=contract_type] option').remove();
      $('[name=contract_type]').append('<option value="0">All</option>');
      $.each(response['contract_types'], function (index, value) {
        $('[name=contract_type]').append('<option value="' + value.id + '">' + value.name + '</option>');
      });
    });
  });

  /*Change of option in facility dropdown*/
  $("#hospital").change(function (){
    var region_value = $('#region').val();
    var hospital_value = $(this).val();


    $.ajax({
      url: '/getFacilitiesContractTypes/region/'+ region_value + '/hospital/'+ hospital_value,
      dataType: 'json'
    }).done(function (response) {
      $('[name=payment_type] option').remove();
      $('[name=payment_type]').append('<option value="0">All</option>');
      $.each(response['payment_types'], function (index, value) {
        $('[name=payment_type]').append('<option value="' + value.id + '">' + value.name + '</option>');
      });
      $('[name=contract_type] option').remove();
      $('[name=contract_type]').append('<option value="0">All</option>');
      $.each(response['contract_types'], function (index, value) {
        $('[name=contract_type]').append('<option value="' + value.id + '">' + value.name + '</option>');
      });
    });
  });

  /*Change of option in payment type dropdown*/
  $("#payment_type").change(function (){
    var region_value = $('#region').val();
    var hospital_value = $('#hospital').val();
    var payment_type_value= $(this).val();

    $.ajax({
      url: '/getFacilitiesContractTypes/region/'+ region_value + '/hospital/'+ hospital_value+ '/payment_type/' +payment_type_value,
      dataType: 'json'
    }).done(function (response) {

      $('[name=contract_type] option').remove();
      $('[name=contract_type]').append('<option value="0">All</option>');
      $.each(response['contract_types'], function (index, value) {
        $('[name=contract_type]').append('<option value="' + value.id + '">' + value.name + '</option>');
      });
    });
  });

});
</script>
