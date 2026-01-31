<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        $this->middleware('permission:create-user|edit-user|delete-user', ['only' => ['index','show','getData']]);
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
        return view('users.create', [
            'roles' => Role::pluck('name')->all()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $input = $request->all();
        $input['password'] = Hash::make($request->password);

        $user = User::create($input);
        $user->assignRole($request->roles);

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
        // Check Only Super Admin can update his own Profile
        if ($user->hasRole('Super Admin')){
            if($user->id != auth()->user()->id){
                abort(403, 'USER DOES NOT HAVE THE RIGHT PERMISSIONS');
            }
        }

        return view('users.edit', [
            'user' => $user,
            'roles' => Role::pluck('name')->all(),
            'userRoles' => $user->roles->pluck('name')->all()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $input = $request->all();
 
        if(!empty($request->password)){
            $input['password'] = Hash::make($request->password);
        }else{
            $input = $request->except('password');
        }
        
        $user->update($input);

        $user->syncRoles($request->roles);

        return redirect()->back()
                ->withSuccess('User is updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
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