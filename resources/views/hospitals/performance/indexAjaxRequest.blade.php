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
                        {{ Form::select('practice', $practices, Request::old('practice',$practice), ['id'=>'practice', 'class' => 'form-control','multiple'=>true ]) }}
                        <input type="checkbox" class= "selectAll" id="pracChk" name="SelectAll" onclick="selectAll('practice','pracChk')" value="All"  />Select All
                    </div>
        </div>

        <div class="form-group  col-xs-12">
            <label class="col-xs-3 control-label">Payment Type: </label>
            <div class="col-md-9 col-sm-9 col-xs-9">
                {{ Form::select('payment_types', $payment_types, Request::old('payment_type',$payment_type), ['id'=>'payment_type', 'class' => 'form-control' ]) }}
            </div>
        </div>
        <div class="form-group  col-xs-12">
            <label class="col-xs-3 control-label">Contract Type: </label>
            <div class="col-md-9 col-sm-9 col-xs-9">
                {{ Form::select('contract_types[]', $contract_types, Request::old('contract_type',$contract_type), ['id'=>'contract_type', 'class' => 'form-control','multiple'=>true ]) }}
                <input type="checkbox" class= "selectAll" id="typeChk" name="SelectAll" onclick="selectAll('contract_type','typeChk')" value="All"  />Select All
            </div>
            <!-- <div>
            <input type="checkbox" id="typeChk" name="SelectAll" onclick="selectAll('contract_type','typeChk')" value="All"  />Select All
            </div> -->
        </div>
       
    </div>

    <div class="col-md-6">
        <div class="form-group  col-xs-12">
            <label class="col-xs-3 control-label">Agreement: </label>
            <div class="col-md-9 col-sm-9 col-xs-9">
                {{ Form::select('agreement[]', $agreements, Request::old('agreement', $agreement), ['id'=> 'agreement', 'class' => 'form-control','multiple'=>true ]) }}
                <input type="checkbox" class= "selectAll" id ="agreementChk" name="SelectAll" value="All" onclick="selectAll('agreement','agreementChk')" />Select All
            </div>
            <!-- <div>
            <input type="checkbox" id ="agreementChk" name="SelectAll" value="All" onclick="selectAll('agreement','agreementChk')" />Select All
            </div> -->
        </div>

        <div class="form-group  col-xs-12">
            <label class="col-xs-3 control-label">Physician: </label>
            <div class="col-md-9 col-sm-9 col-xs-9">
                {{ Form::select('physician[]', $physicians, Request::old('physician',$physician), [ 'id'=> 'physician','class' => 'form-control','multiple'=>true ]) }}
                <input type="checkbox" id="phyChk" class= "selectAll" name="SelectAll" value="All" onclick="selectAll('physician','phyChk')" />Select All
            </div>
            <!-- <div>
            <input type="checkbox" id="phyChk" name="SelectAll" value="All" onclick="selectAll('physician','phyChk')" />Select All
            </div> -->
        </div>

       
        <div class="form-group  col-xs-12">
            <label class="col-xs-3 control-label">Contract Name: </label>
            <div class="col-md-9 col-sm-9 col-xs-9">
                {{ Form::select('contract_names[]', $contract_names, Request::old('contract_name',$contract_name), ['id'=>'contract_name', 'class' => 'form-control','multiple'=>true ]) }}
                <input type="checkbox" id="nameChk" class= "selectAll" name="SelectAll" value="All" onclick="selectAll('contract_name','nameChk')" />Select All
            </div>
            <!-- <div>
            <input type="checkbox" id="nameChk" name="SelectAll" value="All" onclick="selectAll('contract_name','nameChk')" />Select All
            </div> -->
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
            @if(Request::is('getPerformance/reports') || Request::is('getPerformance/reports/*'))
                <div class="panel-footer clearfix">
                    <button class="btn btn-primary btn-sm btn-submit">Submit</button>
                </div>
            @else
            <div style="align-items: center;
                            display: flex;
                            justify-content: center;">
                <a class="performanceButton"  type="button" name="submit"  href="#top" onclick="generatePieChartNew()"> Generate Chart </a>
                <a class="performanceButton"  type="button" name="excelButton" href="{{ URL::route('performance.report') }}">Generate Excel Report</a>
            </div>
                @endif
{{ Form::close() }}
<script>
    $(document).ready(function(){
        $('.overlay').hide();
    });

</script>

<script type="text/javascript" src="{{ asset('assets/js/performanceDashboard.js') }}"></script>

