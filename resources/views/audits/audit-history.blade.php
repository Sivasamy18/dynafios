@php use function App\Start\is_super_user; @endphp
@if(is_super_user())
    {{--variables: $audits--}}

    <div class="panel-heading">
        <h5 class="text-center">Audit History</h5>
    </div>
    <table class="table">
        <thead>
        <tr>
            <th class="text-center">Old Values</th>
            <th class="text-center">New Values</th>
            <th class="text-center">Changed By</th>
            <th class="text-center">Changed On</th>
            <th class="text-center">Triggering URL</th>
            <th class="text-center">IP Address</th>
        </tr>
        </thead>
        <tbody>
        @forelse($audits as $audit)
            <tr>
                <td>
                    @forelse($audit->old_values as $key => $oldValue)
                        <p><b>{{$key}}</b>: {{$oldValue ?? 'Null'}}</p>
                    @empty
                        <p>N/A</p>
                    @endforelse
                </td>
                <td>
                    @forelse($audit->new_values as $key => $newValue)
                        <p><b>{{$key}}</b>: {{$newValue ?? 'Null'}}</p>
                    @empty
                        <p>N/A</p>
                    @endforelse
                </td>
                <td>
                    @if($audit->user)
                        @if(auth()->user()->id === $audit->user_id)
                            <b>You</b>
                        @else
                            <p>{{$audit->user->getFullName()}}</p>
                        @endif
                    @else
                        <p>N/A</p>
                    @endif
                </td>
                <td>{{$audit->created_at->diffForHumans()}}</td>
                <td>{{$audit->url}}</td>
                <td>{{$audit->ip_address}}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center">No audit history to show.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div>
        {{$audits->links()}}
    </div>
@endif

<style>
    p {
        word-break: break-all;
    }
</style>
