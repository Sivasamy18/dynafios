                <div id="contracts">
                    <div class="alert alert-success ajax-success" style="display:none;">
                            <strong>Success! </strong>Data saved successfully and an invoice has been sent to all the delegated recipients!.
                    </div>
                    <div class="alert alert-danger ajax-failed" style="display:none;">

                        <strong>Error! </strong>There is a problem.Please try again after sometime.
                    </div>
                    <div class="alert alert-danger ajax-error" style="display:none;">

                        <strong>Error! </strong>You have already finalized reports for some physicians between this date
                        range.So you can not change data for them
                    </div>
                    <div class="alert alert-danger ajax-validation" style="display:none;">
                        <strong>Error! </strong>These type of invoices will not be available for combined invoice print.
                    </div>
                    <div>
                            <div class="form-group col-xs-12 paddingZero">
                                <div class="">
                                    <label class="control-label">Agreement </label>
                                </div>
                                <div class="col-md-12 col-sm-12 col-xs-12 paddingZero">
                                    {{ Form::select("agreements", $agreements, $selected_agreement_id, ['class' => 'form-control dataFilters','id' => 'agreement_id']) }}
                                </div>
                            </div>

                            <div class="form-group col-xs-12 paddingZero">
                                <div class="">
                                    <label class="control-label">Practice </label>
                                </div>
                                <div class="col-md-12 col-sm-12 col-xs-12 paddingZero">
                                    {{ Form::select("practices", $practice_list, $practice_id, ['class' => 'form-control dataFilters','id' => 'practice_id']) }}
                                </div>
                            </div>

                        <div class="form-group col-xs-12 paddingZero">
                            <div class="">
                                <label class="control-label">Payment Type </label>
                            </div>
                            <div class="col-md-12 col-sm-12 col-xs-12 paddingZero">
                                {{ Form::select("payment_types", $payment_type_list, $payment_type_id, ['class' => 'form-control dataFilters','id' => 'payment_type_id']) }}
                            </div>
                        </div>

                        <div class="form-group col-xs-12 paddingZero">
                            <div class="">
                                <label class="control-label">Contract Type </label>
                            </div>
                            <div class="col-md-12 col-sm-12 col-xs-12 paddingZero">
                                {{ Form::select("contract_types", $contract_type_list, $contract_type_id, ['class' => 'form-control dataFilters','id' => 'contract_type_id']) }}
                            </div>
                        </div>

                            <div class="form-group col-xs-12 paddingZero">
                                <div class="">
                                    <label class="control-label">Physician </label>
                                </div>
                                <div class="col-md-12 col-sm-12 col-xs-12 paddingZero">
                                    {{ Form::select("physicians", $physician_list, $physician_id, ['class' => 'form-control dataFilters','id' => 'physician_id']) }}
                                </div>
                            </div>

                            Date :
                            {{ Form::select("agreement_{$selected_agreement_id}_start_month", $dates_list, $current_month, ['class' => 'form-control dataFilters','id' => 'start_date']) }}
                            <div class="alert alert-danger"
                                 style="display:none;padding:5px;clear:both;margin-top:10px;">
                                <a class="close" data-dismiss="alert">&times;</a>
                                <strong>Error! </strong>Please Select Valid Dates.
                            </div>
                    </div>
                    {!! $table !!}
                </div>
