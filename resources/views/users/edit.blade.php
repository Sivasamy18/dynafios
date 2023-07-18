@php use function App\Start\is_super_hospital_user; @endphp
@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_owner; @endphp
@extends('layouts/_dashboard')
@section('main')
    <div class="page-header">
        <h3>
            <i class="fa fa-user fa-fw icon"></i> Settings - {{ "$user->first_name $user->last_name" }}
        </h3>
        <div class="btn-group btn-group-sm">
            @if(is_super_hospital_user()||is_super_user()).
            @if($hospital_id>0)
                <a class="btn btn-default  btn-reset-password"
                   href="{{ URL::route('users.admin_reset_password', [$user->id,$hospital_id]) }}">
                    <i class="fa fa-arrow-circle-left fa-fw"></i> Reset Password
                </a>
                <a class="btn btn-default btn-welcome"
                   href="{{ URL::route('users.admin_welcome', [$user->id,$hospital_id]) }}">
                    <i class="fa fa-envelope fa-fw"></i> Welcome Packet
                </a>
                <a class="btn btn-default" href="{{ URL::route('users.adminshow', [$user->id,$hospital_id]) }}">
                    <i class="fa fa-arrow-circle-left fa-fw"></i> Back
                </a>
            @else
                <a class="btn btn-default  btn-reset-password"
                   href="{{ URL::route('users.reset_password', $user->id) }}">
                    <i class="fa fa-arrow-circle-left fa-fw"></i> Reset Password
                </a>
                <a class="btn btn-default btn-welcome" href="{{ URL::route('users.welcome',  $user->id) }}">
                    <i class="fa fa-envelope fa-fw"></i> Welcome Packet
                </a>
                <a class="btn btn-default" href="{{ URL::route('users.show', $user->id) }}">
                    <i class="fa fa-arrow-circle-left fa-fw"></i> Back
                </a>
            @endif
            @else
                <a class="btn btn-default  btn-reset-password"
                   href="{{ URL::route('users.reset_password', $user->id) }}">
                    <i class="fa fa-arrow-circle-left fa-fw"></i> Reset Password
                </a>

            @endif


        </div>
    </div>
    @include('layouts/_flash')
    {{ Form::open([ 'class' => 'form form-horizontal form-edit-user' ]) }}
    {{ Form::hidden('id', $user->id) }}
    <div class="panel panel-default">
        <div class="panel-heading">General</div>
        <div class="panel-body">
            @if (is_super_user()|| is_super_hospital_user())
                <div class="form-group">
                    <label class="col-xs-2 control-label">Email</label>
                    <div class="col-xs-5">
                        {{ Form::text('email', $user->email, [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('email', '<p  id="error-message" class="validation-error">:message</p>') !!}</div>
                </div>
            @endif
            <div class="form-group">
                <label class="col-xs-2 control-label">First Name</label>

                <div class="col-xs-5">
                    {{ Form::text('first_name', $user->first_name, [ 'class' => 'form-control' ]) }}
                </div>
                <div class="col-xs-5">
                    {!! $errors->first('first_name', '<p class="validation-error">:message</p>') !!}
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-2 control-label">Last Name</label>

                <div class="col-xs-5">
                    {{ Form::text('last_name', $user->last_name, [ 'class' => 'form-control' ]) }}
                </div>
                <div class="col-xs-5">
                    {!! $errors->first('last_name', '<p class="validation-error">:message</p>') !!}
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-2 control-label">Title</label>

                <div class="col-xs-5">
                    {{ Form::text('title', $user->title, [ 'class' => 'form-control' ]) }}
                </div>
                <div class="col-xs-5">
                    {!! $errors->first('title', '<p class="validation-error">:message</p>') !!}
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-2 control-label">Phone</label>
                <div class="col-xs-5">
                    {{ Form::text('phone', $user->phone, [ 'class' => 'form-control' ]) }}
                </div>
                <div class="col-xs-5">
                    {!! $errors->first('phone', '<p class="validation-error">:message</p>') !!}
                </div>
            </div>
            @if (is_super_user())
                <div class="form-group">
                    <label class="col-xs-2 control-label">Group</label>
                    <div class="col-xs-5">
                        {{ Form::select('group', $groups, Request::old('group_id', $user->group_id), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">
                        {!! $errors->first('phone', '<p class="validation-error">:message</p>') !!}
                    </div>
                </div>
            @endif
            @if (is_super_user())
                <div class="form-group">
                    <label class="col-xs-2 control-label">Locked</label>

                    <div class="col-xs-4">
                        <div id="toggle" class="input-group">
                            <label class="switch">
                                <!--<input id="on_off" name="on_off" type="checkbox" checked>-->
                                {{ Form::checkbox('locked', 1, Request::old('locked',$user->locked), ['id' => 'locked']) }}
                                <div class="slider round"></div>
                                <div class="text"></div>
                            </label>
                        </div>
                    </div>
                    <div class="col-xs-5"></div>
                </div>
            @endif
        </div>
        @if (is_owner($user->id))
            <div class="panel panel-default">
                <div class="panel-heading">Change Password</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-xs-2 control-label">Current Password</label>

                        <div class="col-xs-5">
                            {{ Form::password('current_password', [ 'class' => 'form-control','id' => 'current_password' ]) }}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-2 control-label">New Password</label>

                        <div class="col-xs-5">
                            {{ Form::password('new_password', [ 'class' => 'form-control','id' => 'new_password' ]) }}
                        </div>
                        <div class="col-xs-1">
                            <span class="fa fa-fw fa-eye toggle-password-new"></span>
                        </div>
                        <div class="col-xs-5">
                            {!! $errors->first('new_password', '<p class="validation-error">:message</p>') !!}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-2 control-label">Confirm New Password</label>

                        <div class="col-xs-5">
                            {{ Form::password('new_password_confirmation', [ 'class' => 'form-control','id' => 'new_password_confirmation' ]) }}
                        </div>
                        <div class="col-xs-5">
                            {!! $errors->first('new_password_confirmation', '<p class="validation-error">:message</p>') !!}
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <div class="panel-footer clearfix">
            <button class="btn btn-primary btn-sm btn-submit" type="submit"
                    onclick="return validateEmailField(email_domains)">Submit
            </button>
        </div>
    </div>
    {{ Form::close() }}

    <div id="modal-confirm-welcome" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">Send Welcome Packet?</h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to send the welcome packet to this User?</p>

                    <p><strong style="color: red">Warning!</strong><br>
                        The welcome packet should be sent once per user after all contracts have been setup
                        successfully. An email will be sent to the user each time this feature is used.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Send Packet</button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div><!-- /.modal -->

    <div id="modal-confirm-reset" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">Reset Password?</h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reset this user's password?</p>

                    <p>
                        <small>Note: The new password will be emailed to the users current email address.</small>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Reset Password</button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div><!-- /.modal -->

@endsection

@section('scripts')
    <script type="text/javascript">
        let email_domains = '{{ env("EMAIL_DOMAIN_REJECT_LIST") }}';
        $(function () {
            Dashboard.confirm({
                button: '.btn-welcome',
                dialog: '#modal-confirm-welcome',
                dialogButton: '#modal-confirm-welcome .btn-primary'
            });

            Dashboard.confirm({
                button: '.btn-reset-password',
                dialog: '#modal-confirm-reset',
                dialogButton: '#modal-confirm-reset .btn-primary'
            });

            $(".toggle-password-new").on({
                mouseenter: function () {
                    $('#current_password').attr('type', 'text');
                    $('#new_password').attr('type', 'text');
                    $('#new_password_confirmation').attr('type', 'text');
                },
                mouseleave: function () {
                    $('#current_password').attr('type', 'password');
                    $('#new_password').attr('type', 'password');
                    $('#new_password_confirmation').attr('type', 'password');
                }
            });
        });
    </script>
@endsection
