<form action="{{ route('roles_permissions.updateRole', $role->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="form-group">
        <label for="name">Role Name</label>
        <input type="text" name="name" id="name" class="form-control" value="{{ $role->name }}" pattern="^[a-zA-Z_-. ]+$" maxlength="50">
    </div>
    <div class="form-group">
        <label>Permissions</label>
        <br>
        @foreach ($permissions as $permission)
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="permissions[]" id="{{ $permission->name }}" value="{{ $permission->name }}" {{ in_array($permission->name, $rolePermissions->toArray()) ? 'checked' : '' }}>
            <label class="form-check-label" for="{{ $permission->name }}" pattern="^[a-zA-Z0-9_]+$" maxlength="50">{{ $permission->name }}</label>
        </div>
        @endforeach
    </div>
    <button type="submit" class="btn btn-primary">Update Role</button>
</form>