<?php

namespace App\Http\Controllers;

use App\Filterable;
use App\Sortable;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    use Filterable, Sortable;

    public function getAll(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1', 
            'pluckName' => 'sometimes|boolean'
        ]);
        

        $query = Permission::query(); 
        $query = $this->applyFilters($query, $request->input('filter', []));
        $query = $this->applySort($query, $request->input('sort', []));

        if($request->pluckName ?? false){
            $data = $query->pluck('name', 'id');
        }else{
            $perPage = $validatedData['perPage'] ?? 10;
            $data = $query->paginate($perPage);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Permissions retrieved successfully',
            'data' => $data
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions'
        ]);

        $permission = Permission::create(['name' => $request->name]);

        return response()->json([
            'status' => 'success',
            'message' => 'Permission created successfully',
            'data' => $permission
        ]);
    }


    public function getById($id)
    {
        $permission = Permission::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Permission retrieved successfully',
            'data' => $permission
        ]);
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $id,
        ]);

        $permission = Permission::findOrFail($id);
        $permission->update(['name' => $request->name]);

        return response()->json([
            'status' => 'success',
            'message' => 'Permission updated successfully',
            'data' => $permission
        ]);
    }


    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Permission deleted successfully'
        ]);
    }


    public function assignToRole(Request $request)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
            'permission' => 'required|string|exists:permissions,name',
        ]);

        $role = Role::where('name',$request->role)->first();
        $role->givePermissionTo($request->permission);

        return response()->json([
            'status' => 'success',
            'message' => 'Permission assigned to role successfully',
            'data' => $role->load('permissions')
        ]);
    }


    public function removeFromRole(Request $request)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
            'permission' => 'required|string|exists:permissions,name',
        ]);

        $role = Role::where('name',$request->role)->first();
        $role->revokePermissionTo($request->permission);

        return response()->json([
            'status' => 'success',
            'message' => 'Permission removed from role successfully',
            'data' => $role->load('permissions')
        ]);
    }

    public function destroyAll()
    {
        Permission::query()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'All permissions deleted successfully'
        ]);
    }
}
