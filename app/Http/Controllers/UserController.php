<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    /**
     * Instantiate a new UserController instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-users|create-user|edit-user|delete-user', ['only' => ['index','show','getData']]);
        $this->middleware('permission:create-user', ['only' => ['create','store']]);
        $this->middleware('permission:edit-user', ['only' => ['edit','update']]);
        $this->middleware('permission:delete-user', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('users.index');
    }

    /**
     * Get users data for DataTables
     */
    public function getData(Request $request)
    {
        $query = User::query();

        // Tenant isolation: a non-super-admin only sees users whose primary
        // company is one they can access (User has no global company scope).
        $actor = auth()->user();
        if (! ($actor->is_super_admin ?? false)) {
            $companyIds = $actor->accessibleCompanies()->pluck('id')->all();
            $query->whereIn('company_id', $companyIds ?: [-1]);
        }

        return DataTables::of($query)
            ->addColumn('roles_badge', function ($user) {
                $roles = $user->getRoleNames();
                if ($roles->count() > 0) {
                    $badges = '';
                    foreach ($roles as $role) {
                        $badges .= '<span class="badge bg-secondary me-1">' . $role . '</span>';
                    }
                    return $badges;
                }
                return '<span class="text-muted">No roles assigned</span>';
            })
            ->addColumn('name_with_badge', function ($user) {
                $html = '<strong>' . $user->name . '</strong>';
                if (in_array('Super Admin', $user->getRoleNames()->toArray() ?? [])) {
                    $html .= ' <span class="badge bg-danger ms-2">Protected</span>';
                }
                if ($user->is_demo_user) {
                    $html .= ' <span class="badge bg-warning text-dark ms-1" title="Restricted access">Demo</span>';
                }
                return $html;
            })
            ->addColumn('actions', function ($user) {
                $html = '<div class="d-flex gap-1">';
                
                if (in_array('Super Admin', $user->getRoleNames()->toArray() ?? [])) {
                    if (Auth::user()->hasRole('Super Admin')) {
                        $html .= '<a href="' . route('users.edit', $user->id) . '" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                  </a>';
                    }
                } else {
                    if (auth()->user()->can('edit-user')) {
                        $html .= '<a href="' . route('users.edit', $user->id) . '" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                  </a>';
                    }
                    
                    if (auth()->user()->can('delete-user') && Auth::user()->id != $user->id) {
                        $html .= '<form method="POST" action="' . route('users.destroy', $user->id) . '" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this user?\');">
                                    ' . csrf_field() . '
                                    ' . method_field('DELETE') . '
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                  </form>';
                    }
                }
                
                $html .= '</div>';
                return $html;
            })
            ->rawColumns(['roles_badge', 'name_with_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $companies = Company::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
        return view('users.create', [
            'roles' => Role::pluck('name')->all(),
            'sidebarMenuItems' => config('sidebar.menu_items', []),
            'companies' => $companies,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Block a non-super-admin from touching a user outside the companies they
     * can access (User has no global company scope, so route-model binding would
     * otherwise resolve any tenant's user by id — cross-tenant IDOR).
     */
    private function ensureCanManage(User $user): void
    {
        $actor = auth()->user();
        if (! ($actor->is_super_admin ?? false) && ! $actor->canAccessCompany($user->company_id)) {
            abort(403, 'You do not have access to this user.');
        }
    }

    /**
     * Roles the current actor is allowed to assign. Only a super-admin may grant
     * the Super Admin role — otherwise any user-manager could escalate privileges.
     */
    private function assignableRoles($requested): array
    {
        $roles = array_values(array_filter((array) $requested));
        if (! (auth()->user()->is_super_admin ?? false)) {
            $roles = array_values(array_diff($roles, ['Super Admin']));
        }

        return $roles;
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $input = $request->all();
        // is_super_admin is never a form field. Strip it so it can't be
        // mass-assigned to bypass the tenant scope (privilege escalation).
        unset($input['is_super_admin']);
        $input['password'] = Hash::make($request->password);
        $input['is_demo_user'] = $request->boolean('is_demo_user');
        $input['allowed_sidebar_routes'] = $request->boolean('use_default_sidebar') ? null : $request->input('sidebar_routes', []);
        $input['restricted_sidebar_routes'] = $request->boolean('is_demo_user') ? $request->input('restricted_sidebar_routes', []) : null;

        $primaryCompanyId = $request->input('company_id') ? (int) $request->input('company_id') : null;
        $companyAccess = $request->input('company_access', []);
        $companyAccess = is_array($companyAccess) ? array_map('intval', array_filter($companyAccess)) : [];
        if ($primaryCompanyId !== null) {
            $companyAccess = array_values(array_unique(array_diff($companyAccess, [$primaryCompanyId])));
        }
        $input['company_id'] = $primaryCompanyId;
        $input['company_access'] = $companyAccess;

        $user = User::create($input);
        $user->assignRole($this->assignableRoles($request->roles));

        return redirect()->route('users.index')
                ->withSuccess('New user is added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): RedirectResponse
    {
        return redirect()->route('users.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        $this->ensureCanManage($user);

        // Check Only Super Admin can update his own Profile
        if ($user->hasRole('Super Admin')){
            if($user->id != auth()->user()->id){
                abort(403, 'USER DOES NOT HAVE THE RIGHT PERMISSIONS');
            }
        }

        $sidebarMenuItems = config('sidebar.menu_items', []);
        $allowedSidebarRoutes = $user->allowed_sidebar_routes;
        $useDefaultSidebar = $allowedSidebarRoutes === null || ! is_array($allowedSidebarRoutes);
        $companies = Company::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        return view('users.edit', [
            'user' => $user,
            'roles' => Role::pluck('name')->all(),
            'userRoles' => $user->roles->pluck('name')->all(),
            'sidebarMenuItems' => $sidebarMenuItems,
            'allowedSidebarRoutes' => is_array($allowedSidebarRoutes) ? $allowedSidebarRoutes : [],
            'useDefaultSidebar' => $useDefaultSidebar,
            'companies' => $companies,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->ensureCanManage($user);

        // Only a Super Admin may edit a Super Admin account (mirrors edit()).
        // Without this the guard could be bypassed by POSTing straight to update.
        if ($user->hasRole('Super Admin') && $user->id != auth()->id()) {
            abort(403, 'USER DOES NOT HAVE THE RIGHT PERMISSIONS');
        }

        $input = $request->all();
        // Never let is_super_admin be set via the form (privilege escalation).
        unset($input['is_super_admin']);

        if (!empty($request->password)) {
            $input['password'] = Hash::make($request->password);
        } else {
            $input = $request->except('password');
            unset($input['is_super_admin']);
        }
        $input['is_demo_user'] = $request->boolean('is_demo_user');
        $input['allowed_sidebar_routes'] = $request->boolean('use_default_sidebar') ? null : $request->input('sidebar_routes', []);
        $input['restricted_sidebar_routes'] = $request->boolean('is_demo_user') ? $request->input('restricted_sidebar_routes', []) : null;

        $primaryCompanyId = $request->input('company_id') ? (int) $request->input('company_id') : null;
        $companyAccess = $request->input('company_access', []);
        $companyAccess = is_array($companyAccess) ? array_map('intval', array_filter($companyAccess)) : [];
        if ($primaryCompanyId !== null) {
            $companyAccess = array_values(array_unique(array_diff($companyAccess, [$primaryCompanyId])));
        }
        $input['company_id'] = $primaryCompanyId;
        $input['company_access'] = $companyAccess;

        $user->update($input);

        $user->syncRoles($this->assignableRoles($request->roles));

        return redirect()->back()
                ->withSuccess('User is updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->ensureCanManage($user);

        // About if user is Super Admin or User ID belongs to Auth User
        if ($user->hasRole('Super Admin') || $user->id == auth()->user()->id)
        {
            abort(403, 'USER DOES NOT HAVE THE RIGHT PERMISSIONS');
        }

        $user->syncRoles([]);
        $user->delete();
        return redirect()->route('users.index')
                ->withSuccess('User is deleted successfully.');
    }
}