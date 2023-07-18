@extends('layouts._admin')

@section('main')
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Payments</h3>
    </div>
    <form action="{{ route('admin.payments.index') }}" method="get">
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label for="start_date">Start Date</label>
            <input type="date" class="form-control" name="start_date" id="start_date"
                   value="{{ request('start_date') }}">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label for="end_date">End Date</label>
            <input type="date" class="form-control" name="end_date" id="end_date" value="{{ request('end_date') }}">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label for="contract_name">Search Contract Name</label>
            <input type="text" class="form-control" name="contract_name" id="contract_name"
                   value="{{ request('contract_name') }}">
          </div>
        </div>

        <div class="col-md-3">
          <div class="form-group">
            <label for="physician_name">Search Physician Name</label>
            <input type="text" class="form-control" name="physician_name" id="physician_name"
                   value="{{ request('physician_name') }}">
          </div>
        </div>

        <div class="col-md-3">
          <div class="form-group">
            <label for="amount">Search Amount</label>
            <input type="text" class="form-control" name="amount" id="amount"
                   value="{{ request('amount') }}">
          </div>
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn btn-primary mt-4">Search</button>
        </div>

        <div class="col-md-3">
          <a href="{{ route('admin.payments.index') }}" class="btn btn-primary mt-4 ml-2">Clear Filters</a>
        </div>
      </div>
    </form>


    <div class="card-body">
      <table class="table table-bordered" id="payments-table">
        <thead>
        <tr>
          <th>ID</th>
          <th>Amount</th>
          <th>Invoice Number</th>
          <th>Physician</th>
          <th>Start Date</th>
          <th>End Date</th>
          <th>Contract</th>
          <th>Delete</th>
        </tr>
        </thead>
        <tbody id="payment-rows">
        @foreach ($payments as $payment)
          <tr>
            <td>{{ $payment->id }}</td>
            <td>{{ $payment->amt_paid }}</td>
            <td>{{ $payment->amountPaid->invoice_no ?? 'N/A'}}</td>
            @if($payment->physician)
              @if($payment->contract)
              <td>
                <a href="{{url("/physicians/{$payment->physician->id}/{$payment->contract->practice_id}")}}">{{ $payment->physician->getFullName() }}</a>
              </td>
                @else
                <td>Error Getting Contract</td>
              @endif
            @else
              <td>Error Getting Physician</td>
            @endif
            <td>{{ $payment->start_date }}</td>
            <td>{{ $payment->end_date }}</td>
            @if($payment->contract)
              <td>
                <a href="{{url("/practices/{$payment->contract->practice_id}/contract/{$payment->contract->id}")}}">{{ $payment->contract->contractName->name }}</a>
              </td>
            @else
              <td>No Contract Data Found</td>
            @endif
            @if($payment->deleted_at)
              <td>{{ \Carbon\Carbon::createFromTimeString($payment->deleted_at) }}</td>
            @else
              <td>
                <div class="dropdown">
                  <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton"
                          data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                          style="margin: auto; display: flex">
                    ...
                  </button>
                  <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <button type="button" class="dropdown-item delete-payment" style="margin: auto; display: flex" data-payment-id="{{ $payment->id }}">
                      Delete
                    </button>
                  </div>
                </div>
              </td>
            @endif
          </tr>
        @endforeach
        </tbody>
      </table>
      {{ $payments->links() }}
    </div>
  </div>
  <div id="modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Confirm Payment Deletion</h5>
          <button type="button" class="close" data-dismiss="modal">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this payment?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger" id="delete-payment">Delete</button>
        </div>
      </div>
    </div>
  </div>
  <div id="loading-overlay" class="overlay">
    <div class="spinner"></div>
  </div>
  <style>
      .overlay {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(0, 0, 0, 0.5);
          z-index: 1000;
          display: none;
      }
  </style>
  <script>
      $(document).ready(function () {
          // When a delete payment button is clicked, show the confirmation modal
          $('.delete-payment').click(function (e) {
              e.preventDefault();
              $('#modal').modal('show');
              var paymentId = $(this).data('payment-id');
              $('#delete-payment').data('payment-id', paymentId);
          });

          // When the delete button on the confirmation modal is clicked, send the delete request and show the loading overlay
          $('#delete-payment').click(function (e) {
              e.preventDefault();
              var paymentId = $(this).data('payment-id');

              $('#modal').modal('hide');
              $('#loading-overlay').show();

              $.ajax({
                  url: `/admin/dashboard/payment/${paymentId}/delete`,
                  type: 'DELETE',
                  data: {
                      _token: '{{ csrf_token() }}'
                  },
                  success: function (response) {
                      if (response.status === 200) {
                          location.reload();
                          alert(response.message);
                      } else {
                          alert(response.message);
                      }
                  },
                  error: function (jqXHR, textStatus, errorThrown) {
                      alert('Error deleting payment.');
                      console.log(jqXHR.responseText);
                  },
                  complete: function () {
                      $('#loading-overlay').hide();
                  }
              });
          });
      });
  </script>
@endsection