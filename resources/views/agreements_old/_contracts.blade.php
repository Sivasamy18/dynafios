@if (count($contracts) > 0)
<ul style="list-style:none">
    @foreach ($contracts as $contract)
    <li>
        <i class="fa fa-file-text-o"></i> <strong>{{ $contract->name }}</strong>
        <ul style="list-style:none">
            @foreach ($contract->practices as $practice)
            <li>
                <a href="{{ route('practices.show', $practice->id) }}">
                    <i class="fa fa-hospital-o fa-fw"></i> {{ $practice->name}}
                </a>
                <ul style="list-style:none">
                    @foreach ($practice->physicians as $physician)
                    <li>
                        <a href="{{ route('physicians.show', $physician->id) }}">
                            <i class="fa fa-user-md fa-fw"></i> {{ $physician->name }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </li>
            @endforeach
        </ul>
    </li>
    @endforeach
</ul>
@else
<p>There are currently no contracts available for display.</p>
@endif