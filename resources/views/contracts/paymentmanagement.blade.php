@extends('layouts/_physician', [ 'tab' => 2 ])
@section('content')
  {{ Form::open([ 'class' => 'form form-horizontal' ]) }}
  {{ Form::hidden('contract_id', $contract->id) }}
  {{ Form::hidden('split_payment_count',Request::old('split_payment_count', $split_payment_count),['id' => 'split_payment_count']) }}

  <div class="panel panel-default">
    <div class="panel-heading">
      Payment Management
      <a style="float: right; margin-top: -7px" class="btn btn-primary"
         href="{{ route('contracts.edit', [$contract->id, $practice->id, $physician->id]) }}">
        Back
      </a>
    </div>

    <div class="panel-body" id="notes">
        <?php
        $i = 1;
        ?>
      @foreach($return_data as $return_data)
        <div id="payment_box_{{$i}}">
          @if($i != 1)
            <label class="col-xs-12 control-label"
                   style="border-top: 2px #d6d6d6 solid; margin-top: 10px; margin-bottom: 15px"></label>
          @endif

          <div class="form-group split_payment-note">
            <label class="col-xs-2 control-label">Payment %</label>
            <div class="col-xs-5">
              {{ Form::text('payment_'.$i, Request::old('payment', $return_data['payment_percentage']), [ 'class' => 'form-control payment', 'id' => 'payment_'.$i, 'maxlength' => 5]) }}
            </div>
            <div class="col-xs-2">
              <button id="btn_remove_split_payment_{{$i}}" value='{{$i}}' onClick="removepaymentnote(this);"
                      class="btn btn-primary btn-submit remove-split-payment" type="button"> -
              </button>
            </div>
          </div>

          <div class="form-group split_payment-note">
            <label class="col-xs-2 control-label">Payment Note 1</label>
            <div class="col-xs-5">
              {{ Form::textarea('payment_note_'.$i.'_1', Request::old('payment_note_1', $return_data['payment_note_1']), [ 'class' => 'form-control','id' => 'payment_note_'.$i.'_1','maxlength' => 50, 'rows' => 2, 'cols' => 54, 'style' => 'resize:none' ]) }}
            </div>
          </div>

          <div class="form-group split_payment-note">
            <label class="col-xs-2 control-label">Payment Note 2</label>
            <div class="col-xs-5">
              {{ Form::textarea('payment_note_'.$i.'_2', Request::old('payment_note_2', $return_data['payment_note_2']), [ 'class' => 'form-control','id' => 'payment_note_'.$i.'_2','maxlength' => 50, 'rows' => 2, 'cols' => 54, 'style' => 'resize:none' ]) }}
            </div>
          </div>

          <div class="form-group split_payment-note">
            <label class="col-xs-2 control-label">Payment Note 3</label>
            <div class="col-xs-5">
              {{ Form::textarea('payment_note_'.$i.'_3', Request::old('payment_note_3', $return_data['payment_note_3']), [ 'class' => 'form-control','id' => 'payment_note_'.$i.'_3','maxlength' => 50, 'rows' => 2, 'cols' => 54, 'style' => 'resize:none' ]) }}
            </div>
          </div>

          <div class="form-group split_payment-note">
            <label class="col-xs-2 control-label">Payment Note 4</label>
            <div class="col-xs-5">
              {{ Form::textarea('payment_note_'.$i.'_4', Request::old('payment_note_4', $return_data['payment_note_4']), [ 'class' => 'form-control','id' => 'payment_note_'.$i.'_4','maxlength' => 50, 'rows' => 2, 'cols' => 54, 'style' => 'resize:none' ]) }}
            </div>
          </div>
        </div>

                <?php
                $i++;
                ?>

      @endforeach

      @if($split_payment_count == 0)
        <div id="payment_box_1">
          <div class="form-group split_payment-note">
            <label class="col-xs-2 control-label">Payment %</label>
            <div class="col-xs-5">
              {{ Form::text('payment_1', Request::old('payment'), [ 'class' => 'form-control payment', 'id' => 'payment_1', 'maxlength' => 5, 'max' => 100]) }}
            </div>
            <div class="col-xs-2">
              <button id="btn_remove_split_payment_1" value='1' onClick="removepaymentnote(this);"
                      class="btn btn-primary btn-submit remove-split-payment" type="button"> -
              </button>
            </div>
          </div>

          <div class="form-group split_payment-note">
            <label class="col-xs-2 control-label">Payment Note 1</label>
            <div class="col-xs-5">
              {{ Form::textarea('payment_note_1_1', Request::old('payment_note_1'), [ 'class' => 'form-control','id' => 'payment_note_1_1','maxlength' => 50, 'rows' => 2, 'cols' => 54, 'style' => 'resize:none' ]) }}
            </div>
          </div>

          <div class="form-group split_payment-note">
            <label class="col-xs-2 control-label">Payment Note 2</label>
            <div class="col-xs-5">
              {{ Form::textarea('payment_note_1_2', Request::old('payment_note_2'), [ 'class' => 'form-control','id' => 'payment_note_1_2','maxlength' => 50, 'rows' => 2, 'cols' => 54, 'style' => 'resize:none' ]) }}
            </div>
          </div>

          <div class="form-group split_payment-note">
            <label class="col-xs-2 control-label">Payment Note 3</label>
            <div class="col-xs-5">
              {{ Form::textarea('payment_note_1_3', Request::old('payment_note_3'), [ 'class' => 'form-control','id' => 'payment_note_1_3','maxlength' => 50, 'rows' => 2, 'cols' => 54, 'style' => 'resize:none' ]) }}
            </div>
          </div>

          <div class="form-group split_payment-note">
            <label class="col-xs-2 control-label">Payment Note 4</label>
            <div class="col-xs-5">
              {{ Form::textarea('payment_note_1_4', Request::old('payment_note_4'), [ 'class' => 'form-control','id' => 'payment_note_1_4','maxlength' => 50, 'rows' => 2, 'cols' => 54, 'style' => 'resize:none' ]) }}
            </div>
          </div>
        </div>
      @endif
    </div>

    <div class="panel-body">
      <button class="btn btn-primary btn-submit add-split-payment" type="button">Add Split Payments</button>
    </div>

    <div class="panel-footer clearfix">
      <button class="btn btn-primary btn-sm btn-submit" id="btnsubmit" type="submit">Submit</button>
    </div>
  </div>
  {{ Form::close() }}
@endsection
@section('scripts')
  <script type="text/javascript">
      $(function () {
          if (parseInt($('#split_payment_count').val()) == 0) {
              $('#split_payment_count').val(1);
          }
          $(".add-split-payment").on('click', function (event) {
              event.preventDefault();

              var note_number = parseInt($('#split_payment_count').val()) + 1;
              var data = '<div id="payment_box_' + note_number + '">';

              if (note_number != 1) {
                  data += '<div style="border-top: 2px #d6d6d6 solid; margin-top: 21px; margin-bottom: 21px"></div>';
              }
              data += '<div class="form-group split_payment-note">'
                  + '<label class="col-xs-2 control-label">Payment %</label>'
                  + '<div class="col-xs-5">'
                  + '<input class="form-control payment" id="payment_' + note_number + '" name="payment_' + note_number + '" type="text" maxlength="5" max="100">'
                  + '</div>'
                  + '<div class="col-xs-2"><button id="btn_remove_split_payment_' + note_number + '" value="' + note_number + '" onClick="removepaymentnote(this);" class="btn btn-primary btn-submit remove-split-payment" type="button"> - </button></div>'
                  + '</div>'
                  + '<div class="form-group">'
                  + '<label class="col-xs-2 control-label">Payment Note 1</label>'
                  + '<div class="col-xs-5">'
                  + '<textarea class="form-control" id="payment_note_' + note_number + '_1" maxlength="50" rows="2" cols="54" style="resize:none" name="payment_note_' + note_number + '_1" spellcheck="false"></textarea>'
                  + '</div>'
                  + '</div>'
                  + '<div class="form-group">'
                  + '<label class="col-xs-2 control-label">Payment Note 2</label>'
                  + '<div class="col-xs-5">'
                  + '<textarea class="form-control" id="payment_note_' + note_number + '_2" maxlength="50" rows="2" cols="54" style="resize:none" name="payment_note_' + note_number + '_2" spellcheck="false"></textarea>'
                  + '</div>'
                  + '</div>'
                  + '<div class="form-group">'
                  + '<label class="col-xs-2 control-label">Payment Note 3</label>'
                  + '<div class="col-xs-5">'
                  + '<textarea class="form-control" id="payment_note_' + note_number + '_3" maxlength="50" rows="2" cols="54" style="resize:none" name="payment_note_' + note_number + '_3" spellcheck="false"></textarea>'
                  + '</div>'
                  + '</div>'
                  + '<div class="form-group">'
                  + '<label class="col-xs-2 control-label">Payment Note 4</label>'
                  + '<div class="col-xs-5">'
                  + '<textarea class="form-control" id="payment_note_' + note_number + '_4" maxlength="50" rows="2" cols="54" style="resize:none" name="payment_note_' + note_number + '_4" spellcheck="false"></textarea>'
                  + '</div>'
                  + '</div></div>';

              $('#notes').append(data);
              $('#split_payment_count').val(note_number);

              $('.payment').keypress(function (e) {
                  var charCode = (e.which) ? e.which : event.keyCode

                  if (String.fromCharCode(charCode).match(/[^0-9\.]/g))
                      return false;
              });
          });

          $("#btnsubmit").on('click', function (event) {
              var payment_count = $('#split_payment_count').val();
              var sum_of_payment = 0;

              if (payment_count > 0) {
                  for ($i = 1; $i <= payment_count; $i++) {
                      var val = parseFloat($('#payment_' + $i).val());
                      if (val != "") {
                          sum_of_payment += val;
                      }
                  }
              }

              if (sum_of_payment != 100) {
                  alert('Payment % total should be 100%');
                  return false;
              }
          });

          $('.payment').keypress(function (e) {
              var charCode = (e.which) ? e.which : event.keyCode

              if (String.fromCharCode(charCode).match(/[^0-9\.]/g))
                  return false;
          });
      })

      function removepaymentnote(current_val) {
          var current_index = parseInt(current_val.value);
          var note_count = parseInt($('#split_payment_count').val());

          $('#payment_box_' + current_index).remove();

          for (var i = current_index + 1; i <= note_count; i++) {
              $('#payment_box_' + i).attr("id", 'payment_box_' + (i - 1));
              $('#payment_' + i).attr("id", 'payment_' + (i - 1));
              $('[name=payment_' + i + ']').attr("name", 'payment_' + (i - 1));
              $('#payment_note_' + i + '_1').attr("id", 'payment_note_' + (i - 1) + '_1');
              $('[name=payment_note_' + i + '_1]').attr("name", 'payment_note_' + (i - 1) + '_1');
              $('#payment_note_' + i + '_2').attr("id", 'payment_note_' + (i - 1) + '_2');
              $('[name=payment_note_' + i + '_2]').attr("name", 'payment_note_' + (i - 1) + '_2');
              $('#payment_note_' + i + '_3').attr("id", 'payment_note_' + (i - 1) + '_3');
              $('[name=payment_note_' + i + '_3]').attr("name", 'payment_note_' + (i - 1) + '_3');
              $('#payment_note_' + i + '_4').attr("id", 'payment_note_' + (i - 1) + '_4');
              $('[name=payment_note_' + i + '_4]').attr("name", 'payment_note_' + (i - 1) + '_4');

              $('#btn_remove_split_payment_' + i).val(i - 1);
              $('#btn_remove_split_payment_' + i).attr("id", 'btn_remove_split_payment_' + (i - 1));
          }

          $('#split_payment_count').val(note_count - 1);
      }

  </script>
@endsection