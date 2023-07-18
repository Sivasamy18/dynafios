
        <div class="row">
            <div class="col-md-6">
                <h3>Permissions</h3>
            </div>
            <div class="col-md-6">
                <a href="#" data-toggle="modal" data-target="#create-role-modal" class="btn btn-success rounded-circle add" data-action="/admin/dashboard/roles-permissions" data-type="Permission">Add</a>
            </div>
        </div>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($permissions as $permission)
                <tr class="permission " id="permission{{$permission->id}}" data-permission-id="{{ $permission->id }}" data-permission-name="{{$permission->name}}">
                    <td>{{ $permission->name }}</td>
                    <td>
                        <a href="#" data-toggle="modal" data-target="#create-edit-modal" class="btn btn-warning rounded-circle editOption" data-id="{{ $permission->id }}" data-name="permission"><i class="fa fa-edit fa-fw"></i> Edit</a>
                        <a href="#" data-toggle="modal" data-target="#confirm-delete-modal" class="btn btn-danger rounded-circle delete-btn" data-id="{{$permission->id}}" data-name="{{$permission->name}}" data-value="permission"><i class="fa fa-trash-o fa-fw"></i> Delete</a>
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
