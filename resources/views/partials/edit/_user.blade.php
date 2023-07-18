<form action="{{ route('roles_permissions.updateUserRole', $user->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="form-group">
        <label for="{{$user->first_name}}">{{$user->first_name}}</label>
    </div>
    <div class="form-group">
        <label>Roles</label>
        <br>
        @foreach ($roles as $role)
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="roles[]" id="{{ $role->name }}" value="{{ $role->name }}" {{ in_array($role->name, $userRoles->toArray()) ? 'checked' : '' }}>
            <label class="form-check-label" for="{{ $role->name }}">{{ $role->name }}</label>
        </div>
        @endforeach
    </div>
    <button type="submit" class="btn btn-primary">Update user</button>
</form>