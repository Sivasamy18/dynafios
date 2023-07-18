@extends('dashboard/_index_landing_page')
<style>
  .landing_page_main .container-fluid .welcomeHeading {
    font-size: 20px;
    font-family: 'open sans';
    font-weight: normal;
  }

  table {
    border-collapse: collapse;
    background: white;
    table-layout: fixed;
    width: 100%;
  }
  th, td {
    padding: 8px 16px !important;
    /* border: 1px solid #ddd; */
    /* width: 160px; */
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-family: 'open sans';
    font-size: 14px;
    color: #221f1f;
  }

  th {
    white-space: normal;
    background: #221f1f;
    color: #fff;
    font-family: 'open sans';
    font-size: 14px;
    font-weight: 600;
  }

  table tbody tr {
    background: #eaeaea;
    border: solid 1px #b8b8b8;
  }

  table tbody tr:nth-child(odd) {
    background: #dfdfdf;
  }

  .pane {
    background: #eee;
  }
  .pane-hScroll {
    overflow: auto;
    width: 100%;
    background: transparent;
  }
  .pane-vScroll {
    overflow-y: auto;
    overflow-x: hidden;
    max-height: 560px;
    background: transparent;
  }

  .pane--table2 {
    width: 100%;
    overflow-x: scroll;
  }
  .pane--table2 th, .pane--table2 td {
    width: auto;
    min-width: 160px;
  }
  .pane--table2 tbody {
    overflow-y: scroll;
    overflow-x: hidden;
    display: block;
    height: 200px;
  }
  .pane--table2 thead {
    display: table-row;
  }

  label {
    margin-top: 10px;
  }

  .odd_contract_class
  {
    background: #dfdfdf !important;
  }
  .even_contract_class
  {
    background: #fdfdfd !important;
  }
  .pagination a {
    width: auto !important;
    height: auto !important;
    margin: 0 6px !important;
  }

  .pagination span {
    margin: 0 6px;
    -webkit-box-shadow: -3px 3px 0 0 #c4c4c4;
    -moz-box-shadow: -3px 3px 0 0 #c4c4c4;
    -ms-box-shadow: -3px 3px 0 0 #c4c4c4;
    -o-box-shadow: -3px 3px 0 0 #c4c4c4;
    box-shadow: -3px 3px 0 0 #c4c4c4;
  }
  .pagination>li>a:hover{
    color: #fff !important;
  }

  .approved-text{
    color : #f68a1f;
  }

  .rejected-text{
        color : red;
  }
</style>
@section('links')
<div id="form_replace" class="approvalDashboard">
  {{ Form::open([ 'class' => 'form form-horizontal form-generate-report' ]) }}
  <div class="appDashboardFilters">
    <div class="col-md-6">
      <div class="form-group col-xs-12">
        <label class="col-xs-3 control-label">Organization: </label>
        <div class="col-md-9 col-sm-9 col-xs-9">
            {{ Form::select('hospital', $hospitals, Request::old('hospital',$hospital), [ 'id'=>'hospital','class' => 'form-control' ]) }}
        </div>
      </div>

      <div class="form-group col-xs-12">
        <label class="col-xs-3 control-label">Practice: </label>
        <div class="col-md-9 col-sm-9 col-xs-9">
          {{ Form::select('practice', $practices, Request::old('practice',$practice), ['id'=>'practice', 'class' => 'form-control' ]) }}
        </div>
      </div>

      <div class="form-group  col-xs-12">
        <label class="col-xs-3 control-label">Payment Type: </label>
        <div class="col-md-9 col-sm-9 col-xs-9">
          {{ Form::select('payment_types', $payment_types, Request::old('payment_type',$payment_type), ['id'=>'payment_type', 'class' => 'form-control' ]) }}
        </div>
      </div>

      <div class="form-group  col-xs-12">
        <label class="col-xs-3 control-label">Status: </label>
        <div class="col-md-9 col-sm-9 col-xs-9">
          <select class="form-control" id="status">
            <option value="0">All</option>
            <option value="1">Pending</option>
            <option value="2">Approved</option>
            <option value="3">Rejected</option>

          </select>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="form-group  col-xs-12">
        <label class="col-xs-3 control-label">Agreement: </label>
        <div class="col-md-9 col-sm-9 col-xs-9">
          {{ Form::select('agreement', $agreements, Request::old('agreement', $agreement), ['id'=> 'agreement', 'class' => 'form-control' ]) }}
        </div>
      </div>

      <div class="form-group  col-xs-12">
        <label class="col-xs-3 control-label">Physician: </label>
        <div class="col-md-9 col-sm-9 col-xs-9">
          {{ Form::select('physician', $physicians, Request::old('physician',$physician), [ 'id'=> 'physician','class' => 'form-control' ]) }}
        </div>
      </div>

      <div class="form-group  col-xs-12">
        <label class="col-xs-3 control-label">Contract Type: </label>
        <div class="col-md-9 col-sm-9 col-xs-9">
          {{ Form::select('contract_types', $contract_types, Request::old('contract_type',$contract_type), ['id'=>'contract_type', 'class' => 'form-control' ]) }}
        </div>
      </div>
    </div>

    <div class="form-group col-xs-offset-4 col-xs-5" style="margin: 0 auto 50px; float: none; clear:both;">
      <label class="col-xs-4 control-label" style="margin-top: 35px;">Time Period:</label>
      <div class="col-md-8 col-sm-8 col-xs-8 paddingZero">
        <div class="col-md-6 col-sm-6 col-xs-6 paddingLeft">
          <!-- <label class="col-xs-12 control-label paddingLeft " style="font-weight: normal; text-align: center;">Start Month</label> -->
          <label class="col-xs-12 control-label paddingLeft " style="font-weight: normal; text-align: center;">Start Period</label>
          {{ Form::select('start_dates', $dates['start_dates'], Request::old('start_date',$start_date), [ 'id'=> 'start_date','class' => 'form-control' ]) }}
        </div>
        <div class="col-md-6 col-sm-6 col-xs-6 paddingRight">
          <!-- <label class="col-xs-12 control-label paddingLeft" style="font-weight: normal; text-align: center;">End Month</label> -->
          <label class="col-xs-12 control-label paddingLeft" style="font-weight: normal; text-align: center;">End Period</label>
          {{ Form::select('end_dates', $dates['end_dates'], Request::old('end_date',$end_date), [ 'id'=> 'end_date','class' => 'form-control' ]) }}
        </div>

      </div>
    </div>
  </div>

  <div class="appDashboardTable">
    <div id="table-wrapper"></div>

    <div class="pane pane--table1">
      <div class="pane-hScroll">

        <table style="width: calc(100% - 17px);">
          <thead>
              @if($column_preferences->date)<th width="120" id="lbl_date">Date</th>@endif
              @if($column_preferences->hospital)<th width="200" id="lbl_hospital">Hospital</th>@endif
              @if($column_preferences->agreement)<th width="200" id="lbl_agreement">Agreement</th>@endif
              @if($column_preferences->contract)<th width="200" id="lbl_contract">Contract Name</th>@endif
              @if($column_preferences->practice)<th width="120" id="lbl_practice">Practice</th>@endif
              @if($column_preferences->physician)<th width="150" id="lbl_physician">Physician</th>@endif
              @if($column_preferences->log)<th width="130" id="lbl_log">Log</th>@endif
              @if($column_preferences->details)<th width="100" id="lbl_details">Details</th>@endif
              @if($column_preferences->duration)<th width="100" id="lbl_duration">Hours/Units Worked</th>@endif
              @if($column_preferences->physician_approval)<th width="120" id="lbl_physician_approval">Physician Approval</th>@endif
              @if($column_preferences->lvl_1)<th width="200" id="lbl_lvl1">Approval Level 1</th>@endif
              @if($column_preferences->lvl_2)<th width="200" id="lbl_lvl2">Approval Level 2</th>@endif
              @if($column_preferences->lvl_3)<th width="200" id="lbl_lvl3">Approval Level 3</th>@endif
              @if($column_preferences->lvl_4)<th width="200" id="lbl_lvl4">Approval Level 4</th>@endif
              @if($column_preferences->lvl_5)<th width="200" id="lbl_lvl5">Approval Level 5</th>@endif
              @if($column_preferences->lvl_6)<th width="200" id="lbl_lvl6">Approval Level 6</th>@endif
              <!--<th width="100">Approve</th>-->
              <!--<th width="100">Reject</th>-->
          </thead>
        </table>

        <div class="pane-vScroll">
          <table>
            <tbody>
              <?php $prev_contract_id=0;
              $contract_change_flag=0;?>
            @foreach ($items as $log)
                <?php
                if($prev_contract_id!=$log['contract_id'])
                {
                  $prev_contract_id=$log['contract_id'];
                  if($contract_change_flag==0)
                  {
                    $contract_change_flag=1;
                  }
                  else
                  {
                    $contract_change_flag=0;
                  }
                }
                ?>
                @if($contract_change_flag==1)
                <tr class="odd_contract_class">
                @else
                <tr class="even_contract_class">
                @endif
                @if($column_preferences->date)<td width="120" id="date" title="{{ format_date($log['log_date']) }}">{{ format_date($log['log_date']) }}</td>@endif
                @if($column_preferences->hospital)<td width="200" id="hospital" title="{{ $log['hospital_name'] }}">{{ $log['hospital_name'] }}</td>@endif
                @if($column_preferences->agreement)<td width="200" id="agreement" title="{{ $log['agreement_name'] }}">{{ $log['agreement_name'] }}</td>@endif
                @if($column_preferences->contract)<td width="200" id="contract" title="{{ $log['contract_name'] }}">{{ $log['contract_name'] }}</td>@endif
                @if($column_preferences->practice)<td width="120" id="practice" title="{{ $log['practice_name'] }}">{{ $log['practice_name'] }}</td>@endif
                @if($column_preferences->physician)<td width="150" id="physician" title="{{ $log['physician_name'] }}">{{ $log['physician_name'] }}</td>@endif
                @if($column_preferences->log)<td width="130" id="log" title="{{ $log['action'] }}">{{ $log['action'] }}</td>@endif
                @if($column_preferences->details)<td width="100" id="details" title="{{ $log['log_details'] }}">{{ $log['log_details'] }}</td>@endif
                @if($column_preferences->duration)<td width="100" id="duration" style="text-align: center;" title="{{ $log['duration'] }}">{{ $log['duration'] }}</td>@endif
                @if($column_preferences->physician_approval)<td  width="120" id="physician_approval" class="{{ $log['levels'][0]["status"] === "Approved" ? "approved-text" : "" }}" title="{{ $log['levels'][0]["status"] }}">{{ $log['levels'][0]["status"] }}</td>@endif
                  <?php $ip=0; ?>
                  @foreach($log['levels'] as $levels)
                    @if($ip != 0)
                      <?php $lvl='lvl_'.$ip; ?>
                      @if($column_preferences->$lvl)
                      <td width="200" id="lvl_{{$ip}}" class="text-center">
                        <div class="{{ $levels["status"] === "Approved" ? "approved-text" : ($levels["status"] === "Rejected" ? "rejected-text" : "") }}" title='{{ $levels["name"] }}'>{{ $levels["name"] }}</div>
                        <div class="{{ $levels["status"] === "Approved" ? "approved-text" : ($levels["status"] === "Rejected" ? "rejected-text" : "") }}" title='{{ $levels["status"] }}'>{{ $levels["status"] }}</div>
                      </td>
                      @endif
                    @endif
                    <?php $ip++; ?>
                  @endforeach
                </tr>
            @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
    {{$logs->render()}}

    <div class="text-center approvalButtons">
      <ul>
          <li><a href="{{ URL::route('approval.columnPreferencesPaymentStatus') }}">Column Display Preferences</a></li>
          <li><button class="actionBtn" id="export">Export To Excel</button></li>
      </ul>
    </div>
   
  </div>
  
  {{ Form::close() }}
    {{ Form::open(array('url' => 'approvalStatusReport')) }}
    <input type="hidden" name="export_manager_filter" value="{{$manager_filter}}">
    <input type="hidden" name="export_payment_type" value="{{$payment_type}}">
    <input type="hidden" name="export_contract_type" value="{{$contract_type}}">
    <input type="hidden" name="export_hospital" value="{{$hospital}}">
    <input type="hidden" name="export_agreement" value="{{$agreement}}">
    <input type="hidden" name="export_practice" value="{{$practice}}">
    <input type="hidden" name="export_physician" value="{{$physician}}">
    <input type="hidden" name="export_start_date" value="{{$start_date}}">
    <input type="hidden" name="export_end_date" value="{{$end_date}}">
    <input type="hidden" name="export_report_type" value="1">
    <input type="hidden" name="export_status" value="{{$status}}">
    <input type="submit" id="export_submit" value="">
    <input type="hidden" id="current_timestamp" name="current_timestamp" value=" ">
    <input type="hidden" id="current_zoneName" name="current_zoneName" value=" ">
    {{ Form::close() }}


<script>
$('.pane-hScroll').scroll(function() {
$('.pane-vScroll').width($('.pane-hScroll').width() + $('.pane-hScroll').scrollLeft());
});

// Example 2
$('.pane--table2').scroll(function() {
$('.pane--table2 table').width($('.pane--table2').width() + $('.pane--table2').scrollLeft());
});
$('#manager_filter').on('change', function () {
  var redirectURL = "?manager_filter=" + this.value;
  window.location.href = redirectURL;
});
$('#hospital').on('change', function () {
  /*var redirectURL = "?manager_filter=" + $('#manager_filter').val()+"&hospital="+this.value;
   window.location.href = redirectURL;*/
  getLogsForApprovalStatusByAjaxRequest($('#hospital').val(),0,0,0,0,0,'','',0,'');
});

$('#agreement').on('change', function () {
  /*var redirectURL = "?manager_filter=" + $('#manager_filter').val()+"&hospital="+$('#hospital').val()+"&agreement="+this.value;
   window.location.href = redirectURL;*/
  getLogsForApprovalStatusByAjaxRequest($('#hospital').val(),$('#agreement').val(),0,0,0,0,'','',0,'');
});
$('#practice').on('change', function () {
  /*var redirectURL = "?manager_filter=" + $('#manager_filter').val()+"&hospital="+$('#hospital').val()+"&agreement="+$('#agreement').val()+"&practice="+this.value;
   window.location.href = redirectURL;*/
  getLogsForApprovalStatusByAjaxRequest($('#hospital').val(),$('#agreement').val(),$('#practice').val(),0,0,0,'','',0,'');
});
$('#physician').on('change', function () {
  /*var redirectURL = "?manager_filter=" + $('#manager_filter').val()+"&hospital="+$('#hospital').val()+"&agreement="+$('#agreement').val()+"&practice="+$('#practice').val()+"&physician="+this.value;
   window.location.href = redirectURL;*/
  getLogsForApprovalStatusByAjaxRequest($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),0,0,'','',0,'');
});
$('#payment_type').on('change', function () {
  /*var redirectURL = "?manager_filter=" + $('#manager_filter').val()+"&hospital="+$('#hospital').val()+"&agreement="+$('#agreement').val()+"&practice="+$('#practice').val()+"&physician="+$('#physician').val()+"&contract_type="+this.value;
   window.location.href = redirectURL;*/
  getLogsForApprovalStatusByAjaxRequest($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),$('#payment_type').val(),0,'','',0,'');
});
$('#contract_type').on('change', function () {
  /*var redirectURL = "?manager_filter=" + $('#manager_filter').val()+"&hospital="+$('#hospital').val()+"&agreement="+$('#agreement').val()+"&practice="+$('#practice').val()+"&physician="+$('#physician').val()+"&contract_type="+this.value;
   window.location.href = redirectURL;*/
  getLogsForApprovalStatusByAjaxRequest($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),$('#payment_type').val(),$('#contract_type').val(),'','',0,'');
});
$('#start_date').on('change', function () {
  var start = new Date($('#start_date').val());
  var end = new Date($('#end_date').val());
  if(start.getTime() < end.getTime()) {
    /*var redirectURL = "?manager_filter=" + $('#manager_filter').val() + "&hospital=" + $('#hospital').val() + "&agreement=" + $('#agreement').val() + "&practice=" + $('#practice').val() + "&physician=" + $('#physician').val() + "&contract_type=" + $('#contract_type').val()+"&start_date="+$('#start_date').val()+"&end_date="+$('#end_date').val();
     window.location.href = redirectURL;*/
    getLogsForApprovalStatusByAjaxRequest($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),$('#payment_type').val(),$('#contract_type').val(),$('#start_date').val(),$('#end_date').val(),$('#status').val(),'');
  }
});
$('#end_date').on('change', function () {
  var start = new Date($('#start_date').val());
  var end = new Date($('#end_date').val());
  if(start.getTime() < end.getTime()) {
    /*var redirectURL = "?manager_filter=" + $('#manager_filter').val() + "&hospital=" + $('#hospital').val() + "&agreement=" + $('#agreement').val() + "&practice=" + $('#practice').val() + "&physician=" + $('#physician').val() + "&contract_type=" + $('#contract_type').val()+"&start_date="+$('#start_date').val()+"&end_date="+$('#end_date').val();
     window.location.href = redirectURL;*/
    getLogsForApprovalStatusByAjaxRequest($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),$('#payment_type').val(),$('#contract_type').val(),$('#start_date').val(),$('#end_date').val(),$('#status').val(),'');
  }
});
$('#status').on('change', function () {
  /*var redirectURL = "?manager_filter=" + $('#manager_filter').val()+"&hospital="+$('#hospital').val()+"&agreement="+$('#agreement').val()+"&practice="+$('#practice').val()+"&physician="+$('#physician').val()+"&contract_type="+$('#contract_type').val()+"&status="+this.value;
  window.location.href = redirectURL;*/
  getLogsForApprovalStatusByAjaxRequest($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),$('#payment_type').val(),$('#contract_type').val(),'','',$('#status').val(),'');
});

$(".pagination li a").click(function(ev) {
  ev.preventDefault();
  var link = $(this). attr("href");
  var split_link = link.split("=");
  getLogsForApprovalStatusByAjaxRequest($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),$('#payment_type').val(),$('#contract_type').val(),$('#start_date').val(),$('#end_date').val(),$('#status').val(),split_link[1]);
});
$('#export').on('click', function (e) {
    e.preventDefault();
    $( "#export_submit" ).trigger( "click" );
  $('.overlay').show();
});
// function to call log Details In Index page  by ajax request - starts
function getLogsForApprovalStatusByAjaxRequest(hospital_id,agreement_id,practice_id,physician_id,payment_type_id,contract_type_id,startDate,endDate,status,page){
  $('.overlay').show();
  $('#form_replace').html();
  $.ajax({
    url:'',
    type:'get',
    data:{
      'hospital':hospital_id,
      'agreement':agreement_id,
      'practice':practice_id,
      'physician':physician_id,
      'payment_type':payment_type_id,
      'contract_type':contract_type_id,
      'start_date':startDate,
      'end_date':endDate,
      'status':status,
      'page': page
    },
    success:function(response){
      // console.log("Response From Approval Index Controller With Hospital,Agreement ID,Practice ID,Physician ID,Contract Type:",response);
      /*var parsed = $.parseHTML(response);
       result = $(parsed).find("tbody tr");
       console.log("index result:",result);
       if(result.length == 0){
       $('#indexDataToBeDisplayed table tbody').html('<tr class="odd_contract_class"><td class="text-center">No Data Available</td></tr>');
       }else{
       $('#indexDataToBeDisplayed').html(response);
       }*/
      $('#form_replace').html(response);
    },
    complete:function(){
      $('.overlay').hide();
    }
  });
}
</script>
    <script type="text/javascript">
        $(function () {
          $('#status').val({{$status}});
            var report_id = "{{ $report_id }}";
        
            @isset($report_id)
                Dashboard.downloadUrl("{{ route('approval.report', $report_id) }}");
                @endisset
            $("#export_submit").hide();
        });
        @if($start_date == '')
          $(function () {
          $("#start_date").val($("#start_date option:first").val());
          $("#end_date option:last").attr("selected", "selected");
        });
      @endif
    </script>
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
         // $(".form-generate-report").submit(function(){

            var timeZone = formatAMPM(new Date());
            var zoneName = Intl.DateTimeFormat().resolvedOptions().timeZone;
            if(typeof zoneName === "undefined")
            {
                timeZone = '';
                zoneName ='';
            }
            $("#current_timestamp").val(timeZone);
            $("#current_zoneName").val(zoneName);
         // });

      });

  </script>
</div>
@endsection



