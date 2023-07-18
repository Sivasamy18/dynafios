<div class="row">
    <div class="col-md-6">
        <h3>Roles</h3>
    </div>
    <div class="col-md-6">
        <a href="#" data-toggle="modal" data-target="#create-role-modal" class="btn btn-success rounded-circle add" data-action="/admin/dashboard/create/role" data-type="Role">Add</a>
    </div>
</div>
<table class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>Name</th>
            <th>Permissions</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach($roles as $role)
        <tr class="role" id="role{{$role->id}}" data-role-id="{{ $role->id }}">
            <td>{{ $role->name }}</td>
            <td>
                <ul class="permissionInRole" id="td{{$role->id}}">
                    @foreach($role->permissions as $permission)
                    <li class="{{$permission->name}}">{{ $permission->name }}</li>
                    @endforeach
                </ul>
            </td>
            <td>
                <a href="#" data-toggle="modal" data-target="#create-edit-modal" class="btn btn-warning rounded-circle editOption" data-id="{{ $role->id }}" data-id="role" data-name="role"><i class="fa fa-edit fa-fw"></i> Edit</a>
                <a href="#" data-toggle="modal" data-target="#confirm-delete-modal" data-id="{{$role->id}}" data-value="role" data-name="{{$role->name}}" class="btn btn-danger rounded-circle delete-btn"><i class="fa fa-trash-o fa-fw"></i>Delete</a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
<script>
    $('.delete-btn').on('click', function(e) {
        e.preventDefault();
        let id = $(this).data('id');
        let deleteType = $(this).data('value');
        let deleteName = $(this).data('name');
        $('#confirm-delete-btn').data('id', id); // set id in modal button
        $('#confirm-delete-btn').data('deleteType', deleteType); // set type in modal button
        $('#confirm-delete-btn').data('deleteName', deleteName); // set deleteName in modal button
    });
    
</script>