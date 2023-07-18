@extends('layouts._admin')

@section('main')
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Email Activity Tracker</h3>
    </div>
    <form action="{{ route('admin.emails.index') }}" method="get">
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label for="start_date">Sent At - Date Range Filter Start</label>
            <input type="date" class="form-control" name="start_date" id="start_date"
                   value="{{ request('start_date') }}">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label for="end_date">Sent At - Date Range Filter End</label>
            <input type="date" class="form-control" name="end_date" id="end_date" value="{{ request('end_date') }}">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label for="sent_to">Search Sent To</label>
            <input type="text" class="form-control" name="sent_to" id="sent_to"
                   value="{{ request('sent_to') }}">
          </div>
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn btn-primary mt-4">Search</button>
        </div>

        <div class="col-md-3">
          <a href="{{ route('admin.emails.index') }}" class="btn btn-primary mt-4 ml-2">Clear Filters</a>
        </div>
      </div>
    </form>


    <div class="card-body">
      <table class="table table-bordered" id="email-activity-table">
        <thead>
        <tr>
          <th>Sent To</th>
          <th>Subject</th>
          <th>Message</th>
          <th>Sent At</th>
        </tr>
        </thead>
        <tbody id="email-rows">
        @foreach ($mailTrackers as $tracker)
          <tr>
            <td>{{ $tracker->sent_to }}</td>
            <td>{{ $tracker->subject }}</td>
            <td>{{ $tracker->message }}</td>
            <td>{{ $tracker->created_at }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
      {{ $mailTrackers->links() }}
    </div>
  </div>
@endsection