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
<h2>Set your Approval Dashboard column display preferences below, then click save</h2>
{{ Form::open([ 'class' => 'form form-horizontal form-create-physician' , 'enctype'=> 'multipart/form-data']) }}
<div class="panel panel-default">
  <div class="panel-body">
      <div class="form-group col-xs-6">
          <label class="col-xs-2 control-label">Date:</label>

          <div class="col-xs-5">
              <div id="toggle" class="input-group">
                  <label class="switch">

                      {{ Form::checkbox('date_on_off', 1, Request::old('date_on_off',$column_preferences->date), ['id' => 'date_on_off']) }}
                      <div class="slider round"></div>
                      <div class="text"></div>
                  </label>
              </div>
          </div>
          <div class="col-xs-5"></div>
      </div>

      <div class="form-group col-xs-6">
          <label class="col-xs-2 control-label">Hours/Units Worked:</label>

          <div class="col-xs-5">
              <div id="toggle" class="input-group">
                  <label class="switch">

                      {{ Form::checkbox('duration_on_off', 1, Request::old('duration_on_off',$column_preferences->duration), ['id' => 'duration_on_off']) }}
                      <div class="slider round"></div>
                      <div class="text"></div>
                  </label>
              </div>
          </div>
          <div class="col-xs-5"></div>
      </div>

      <div class="form-group col-xs-6">
          <label class="col-xs-2 control-label">Hospital:</label>

          <div class="col-xs-5">
              <div id="toggle" class="input-group">
                  <label class="switch">

                      {{ Form::checkbox('hospital_on_off', 1, Request::old('hospital_on_off',$column_preferences->hospital), ['id' => 'hospital_on_off']) }}
                      <div class="slider round"></div>
                      <div class="text"></div>
                  </label>
              </div>
          </div>
          <div class="col-xs-5"></div>
      </div>

      <div class="form-group col-xs-6">
          <label class="col-xs-2 control-label">Physician Approval:</label>

          <div class="col-xs-5">
              <div id="toggle" class="input-group">
                  <label class="switch">

                      {{ Form::checkbox('physician_approval_on_off', 1, Request::old('physician_approval_on_off',$column_preferences->physician_approval), ['id' => 'physician_approval_on_off']) }}
                      <div class="slider round"></div>
                      <div class="text"></div>
                  </label>
              </div>
          </div>
          <div class="col-xs-5"></div>
      </div>

      <div class="form-group col-xs-6">
          <label class="col-xs-2 control-label">Agreement:</label>

          <div class="col-xs-5">
              <div id="toggle" class="input-group">
                  <label class="switch">

                      {{ Form::checkbox('agreement_on_off', 1, Request::old('agreement_on_off',$column_preferences->agreement), ['id' => 'agreement_on_off']) }}
                      <div class="slider round"></div>
                      <div class="text"></div>
                  </label>
              </div>
          </div>
          <div class="col-xs-5"></div>
      </div>

      <div class="form-group col-xs-6">
          <label class="col-xs-2 control-label">Approval Level 1:</label>

          <div class="col-xs-5">
              <div id="toggle" class="input-group">
                  <label class="switch">

                      {{ Form::checkbox('lvl_1_on_off', 1, Request::old('lvl_1_on_off',$column_preferences->lvl_1), ['id' => 'lvl_1_on_off']) }}
                      <div class="slider round"></div>
                      <div class="text"></div>
                  </label>
              </div>
          </div>
          <div class="col-xs-5"></div>
      </div>

      <div class="form-group col-xs-6">
          <label class="col-xs-2 control-label">Contract Name:</label>

          <div class="col-xs-5">
              <div id="toggle" class="input-group">
                  <label class="switch">

                      {{ Form::checkbox('contract_on_off', 1, Request::old('contract_on_off',$column_preferences->contract), ['id' => 'contract_on_off']) }}
                      <div class="slider round"></div>
                      <div class="text"></div>
                  </label>
              </div>
          </div>
          <div class="col-xs-5"></div>
      </div>

      <div class="form-group col-xs-6">
          <label class="col-xs-2 control-label">Approval Level 2:</label>

          <div class="col-xs-5">
              <div id="toggle" class="input-group">
                  <label class="switch">

                      {{ Form::checkbox('lvl_2_on_off', 1, Request::old('lvl_2_on_off',$column_preferences->lvl_2), ['id' => 'lvl_2_on_off']) }}
                      <div class="slider round"></div>
                      <div class="text"></div>
                  </label>
              </div>
          </div>
          <div class="col-xs-5"></div>
      </div>

      <div class="form-group col-xs-6">
          <label class="col-xs-2 control-label">Practice:</label>

          <div class="col-xs-5">
              <div id="toggle" class="input-group">
                  <label class="switch">

                      {{ Form::checkbox('practice_on_off', 1, Request::old('practice_on_off',$column_preferences->practice), ['id' => 'practice_on_off']) }}
                      <div class="slider round"></div>
                      <div class="text"></div>
                  </label>
              </div>
          </div>
          <div class="col-xs-5"></div>
      </div>

      <div class="form-group col-xs-6">
          <label class="col-xs-2 control-label">Approval Level 3:</label>

          <div class="col-xs-5">
              <div id="toggle" class="input-group">
                  <label class="switch">

                      {{ Form::checkbox('lvl_3_on_off', 1, Request::old('lvl_3_on_off',$column_preferences->lvl_3), ['id' => 'lvl_3_on_off']) }}
                      <div class="slider round"></div>
                      <div class="text"></div>
                  </label>
              </div>
          </div>
          <div class="col-xs-5"></div>
      </div>

      <div class="form-group col-xs-6">
          <label class="col-xs-2 control-label">Physician:</label>

          <div class="col-xs-5">
              <div id="toggle" class="input-group">
                  <label class="switch">

                      {{ Form::checkbox('physician_on_off', 1, Request::old('physician_on_off',$column_preferences->physician), ['id' => 'physician_on_off']) }}
                      <div class="slider round"></div>
                      <div class="text"></div>
                  </label>
              </div>
          </div>
          <div class="col-xs-5"></div>
      </div>

      <div class="form-group col-xs-6">
          <label class="col-xs-2 control-label">Approval Level 4:</label>

          <div class="col-xs-5">
              <div id="toggle" class="input-group">
                  <label class="switch">

                      {{ Form::checkbox('lvl_4_on_off', 1, Request::old('lvl_4_on_off',$column_preferences->lvl_4), ['id' => 'lvl_4_on_off']) }}
                      <div class="slider round"></div>
                      <div class="text"></div>
                  </label>
              </div>
          </div>
          <div class="col-xs-5"></div>
      </div>

      <div class="form-group col-xs-6">
          <label class="col-xs-2 control-label">Log:</label>

          <div class="col-xs-5">
              <div id="toggle" class="input-group">
                  <label class="switch">

                      {{ Form::checkbox('log_on_off', 1, Request::old('log_on_off',$column_preferences->log), ['id' => 'log_on_off']) }}
                      <div class="slider round"></div>
                      <div class="text"></div>
                  </label>
              </div>
          </div>
          <div class="col-xs-5"></div>
      </div>

      <div class="form-group col-xs-6">
          <label class="col-xs-2 control-label">Approval Level 5:</label>

          <div class="col-xs-5">
              <div id="toggle" class="input-group">
                  <label class="switch">

                      {{ Form::checkbox('lvl_5_on_off', 1, Request::old('lvl_5_on_off',$column_preferences->lvl_5), ['id' => 'lvl_5_on_off']) }}
                      <div class="slider round"></div>
                      <div class="text"></div>
                  </label>
              </div>
          </div>
          <div class="col-xs-5"></div>
      </div>

      <div class="form-group col-xs-6">
          <label class="col-xs-2 control-label">Details:</label>

          <div class="col-xs-5">
              <div id="toggle" class="input-group">
                  <label class="switch">

                      {{ Form::checkbox('details_on_off', 1, Request::old('details_on_off',$column_preferences->details), ['id' => 'details_on_off']) }}
                      <div class="slider round"></div>
                      <div class="text"></div>
                  </label>
              </div>
          </div>
          <div class="col-xs-5"></div>
      </div>

      <div class="form-group col-xs-6">
          <label class="col-xs-2 control-label">Approval Level 6:</label>

          <div class="col-xs-5">
              <div id="toggle" class="input-group">
                  <label class="switch">

                      {{ Form::checkbox('lvl_6_on_off', 1, Request::old('lvl_6_on_off',$column_preferences->lvl_6), ['id' => 'lvl_6_on_off']) }}
                      <div class="slider round"></div>
                      <div class="text"></div>
                  </label>
              </div>
          </div>
          <div class="col-xs-5"></div>
      </div>

      <div class="form-group col-xs-6">
          <label class="col-xs-2 control-label">Calculated payment:</label>

          <div class="col-xs-5">
              <div id="toggle" class="input-group">
                  <label class="switch">

                      {{ Form::checkbox('calculated_payment', 1, Request::old('calculated_payment',$column_preferences->calculated_payment), ['id' => 'calculated_payment']) }}
                      <div class="slider round"></div>
                      <div class="text"></div>
                  </label>
              </div>
          </div>
          <div class="col-xs-5"></div>
      </div>
  </div>
</div>
<div class="text-center approvalButtons">
  <ul>
    <li><button class="actionBtn" type="submit">Save</button></li>
    <li></li>
  </ul>
</div>
    {{ Form::close() }}

@endsection
