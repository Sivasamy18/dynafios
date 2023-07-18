@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_super_hospital_user; @endphp
@php use function App\Start\is_user_owner; @endphp
@php use function App\Start\is_physician; @endphp
@extends('layouts/_dashboard')
@section('main')
    <div class="page-header">
        <h3>
            <i class="fa fa-user fa-fw icon"></i> {{ "$user->first_name $user->last_name" }}
        </h3>

        <div class="btn-group btn-group-sm">
            @if (is_super_user())
                <a class="btn btn-default" href="{{ URL::route('users.index') }}">
                    <i class="fa fa-list fa-fw"></i> Index
                </a>
            @elseif(is_super_hospital_user() && !is_user_owner($user->id))
                <a class="btn btn-default" href="{{ URL::route('hospitals.admins',$hospital_id) }}">
                    <i class="fa fa-list fa-fw"></i> Index
                </a>
            @endif
            @if(is_user_owner($user->id) &&(!is_physician())&&(!is_super_user()))
                <a class="btn btn-default" href="{{ URL::route('users.add_proxy', $user->id) }}">
                    <i class="fa fa-cog fa-fw"></i> Proxy Approver
                </a>
            @endif
            @if (is_physician())
                <a class="btn btn-default" href="{{ URL::route('users.edit',$user->id) }}">
                    <i class="fa fa-cogs fa-fw"></i> Settings
                </a>
            @elseif(is_user_owner($user->id))
                <a class="btn btn-default" href="{{ URL::route('users.edit',$user->id) }}">
                    <i class="fa fa-cogs fa-fw"></i> Settings
                </a>
            @elseif(is_super_hospital_user())
                <a class="btn btn-default" href="{{ URL::route('users.adminedit', [$user->id,$hospital_id]) }}">
                    <i class="fa fa-cogs fa-fw"></i> Settings
                </a>
            @endif

            @if (is_super_user() && $user->group->id!=1)
                <a class="btn btn-default"
                   href="{{ URL::route('userswitch.switchuser', array('new_user_id' => $user->id)) }}">
                    <i class="fa fa-random fa-fw"></i> Switch To User
                </a>
            @endif

            @if (is_super_user())
                <a class="btn btn-default btn-delete" href="{{ URL::route('users.delete', $user->id) }}">
                    <i class="fa fa-trash-o fa-fw"></i> Delete
                </a>
            @elseif(is_super_hospital_user() && !is_user_owner($user->id))
                <a class="btn btn-default btn-delete"
                   href="{{ URL::route('users.admindelete', [$user->id,$hospital_id]) }}">
                    <i class="fa fa-trash-o fa-fw"></i> Delete
                </a>
            @endif
        </div>
    </div>
    @include('layouts/_flash')
    <div class="panel panel-default">
        <div class="panel-heading">
            <h5 class="text-center">General Information</h5>
        </div>
        <table class="table profile-table">
            @if (is_super_user())
                <tr>
                    <td><i class="fa fa-users fa-fw"></i> Group</td>
                    <td>{{ $user->group->name }}</td>
                </tr>
            @endif
            <tr>
                <td><i class="fa fa-envelope fa-fw"></i> Email</td>
                <td><a href="mailto::{{ $user->email }}">{{ $user->email }}</a></td>
            </tr>
            <tr>
                <td><i class="fa fa-phone fa-fw"></i> Phone</td>
                <td>{{ $user->phone }}</td>
            </tr>
            <tr>
                <td><i class="fa fa-lock fa-fw"></i> Locked</td>
                @if($user->locked===1)
                    <td>True</td>
                @else
                    <td>False</td>
                @endif
            </tr>
            <tr>
                <td><i class="fa fa-calendar fa-fw"></i> Password Expiration</td>
                <td>{{ format_date($user->password_expiration_date) }}</td>
            </tr>
            <tr>
                <td><i class="fa fa-calendar fa-fw"></i> Created</td>
                <td>{{ format_timestamp($user->created_at) }}</td>
            </tr>
            <tr>
                <td><i class="fa fa-clock-o fa-fw"></i> Last Seen</td>
                <td>{{ format_timestamp($user->seen_at) }}</td>
            </tr>
        </table>
        @include('audits.audit-history', ['audits' => $user->audits()->orderBy('created_at', 'desc')->with('user')->paginate(50)])
    </div>
    <div id="modal-confirm-delete" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">Delete User?</h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this user?</p>

                    <p><strong style="color: red">Warning!</strong><br>
                        This action will delete the specified user and any associated data. There is no way to restore
                        this data once this action has been completed.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Delete</button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div><!-- /.modal -->
@endsection

@section('scripts')
    <script type="text/javascript">
        $(function () {
            Dashboard.confirm({
                button: '.btn-delete',
                dialog: '#modal-confirm-delete',
                dialogButton: '.btn-primary'
            });

        });
    </script>
@endsection
