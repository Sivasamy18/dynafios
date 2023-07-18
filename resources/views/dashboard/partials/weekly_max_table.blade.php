
<div class="clearfix"></div>
@if (count($items) > 0)
    <table class="table table-striped table-hover hospitals-table">
        <thead>
        <tr>
            <th>Physician Name</th>
            <th>Agreement Name</th>
            <th>Contract Type</th>
            <th>Contract Name</th>
            <th>Hospital Name</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody data-link="row" class="rowlink">
        @foreach ($items as $contract)
            <tr>
                <td style="padding-left: 17px;">{{ $contract->physician_name }}</td>
                <td style="padding-left: 17px;">{{ $contract->agreement_name }}</td>
                <td style="padding-left: 17px;">{{ $contract->contract_type_name }}</td>
                <td style="padding-left: 17px;">{{ $contract->contract_name }}</td>
                <td style="padding-left: 17px;">{{ $contract->hospital_name }}</td>
                <td class="text-center rowlink-skip" onClick="editWeeklyMax('{{$contract->contract_id}}','{{$contract->physician_name}}', '{{$contract->agreement_name}}',
                                                    '{{$contract->contract_type_name}}','{{$contract->contract_name}}', '{{$contract->hospital_name}}')">
                    <div class="btn-group btn-group-xs">
                        <a class="btn btn-default btn-edit">
                            <i class="fa fa-edit fa-fw"></i>
                        </a>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    <div class="panel panel-default panel-filtered">
        <div class="panel-body">
            There are no contracts to display at this time.
        </div>
    </div>
@endif

{{--  Below Modal for Show details about the contract and allow user  to update the weekly max hours. --}}
<div id="modal-confirm-edit" class="modal fade">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Update Weekly Max Hours</h4>
            </div>
            <div class="modal-body">

                <div class="alert alert-success" style="display: none"></div>

                <div class="row">
                    <div class="col-lg-12">
                        <table id="rehab-contract">
                            <thead>
                            <tr>
                                <th>Physician Name</th>
                                <th>Agreement Name</th>
                                <th>Contract Type</th>
                                <th>Contract Name</th>
                                <th>Hospital Name</th>
                            </tr>
                            </thead>
                            <tbody >
                            <tr>
                                <td id="physician"></td>
                                <td id="agreement"></td>
                                <td id="contract-type"></td>
                                <td id="contract-name"></td>
                                <td id="hospital"></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <form style="margin-top: 20px" method="post">
                        @csrf
                            <input type="hidden" id="contract_id">
                            <div class="form-group row">
                                <label for="months" class="col-sm-4 col-form-label">Select Month</label>
                                <div class="col-sm-4">
                                    <select class="form-control" id="months"></select>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="weekly-max-hours" class="col-sm-4 col-form-label">Weekly Max Hours</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control" id="weekly-max-hours" placeholder="0.00">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <p><strong style="color: red">Warning!</strong><br>
                    This action will add/update the weekly max hours for the given month.
                </p>

            </div>
            <div class="modal-footer">
                <button type="button" data-dismiss="modal">Cancel</button>
                <button type="button" id="save-max-hour" onclick="saveMaxHour()">Save</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $('.table').DataTable({
            "order": [[ 1, "asc" ]]
        });
    });

    function saveMaxHour(){
        $(".overlay").show();

        var contract_id = $('#contract_id').val();
        var selected_date = $('#months').val();
        var max_hours = $('#weekly-max-hours').val();

        // console.log(contract_id,selected_date);

        $.ajax({
            type:"post",
            dataType: "json",
            data: {contract_id: contract_id, selected_date:selected_date, max_hours:max_hours},
            url:'/postWeeklyMaxForSelectedPeriod',
            success:function(response){
                if(response && Object.keys(response).length != 0){
                    $(".alert-success").append(response.message);
                    $(".alert-success").show();
                    $('#weekly-max-hours').val(parseFloat(response.max_hours_per_week).toFixed(2));
                    setTimeout(function() {
                        $('#modal-confirm-edit').modal('hide');
                        $(".alert-success").hide();
                    }, 2000);
                } else {
                    $('#weekly-max-hours').val('0.00');
                }
                $(".overlay").hide();
            }
        });

    }

    $('#months').change(function(){

        var contract_id = $('#contract_id').val();
        var selected_date = $('#months').val();
        // console.log(contract_id,selected_date);

        $.ajax({
            type:"get",
            url:'/getWeeklyMaxForSelectedPeriod/' + contract_id + '/' +selected_date,
            success:function(response){
                if(response && Object.keys(response).length != 0){
                    $('#weekly-max-hours').val(parseFloat(response.max_hours_per_week).toFixed(2));
                } else {
                    $('#weekly-max-hours').val('0.00');
                }
            }
        });

    });

    function editWeeklyMax(contract_id, physician_name, agreement_name, contract_type_name, contract_name, hospital_name){
debugger;
        document.getElementById('contract_id').value = contract_id;
        document.getElementById('physician').innerHTML  = physician_name;
        document.getElementById('agreement').innerHTML  = agreement_name;
        document.getElementById('contract-type').innerHTML  = contract_type_name;
        document.getElementById('contract-name').innerHTML  = contract_name;
        document.getElementById('hospital').innerHTML  = hospital_name;
        $('#months').find('option').remove();

        var today = new Date();
        // var current_month_start_date = new Date(today.getFullYear(), today.getMonth(), 1); // This should be used in production.
        var current_month_start_date = new Date(today.getFullYear(), today.getMonth() - 3, 1); //This should be used in testing.

        for(let i=1; i<=3; i++){
            current_month_start_date.setMonth(current_month_start_date.getMonth() + 1);
            var newDate = formatDate(current_month_start_date);

            var $monthsDropdown = $("#months");
            $monthsDropdown.append($("<option />").val(newDate).text(newDate));
        }
        $('#months').change();

        Dashboard.confirm({
            button: '.btn-edit',
            dialog: '#modal-confirm-edit',
            dialogButton: '#modal-confirm-edit .btn-primary'
        });
    }

    /*
    Function for formatting date to Y-m-d format
     */
    function formatDate(date) {
        var d = new Date(date),
            month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();

        if (month.length < 2)
            month = '0' + month;
        if (day.length < 2)
            day = '0' + day;

        return [year, month, day].join('-');
    }

    /*
    Function for restricting the value to only two digit after decimal point.
     */
    $(function () {
        $('#weekly-max-hours').keypress(function (event) {
            var $this = $(this);
            if ((event.which != 46 || $this.val().indexOf('.') != -1) && ((event.which <48 || event.which > 57) && (event.which != 0 && event.which != 8))) {
                event.preventDefault();
            }
            var text = $(this).val();
            if (text.length === 18) {
                $(this).val(text + ".")
            }
            if ((event.which == 46) && (text.indexOf('.') == -1)) {
                setTimeout(function () {
                    if ($this.val().substring($this.val().indexOf('.')).length > 3) {
                        $this.val($this.val().substring(0, $this.val().indexOf('.') + 3));
                    }
                }, 1);
            }
            if ((text.indexOf('.') == 18 && text.substring(text.indexOf('.')).length > 2)) {
                event.preventDefault();
            }
            if (((text.indexOf('.') != -1) && (text.substring(text.indexOf('.')).length > 2) && (event.which != 0 && event.which != 8) && ($(this)[0].selectionStart >= text.length - 2))) {
                event.preventDefault();
            }
        });
    });
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
</script>

<style type="text/css">
    .dataTables_wrapper {
        margin-top: 20px;
    }
    #rehab-contract {
        font-family: Arial, Helvetica, sans-serif;
        border-collapse: collapse;
        width: 100%;
    }
    #rehab-contract thead, #rehab-contract th{
        border: 1px solid #ddd;
        padding: 8px;
        background: black;
        color: white;
    }
    #rehab-contract tbody, #rehab-contract tr, #rehab-contract td{
        border: 1px solid #ddd;
        padding: 8px;
    }
</style>