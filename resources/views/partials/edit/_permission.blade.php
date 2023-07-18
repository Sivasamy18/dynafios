<form action="{{ route('roles_permissions.updatePermission', $permission->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="form-group">
        <label for="name">Permission Name</label>
        <input type="text" name="name" id="name" class="form-control" value="{{ $permission->name }}" pattern="^[a-zA-Z_-. ]+$" maxlength="50">
    </div>
    <button type="submit" class="btn btn-primary">Update Permission</button>
</form>