<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Error;
use Exception;;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\User;

class RolePermissionController extends Controller
{
    // function will trigger when page loading
    public function index()
    {
        $roles = Role::all();
        $permissions = Permission::all();
        $users = User::limit(10)->with('roles')->get();
        $search = "";
        return view('admin.role_permission.index', compact('roles', 'permissions', 'users', 'search'));
    }

    // Role Edit function
    public function editRole($id)
    {
        $role = Role::findById($id);
        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('name');
        return view('partials.edit._role', compact('role', 'permissions', 'rolePermissions'));
    }

    // Permission Edit function
    public function editPermission($id)
    {
        $permission = Permission::findById($id);
        return view('partials.edit._permission', compact('permission'));
    }

    // edit operation for roles 
    public function updateRole(Request $request, $id)
    {
        $role = Role::findById($id);
        $role->name = $request->input('name');
        $role->syncPermissions($request->input('permissions', []));
        $role->save();
        return redirect()->back()->with('success', 'Role updated successfully.');
    }

    public function editUserRole($id)
    {   
        $user = User::where('id',$id)->first();
        $roles=Role::all();
        $userRoles=$user->getRoleNames();
        return view('partials.edit._user', compact('user','roles','userRoles'));
    }
    //edit operation for user Role
    public function updateUserRole(Request $request, $id)
    {
        $user= User::find($id);
        $roles = Role::whereIn('name', $request->input('roles',[]))->get();
        $user->syncRoles($roles);
        $user->save();
        return redirect()->back()->with('success', 'Role updated successfully.');
    }
    // edit operation for permission
    public function updatePermission(Request $request, $id)
    {
        $permission = Permission::findById($id);
        $permission->name = $request->input('name');
        $permission->save();
        return redirect()->back()->with('success', 'Role updated successfully.');
    }

    // create operation for roles 
    public function createRole(Request $request)
    {
        try {
            Role::create(['name' => $request->input('create_name')])->save();
            $roles = Role::all();
            return view('partials.roleAndPermission._role', compact('roles'));
        } catch (Exception $e) {
            return response()->json(['error' => true, 'data' => $e->getMessage()], 400);
        }
    }

    public function viewRoles(Request $request)
    {
        try {
            $roles = Role::all();
            return view('partials.roleAndPermission._role', compact('roles'));
        } catch (Exception $e) {
            return response()->json(['error' => true, 'data' => $e->getMessage()], 400);
        }
    }

    public function viewUsers(Request $request)
    {
        try {
            $users = User::limit(10)->with('roles')->get();
            $search="";
            return view('partials.roleAndPermission._users', compact('users','search'));
        } catch (Exception $e) {
            return response()->json(['error' => true, 'data' => $e->getMessage()], 400);
        }
    }

    public function searchUser($search)
    {
        try {
            $users = User::where(function ($query) use ($search) {
                $query->where('first_name', 'LIKE', '%' . $search . '%')
                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
            })->limit(10)->with('roles')->get();
            return view('partials.roleAndPermission._users', compact('users', 'search'));
        } catch (Exception $e) {
            return response()->json(['error' => true, 'data' => $e->getMessage()], 400);
        }
    }

    // create operation for permission
    public function createPermission(Request $request)
    {
        $perm = Permission::create(['name' => $request->input('create_name')])->save();
        $permissions = Permission::all();
        return view('partials.roleAndPermission._permission', compact('permissions'));
    }

    //view for permission
    public function viewPermission(Request $request)
    {
        try {
            $permissions = Permission::all();
            return view('partials.roleAndPermission._permission', compact('permissions'));
        } catch (Exception $e) {
            return response()->json(['error' => true, 'data' => $e->getMessage()], 400);
        }
    }
    // To delete roles 
    public function deleteRole($id)
    {
        Role::find($id)->delete();
        $roles = Role::all();
        return view('partials.roleAndPermission._role', compact('roles'));
    }

    // To delete permission 
    public function deletePermission($id)
    {
        Permission::find($id)->delete();
        $permissions = Permission::all();
        return view('partials.roleAndPermission._permission', compact('permissions'));
    }

    //Search if role name is present
    public function searchRole($search)
    {
        $role = Role::where('name', $search)->first();
        if ($role == null) {
            return response()->json(['success' => true, 'data' => "role doesn't exist"], 200);
        } else {
            return response()->json(['error' => true, 'data' => "Role with name $search is already exist"], 400);
        }
    }

    //search if Permission name is present
    public function searchPermission($search)
    {
        $role = Permission::where('name', $search)->first();
        if ($role == null) {
            return response()->json(['success' => true, 'data' => "Permission doesn't exist"], 200);
        } else {
            return response()->json(['error' => true, 'data' => "Permission with name $search is already exist"], 400);
        }
    }

}
