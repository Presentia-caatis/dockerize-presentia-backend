<?php

namespace App\Http\Controllers;

use App\Filterable;
use App\Models\User;
use App\Sortable;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use function App\Helpers\current_school_id;

class RoleController extends Controller
{
    use Filterable, Sortable;
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1',
            'pluckName' => 'sometimes|boolean'
        ]);

        $query = Role::query(); 
        $query = $this->applyFilters($query, $request->input('filter', []));
        $query = $this->applySort($query, $request->input('sort', []));

        if($request->pluckName ?? false){
            $data = $query->pluck('name', 'id');
        }else{
            $perPage = $validatedData['perPage'] ?? 10;
            $data = $query->with('permissions')->paginate($perPage);
        }
        

        return response()->json([
            'status' => 'success',
            'message' => 'Roles retrieved successfully',
            'data' => $data
        ]);
    }

    public function getSchoolRoles(Request $request){
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1',
            'pluckName' => 'sometimes|boolean'
        ]);

        $query = Role::query(); 
        $query = $this->applyFilters($query, $request->input('filter', []));
        $query = $this->applySort($query, $request->input('sort', []));

        $query->where('name', 'LIKE', 'school%');

        if($request->pluckName ?? false){
            $data = $query->pluck('name', 'id');
        }else{
            $perPage = $validatedData['perPage'] ?? 10;
            $data = $query->with('permissions')->paginate($perPage);
        }
        

        return response()->json([
            'status' => 'success',
            'message' => 'Roles retrieved successfully',
            'data' => $data
        ]);
    }

    /**
     * Store a new role.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles',
            'permissions' => 'array', // Optional: List of permissions
        ]);

        $role = Role::create(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Role created successfully',
            'data' => $role
        ]);
    }

    /**
     * Show a single role.
     */
    public function show($id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Role retrieved successfully',
            'data' => $role
        ]);
    }

    /**
     * Update a role's name or permissions.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string|unique:roles,name,' . $id,
            'permissions' => 'array',
        ]);

        $role = Role::findOrFail($id);

        if ($request->has('name')) {
            $role->update(['name' => $request->name]);
        }

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Role updated successfully',
            'data' => $role
        ]);
    }

    /**
     * Assign a role to a user.
     */
    public function assignToUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|exists:roles,name',
        ]);

        $user = User::where("school_id", current_school_id())->findOrFail($request->user_id);

        $user->syncRoles([$request->role]);
        $user->load('roles');

        return response()->json([
            'status' => 'success',
            'message' => 'Role assigned successfully',
            'data' => $user
        ]);
    }

    /**
     * Remove a role from a user.
     */
    public function removeFromUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|exists:roles,name',
        ]);

        $user = User::findOrFail($request->user_id);
        $user->removeRole($request->role);

        return response()->json([
            'status' => 'success',
            'message' => 'Role removed successfully',
            'data' => $user
        ]);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Role deleted successfully'
        ]);
    }

    /**
     * Delete all roles and permissions.
     */
    public function destroyAll()
    {
        Role::query()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'All roles and permissions deleted successfully',
        ]);
    }
}
