<div class="row">
    <div class="col-md-6">
        <h3>Users</h3>
    </div>
</div>
<table class="table table-striped table-bordered">
    <div class="form-group">
        <label for="name">Search Name</label>
        <input type="text" name="name" id="name" class="userName" value="{{$search}}">
    </div>
    <thead>
        <tr>
            <th>Name</th>
            <th>Roles</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach($users as $user)
        <tr class="role">
            <td>{{ $user->first_name}}</td>
            <td>
                @foreach($user->roles as $role)
                <option>{{$role->name}}</option>
                @endforeach
            </td>
            <td>
                <a href="#" data-toggle="modal" data-target="#create-edit-modal" class="btn btn-warning rounded-circle editOption" data-id="{{ $user->id }}" data-id="user" data-name="user"><i class="fa fa-edit fa-fw"></i> Edit</a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<script>
     $(document).ready(function() {
        function debounce(func, delay) {
                var timeoutId;
                return function() {
                    var context = this;
                    var args = arguments;
                    clearTimeout(timeoutId);
                    timeoutId = setTimeout(function() {
                        func.apply(context, args);
                    }, delay);
                };
            };
             //to search the user with user name 
             $('.userName').on('input', debounce(function(e) {
                let regex = /^[a-z_.-]+$/;
                let searchValue = e.target.value;
                let url= (searchValue.length>0)?`/admin/dashboard/roles-permissions/user/${searchValue}`:`/admin/dashboard/roles-permissions/users`;
                    $.ajax({
                        type: 'GET',
                        url:url,
                        data: {
                            '_token': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(data) {
                            $('.createError').text("")
                            $('#users').html(data);
                        },
                        error: function(xhr, status, error, data) {
                            $('.createError').text(xhr.responseJSON.data)
                        }
                    });

                
                                   
                
            }, 600))
     })
</script>