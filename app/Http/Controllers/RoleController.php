<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use DB;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-roles|create-role|edit-role|delete-role', ['only' => ['index','show']]);
        $this->middleware('permission:create-role', ['only' => ['create','store']]);
        $this->middleware('permission:edit-role', ['only' => ['edit','update']]);
        $this->middleware('permission:delete-role', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('roles.index', [
            'roles' => Role::with('permissions')->orderBy('id', 'DESC')->paginate(10),
            'permissions' => Permission::get()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('roles.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * The permission names to sync onto a role, restricted so a non-super-admin
     * can only grant permissions they themselves hold (prevents privilege
     * escalation via role management). Existing permissions the actor can't
     * manage are preserved rather than stripped.
     */
    private function restrictedPermissionNames(?Role $role, $requestedIds): array
    {
        $requested = Permission::whereIn('id', (array) $requestedIds)->pluck('name')->toArray();

        $actor = auth()->user();
        if ($actor->is_super_admin ?? false) {
            return $requested;
        }

        $held = $actor->getAllPermissions()->pluck('name')->toArray();
        $addable = array_intersect($requested, $held);
        $existing = $role ? $role->permissions->pluck('name')->toArray() : [];
        $preserved = array_diff($existing, $held);

        return array_values(array_unique(array_merge($preserved, $addable)));
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create(['name' => $request->name]);

        $role->syncPermissions($this->restrictedPermissionNames($role, $request->permissions));

        return redirect()->route('roles.index')
                ->withSuccess('New role is added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(): RedirectResponse
    {
        return redirect()->route('roles.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role): RedirectResponse
    {
        return redirect()->route('roles.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $input = $request->only('name');

        $role->update($input);

        $role->syncPermissions($this->restrictedPermissionNames($role, $request->permissions));

        return redirect()->route('roles.index')
                ->withSuccess('Role is updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        if($role->name=='Super Admin'){
            abort(403, 'SUPER ADMIN ROLE CAN NOT BE DELETED');
        }
        if(auth()->user()->hasRole($role->name)){
            abort(403, 'CAN NOT DELETE SELF ASSIGNED ROLE');
        }
        $role->delete();
        return redirect()->route('roles.index')
                ->withSuccess('Role is deleted successfully.');
    }
}