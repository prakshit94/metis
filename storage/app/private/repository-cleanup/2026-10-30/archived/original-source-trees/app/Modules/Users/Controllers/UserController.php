<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers;

use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Modules\Core\Controllers\Controller;
use App\Modules\Users\Models\Team;
use App\Modules\Users\Models\User;
use App\Services\EmployeeIdService;
use App\Services\UserDirectoryAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Full CRUD for Users, including role/permission syncing, account activation
 * toggle, and login history access.
 *
 * Routes are protected by operation-level permissions such as user-view,
 * user-create, user-edit, user-delete, and user-restore.
 */
class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(UserDirectoryAccess::class),
            new Middleware('permission:user-view', only: ['index', 'options', 'loginHistory']),
            new Middleware('permission:user-create', only: ['store']),
            new Middleware('permission:user-edit', only: ['update', 'toggleActive', 'syncRoles', 'syncPermissions']),
            new Middleware('permission:user-delete', only: ['destroy']),
            new Middleware('permission:user-restore', only: ['restore']),
        ];
    }

    /**
     * List users with pagination, search, and optional role filtering.
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('user-view'), 403);

        $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'role' => ['sometimes', 'nullable', 'string', 'max:255'],
            'team_id' => ['sometimes', 'nullable', 'regex:/^(global|[0-9]+)$/'],
            'office_id' => ['sometimes', 'nullable', 'integer', 'exists:offices,id'],
            'two_factor' => ['sometimes', 'nullable', 'in:enabled,disabled,authenticator,email'],
            'deleted' => ['sometimes', 'nullable', 'in:with,only,without'],
            'sort_by' => ['sometimes', 'string'], 'sort_dir' => ['sometimes', 'string'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer'],
        ]);

        $sortMap = [
            'name' => 'name',
            'firstName' => 'first_name',
            'lastName' => 'last_name',
            'email' => 'email',
            'lastActive' => 'updated_at',
            'joinDate' => 'created_at',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
        $sortBy = $sortMap[$request->input('sort_by', 'name')] ?? 'name';
        $sortDir = strtolower((string) $request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        $deletedFilter = $request->input('deleted');

        $activeSessions = DB::table('sessions')->selectRaw('1')
            ->whereColumn('sessions.user_id', 'users.id')
            ->where('last_activity', '>=', now()->subMinutes(15)->getTimestamp());
        $activeTokens = DB::table('personal_access_tokens')->selectRaw('1')
            ->whereColumn('tokenable_id', 'users.id')
            ->where('tokenable_type', User::class)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(function ($q) {
                $q->where('last_used_at', '>=', now()->subMinutes(15))
                    ->orWhere('created_at', '>=', now()->subMinutes(15));
            });

        if ($expiration = config('sanctum.expiration')) {
            $activeTokens->where('created_at', '>', now()->subMinutes((int) $expiration));
        }

        // LOB/State scoping: non-global users can only see users in their own team.
        // Resolved via model_has_roles pivot (same table used by allRoles relationship).
        $authUser = $request->user();
        $lobTeamId = $authUser?->lob_team_id;

        $users = User::query()
            ->select('users.*')
            ->selectRaw(
                '(EXISTS ('.$activeSessions->toSql().') OR EXISTS ('.$activeTokens->toSql().')) as is_online',
                [...$activeSessions->getBindings(), ...$activeTokens->getBindings()]
            )
            ->with(['allRoles', 'roles.permissions', 'permissions', 'department', 'manager', 'office:id,team_id,name,code'])
            // LOB restriction: if the requesting user belongs to a team, only
            // show users who are also assigned to that same team.
            ->when($lobTeamId, function ($q) use ($lobTeamId) {
                $lobUserIds = DB::table('model_has_roles')
                    ->where('team_id', $lobTeamId)
                    ->where('model_type', User::class)
                    ->select('model_id');
                $q->whereIn('users.id', $lobUserIds);
            })
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where(function ($inner) use ($request): void {
                    $term = '%'.$request->input('search').'%';
                    $inner->where('users.name', 'like', $term)
                        ->orWhere('users.first_name', 'like', $term)
                        ->orWhere('users.middle_name', 'like', $term)
                        ->orWhere('users.last_name', 'like', $term)
                        ->orWhere('users.email', 'like', $term)
                        ->orWhere('users.phone', 'like', $term);
                }),
            )
            ->when(
                $request->filled('role'),
                fn ($q) => $q->whereHas('allRoles', fn ($roles) => $roles->where('name', $request->input('role'))),
            )
            ->when(
                $request->filled('team_id'),
                function ($q) use ($request) {
                    $teamId = $request->input('team_id');
                    if ($teamId === 'global') {
                        $globalUserIds = DB::table('model_has_roles')
                            ->whereNull('team_id')
                            ->where('model_type', User::class)
                            ->select('model_id');
                        $q->whereIn('users.id', $globalUserIds);
                    } else {
                        $teamUserIds = DB::table('model_has_roles')
                            ->where('team_id', $teamId)
                            ->where('model_type', User::class)
                            ->select('model_id');
                        $q->whereIn('users.id', $teamUserIds);
                    }
                }
            );

        if ($request->filled('office_id')) $users->where('office_id', $request->integer('office_id'));

        if ($request->filled('two_factor')) {
            $factor = DB::table('user_two_factor_settings')->selectRaw('1')
                ->whereColumn('user_id', 'users.id')->whereNotNull('confirmed_at');
            if (in_array($request->input('two_factor'), ['email', 'authenticator'], true)) {
                $factor->where('method', $request->input('two_factor'));
            }
            $users->whereExists($factor, 'and', $request->input('two_factor') === 'disabled');
        }

        // Summary shares the directory's search, role and LOB restrictions, but
        // includes every status. Pagination and the table status do not alter it.
        $summary = $request->boolean('include_summary')
            ? $this->directorySummary(clone $users)
            : null;

        $users = $users
            ->when($deletedFilter === 'with', fn ($q) => $q->withTrashed())
            ->when($deletedFilter === 'only', fn ($q) => $q->onlyTrashed())
            ->when(
                $request->has('is_active'),
                fn ($q) => $q->where('users.is_active', $request->boolean('is_active')),
            )
            ->orderByDesc('is_online')
            ->orderBy(in_array($sortBy, ['name', 'first_name', 'last_name', 'email', 'created_at', 'updated_at']) ? "users.{$sortBy}" : $sortBy, $sortDir)
            ->orderBy('users.id')
            ->paginate($perPage);

        $userIds = $users->getCollection()->pluck('id')->toArray();

        $latestLoginHistories = [];
        if (! empty($userIds)) {
            $latestIds = DB::table('login_histories')
                ->whereIn('user_id', $userIds)
                ->where('status', 'success')
                ->groupBy('user_id')
                ->select(DB::raw('MAX(id) as id'))
                ->pluck('id');

            if ($latestIds->isNotEmpty()) {
                $latestLoginHistories = DB::table('login_histories')
                    ->whereIn('id', $latestIds)
                    ->get()
                    ->keyBy('user_id');
            }
        }

        $security = DB::table('user_two_factor_settings')->whereIn('user_id', $userIds)
            ->get(['user_id', 'confirmed_at', 'method'])->keyBy('user_id');

        $teamMap = Team::get(['id', 'name', 'code'])->keyBy('id');

        $users->getCollection()->transform(function ($user) use ($latestLoginHistories, $teamMap, $security) {
            $team = $user->lob_team_id ? $teamMap->get($user->lob_team_id) : null;
            $user->lob_team_name = $team?->name;
            $user->lob_team_code = $team?->code;
            $user->is_online = (bool) $user->getAttribute('is_online');

            $latestLogin = $latestLoginHistories[$user->id] ?? null;
            $user->last_login_at = $latestLogin ? $latestLogin->attempted_at : null;
            $user->device_type = $latestLogin ? ucfirst($latestLogin->device_type) : 'Web';
            $user->setAttribute('is_suspended', $user->isSuspended());
            $factor = $security->get($user->id);
            $user->setAttribute('two_factor_enabled', (bool) $factor?->confirmed_at);
            $user->setAttribute('two_factor_method', $factor?->confirmed_at ? $factor->method : null);

            // Expose allRoles under the standard 'roles' key for frontend compatibility
            $user->setRelation('roles', $user->allRoles);
            $user->makeHidden('allRoles');

            $user->setAttribute('lob_state_name', $team?->resolved_state_name);

            return $user;
        });

        $response = $users->toArray();
        if ($summary !== null) {
            $response['summary'] = $summary;
        }

        return response()->json($response);
    }

    /** Minimal manager choices avoid downloading full user records and histories on page load. */
    public function options(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('user-view'), 403);
        $team = $request->user()->lob_team_id;
        $users = User::select(['id', 'name', 'designation'])
            ->with(['allRoles', 'roles.permissions', 'permissions'])
            ->when($team, fn ($query) => $query->whereHas('allRoles', fn ($roles) => $roles->where('model_has_roles.team_id', $team)))
            ->orderBy('name')->orderBy('id')->get();
        $teams = Team::get(['id', 'code'])->keyBy('id');

        return response()->json(['data' => $users->map(fn ($user) => [
            'id' => $user->id, 'name' => $user->name, 'designation' => $user->designation,
            'lob_team_code' => $teams->get($user->lob_team_id)?->code,
        ])]);
    }

    /**
     * Aggregate only the permitted directory scope, using its existing online
     * detection. No user records, sessions, tokens or permissions are changed.
     */
    private function directorySummary(Builder $query): array
    {
        $monthStart = now()->startOfMonth();
        $counts = DB::query()->fromSub($query->withTrashed()->withoutEagerLoads()->toBase(), 'directory_users')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NULL AND is_active = 1 THEN 1 ELSE 0 END), 0) as active')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NULL AND is_active = 0 THEN 1 ELSE 0 END), 0) as inactive')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END), 0) as deleted')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NULL AND is_online = 1 THEN 1 ELSE 0 END), 0) as online')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NULL AND is_online = 0 THEN 1 ELSE 0 END), 0) as offline')
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NULL AND suspended_until > ? THEN 1 ELSE 0 END), 0) as suspended', [now()])
            ->selectRaw('COALESCE(SUM(CASE WHEN deleted_at IS NULL AND created_at >= ? AND created_at < ? THEN 1 ELSE 0 END), 0) as newThisMonth', [$monthStart, $monthStart->copy()->addMonth()])
            ->first();

        return array_map('intval', (array) $counts);
    }

    /**
     * Create a new user with optional roles and direct permissions.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorizeTwoFactorRequirement($request, null);
        $originalTeam = getPermissionsTeamId();
        try {
            return DB::transaction(function () use ($request) {
                $validated = $request->validated();
                $officeId = app(\App\Services\OfficeAssignment::class)->validate($request);

                // Validate privileged role/permission assignments before creating the
                // record. Previously a 403 here left behind a partially-created user.
                if (! empty($validated['roles'])) {
                    abort_unless($request->user()?->can('user-sync-roles'), 403, 'You do not have permission to sync roles.');
                    if (in_array('Super Admin', $validated['roles'], true) && ! $request->user()?->hasRole('Super Admin')) {
                        return response()->json(['message' => 'You cannot assign the Super Admin role without being a Super Admin.'], 403);
                    }
                }

                if (! empty($validated['permissions'])) {
                    abort_unless($request->user()?->can('user-sync-permissions'), 403, 'You do not have permission to sync permissions.');
                }

                $user = new User([
                    'name' => $validated['name'],
                    'first_name' => $validated['first_name'] ?? null,
                    'middle_name' => $validated['middle_name'] ?? null,
                    'last_name' => $validated['last_name'] ?? null,
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'is_active' => $validated['is_active'] ?? true,
                    'two_factor_required' => $validated['two_factor_required'] ?? false,
                    'phone' => $validated['phone'] ?? null,
                    'department_id' => $validated['department_id'] ?? null,
                    'office_id' => $officeId,
                    'manager_id' => $validated['manager_id'] ?? null,
                    'employment_type' => $validated['employment_type'] ?? 'Full-time',
                    'employee_id' => $validated['employee_id'] ?? null,
                    'photo' => $validated['photo'] ?? null,
                    'joining_date' => $validated['joining_date'] ?? null,
                    'address_line_1' => $validated['address_line_1'] ?? null,
                    'address_line_2' => $validated['address_line_2'] ?? null,
                    'village_id' => $validated['village_id'] ?? null,
                    'village_name' => $validated['village_name'] ?? null,
                    'post_office' => $validated['post_office'] ?? null,
                    'taluka' => $validated['taluka'] ?? null,
                    'district' => $validated['district'] ?? null,
                    'city' => $validated['city'] ?? null,
                    'state' => $validated['state'] ?? null,
                    'pincode' => $validated['pincode'] ?? null,
                    'date_of_birth' => $validated['date_of_birth'] ?? null,
                    'gender' => $validated['gender'] ?? null,
                    'blood_group' => $validated['blood_group'] ?? null,
                    'designation' => $validated['designation'] ?? null,
                    'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                    'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
                ]);

                app(EmployeeIdService::class)->save($user);

                if ($request->hasFile('photo_file')) {
                    $file = $request->file('photo_file');
                    $extension = $file->extension() ?: 'jpg';
                    $filename = 'user-'.$user->id.'-'.time().'.'.$extension;
                    $user->photo = asset('storage/'.$file->storeAs('users/photos', $filename, 'public'));
                    $user->saveQuietly();
                }

                if (! empty($validated['roles'])) {
                    if ($request->has('team_id')) {
                        setPermissionsTeamId($request->team_id ?: null);
                    }
                    $user->syncRoles($validated['roles']);
                }

                if (! empty($validated['permissions'])) {
                    $user->syncPermissions($validated['permissions']);
                }

                $user->load('allRoles');
                $user->setRelation('roles', $user->allRoles)->makeHidden('allRoles');

                return response()->json([
                    'message' => "User [{$user->email}] created successfully.",
                    'data' => $user,
                ], 201);
            });
        } finally {
            setPermissionsTeamId($originalTeam);
        }
    }

    /**
     * Show a single user with their roles, permissions, and recent login history.
     */
    public function show(Request $request, int|string $user): JsonResponse
    {
        abort_unless($request->user()?->id == $user || $request->user()?->can('user-view'), 403);

        $user = User::withTrashed()
            ->with(['allRoles', 'roles.permissions', 'permissions', 'department', 'manager', 'office:id,team_id,name,code'])
            ->findOrFail($user);

        $user->setRelation('roles', $user->allRoles)->makeHidden('allRoles');

        $factor = DB::table('user_two_factor_settings')->where('user_id', $user->id)->first(['confirmed_at', 'method']);
        $user->setAttribute('two_factor_enabled', (bool) $factor?->confirmed_at);
        $user->setAttribute('two_factor_method', $factor?->confirmed_at ? $factor->method : null);

        $loginHistory = ($request->user()?->id === $user->id || $request->user()?->can('audit-log-view'))
            ? $user->loginHistories()->orderByDesc('attempted_at')->limit(20)->get()
            : [];

        return response()->json([
            'data' => $user,
            'login_history' => $loginHistory,
        ]);
    }

    /**
     * Update user fields, roles, and direct permissions.
     * Only provided fields are updated (PATCH semantics).
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        if ($this->hasSuperAdminRole($user) && ! $request->user()?->hasRole('Super Admin')) {
            return response()->json(['message' => 'You cannot modify a Super Admin user.'], 403);
        }

        $this->authorizeTwoFactorRequirement($request, $user);
        $originalTeam = getPermissionsTeamId();
        try {
            return DB::transaction(function () use ($request, $user) {
                app(UserDirectoryAccess::class)->lockAdministrators();
                $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $validated = $request->validated();

                if (array_key_exists('is_active', $validated)) {
                    abort_unless($request->user()?->can('user-activate'), 403, 'You do not have permission to change account status.');

                    if (! $validated['is_active'] && $user->id === 1) {
                        return response()->json(['message' => 'The Master Admin cannot be deactivated.'], 403);
                    }

                    if (! $validated['is_active'] && $this->isLastActiveSuperAdmin($user)) {
                        return response()->json(['message' => 'Cannot deactivate the last active Super Admin user.'], 403);
                    }
                }

                if (array_key_exists('roles', $validated)) {
                    abort_unless($request->user()?->can('user-sync-roles'), 403, 'You do not have permission to sync roles.');
                    if (in_array('Super Admin', $validated['roles'], true) && ! $request->user()?->hasRole('Super Admin')) {
                        return response()->json(['message' => 'You cannot assign the Super Admin role without being a Super Admin.'], 403);
                    }
                    if ($this->hasSuperAdminRole($user) && ! in_array('Super Admin', $validated['roles'], true) && ($this->superAdminCount() <= 1 || $this->isLastActiveSuperAdmin($user))) {
                        return response()->json(['message' => 'Cannot remove the last Super Admin role.'], 403);
                    }
                }

                if (array_key_exists('permissions', $validated)) {
                    abort_unless($request->user()?->can('user-sync-permissions'), 403, 'You do not have permission to sync permissions.');
                }

                $officeId = app(\App\Services\OfficeAssignment::class)->validate($request, $user);
                $fillable = [];
                $allowedFields = [
                    'name', 'first_name', 'middle_name', 'last_name', 'email', 'is_active', 'phone',
                    'office_id', 'department_id', 'manager_id', 'employment_type', 'employee_id', 'photo', 'joining_date', 'two_factor_required',
                    'address_line_1', 'address_line_2', 'village_id', 'village_name', 'post_office',
                    'taluka', 'district', 'city', 'state', 'pincode',
                    'date_of_birth', 'gender', 'blood_group', 'designation', 'emergency_contact_name', 'emergency_contact_phone',
                ];

                foreach ($allowedFields as $field) {
                    if (array_key_exists($field, $validated)) {
                        $fillable[$field] = $validated[$field];
                    }
                }
                if ($request->has('office_id')) $fillable['office_id'] = $officeId;
                if (array_key_exists('two_factor_required', $fillable) && (bool)$fillable['two_factor_required'] !== (bool)$user->two_factor_required && !$request->attributes->get('two_factor_policy_verified')) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['security_current_password' => 'The security requirement changed. Verify your credentials and try again.']);
                }
                $requiringNow = isset($fillable['two_factor_required']) && $fillable['two_factor_required'] && !$user->two_factor_required;
                if ($requiringNow) $user->setRememberToken(null);
                if (isset($fillable['email']) && $fillable['email'] !== $user->email) {
                    $fillable['email_verified_at'] = null;
                }
                if (array_key_exists('password', $validated)) {
                    $fillable['password'] = Hash::make($validated['password']);
                    $fillable['password_changed_at'] = now();
                    $user->setRememberToken(null);
                }

                if ($request->hasFile('photo_file')) {
                    $file = $request->file('photo_file');
                    $extension = $file->extension() ?: 'jpg';
                    $filename = 'user-'.$user->id.'-'.time().'.'.$extension;
                    $fillable['photo'] = asset('storage/'.$file->storeAs('users/photos', $filename, 'public'));
                }

                if (! empty($fillable)) {
                    // A blank edit keeps an assigned ID; unassigned users receive the next ID.
                    if (array_key_exists('employee_id', $fillable) && trim((string) $fillable['employee_id']) === '') {
                        $fillable['employee_id'] = $user->employee_id;
                    }
                    $user->fill($fillable);
                    if (array_key_exists('employee_id', $fillable)) {
                        app(EmployeeIdService::class)->save($user);
                    } else {
                        $user->save();
                    }

                    // Revoke all active API tokens and web sessions when deactivating the account
                    if ((array_key_exists('is_active', $fillable) && ! $fillable['is_active']) || array_key_exists('password', $fillable) || $requiringNow) {
                        $user->tokens()->delete();
                        DB::table('sessions')->where('user_id', $user->id)->delete();
                    }
                }

                if (array_key_exists('roles', $validated)) {
                    if ($request->has('team_id')) {
                        $targetTeam = $request->input('team_id') ?: null;
                        $currentTeam = $user->allRoles()->whereNotNull('model_has_roles.team_id')->value('model_has_roles.team_id');
                        $moving = (string) $currentTeam !== (string) $targetTeam;
                        if ($moving) {
                            DB::table(config('permission.table_names.model_has_roles'))
                                ->where('model_id', $user->id)->where('model_type', get_class($user))->delete();
                            // Retain direct grants when moving LOB; the profile form does not edit them.
                            $grants = DB::table(config('permission.table_names.model_has_permissions'))
                                ->where('model_id', $user->id)->where('model_type', get_class($user));
                            $permissions = (clone $grants)->get();
                            $grants->delete();
                            foreach ($permissions as $permission) {
                                DB::table(config('permission.table_names.model_has_permissions'))->insertOrIgnore([
                                    'model_id' => $user->id, 'model_type' => get_class($user),
                                    'permission_id' => $permission->permission_id, 'team_id' => $targetTeam,
                                ]);
                            }
                        }
                        setPermissionsTeamId($targetTeam);
                        $user->unsetRelation('roles')->unsetRelation('permissions');
                    }
                    $user->syncRoles($validated['roles']);
                }

                if (array_key_exists('permissions', $validated)) {
                    $user->syncPermissions($validated['permissions']);
                }

                $user->load('allRoles');
                $user->setRelation('roles', $user->allRoles)->makeHidden('allRoles');
                $user->load('permissions');

                return response()->json([
                    'message' => "User [{$user->email}] updated successfully.",
                    'reauthentication_required' => $requiringNow && $request->user()->id === $user->id,
                    'data' => $user,
                ]);
            });
        } finally {
            setPermissionsTeamId($originalTeam);
        }
    }

    /**
     * Update user's profile photo.
     */
    public function updatePhoto(Request $request, User $user): JsonResponse
    {
        // Allow if user is updating their own photo or has edit permission
        abort_unless($request->user()?->id == $user->id || $request->user()?->can('user-edit'), 403, 'You do not have permission to update this profile photo.');

        abort_if($this->hasSuperAdminRole($user) && ! $request->user()?->hasRole('Super Admin'), 403, 'You cannot modify a Super Admin user.');

        $request->validate([
            'photo_file' => ['required', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('photo_file')) {
            $file = $request->file('photo_file');
            $extension = $file->extension() ?: 'jpg';
            $filename = 'user-'.$user->id.'-'.time().'.'.$extension;
            $user->photo = asset('storage/'.$file->storeAs('users/photos', $filename, 'public'));
            $user->saveQuietly();
        }

        return response()->json([
            'message' => 'Profile photo updated successfully.',
            'photo' => $user->photo,
        ]);
    }

    /**
     * Temporarily delete a user and revoke all their Sanctum tokens.
     * Prevents deletion of the last Super Admin.
     */
    public function destroy(Request $request, int|string $user): JsonResponse
    {
        $originalTeam = getPermissionsTeamId();
        try {
            return DB::transaction(function () use ($request, $user) {
                app(UserDirectoryAccess::class)->lockAdministrators();
                if ($user instanceof User) {
                    $user->refresh();
                }
                abort_unless($request->user()?->can('user-delete'), 403);

                $user = User::withTrashed()
                    ->with('allRoles')
                    ->findOrFail($user);

                $user->setRelation('roles', $user->allRoles)->makeHidden('allRoles');

                if ($user->id === 1) {
                    return response()->json(['message' => 'The Master Admin cannot be deleted.'], 403);
                }

                if ($user->trashed()) {
                    return response()->json([
                        'message' => "User [{$user->email}] is already temporarily deleted.",
                    ]);
                }

                if ($this->hasSuperAdminRole($user)) {
                    if (! $request->user()?->hasRole('Super Admin')) {
                        return response()->json(['message' => 'You cannot delete a Super Admin user.'], 403);
                    }
                    $superAdminCount = $this->superAdminCount();
                    if ($superAdminCount <= 1 || $this->isLastActiveSuperAdmin($user)) {
                        return response()->json([
                            'message' => 'Cannot delete the last Super Admin user.',
                        ], 403);
                    }
                }

                // Revoke all active tokens and web sessions before deleting
                $user->tokens()->delete();
                DB::table('sessions')->where('user_id', $user->id)->delete();

                $email = $user->email;
                $user->delete();

                return response()->json([
                    'message' => "User [{$email}] temporarily deleted successfully.",
                ]);
            });
        } finally {
            setPermissionsTeamId($originalTeam);
        }
    }

    /**
     * Restore a temporarily deleted user.
     */
    public function restore(Request $request, int|string $user): JsonResponse
    {
        $originalTeam = getPermissionsTeamId();
        try {
            return DB::transaction(function () use ($request, $user) {
                app(UserDirectoryAccess::class)->lockAdministrators();
                if ($user instanceof User) {
                    $user->refresh();
                }
                abort_unless($request->user()?->can('user-restore'), 403);

                $user = User::withTrashed()
                    ->with(['allRoles', 'roles.permissions', 'permissions', 'department', 'manager', 'office:id,team_id,name,code'])
                    ->findOrFail($user);

                abort_if($this->hasSuperAdminRole($user) && ! $request->user()?->hasRole('Super Admin'), 403, 'You cannot modify a Super Admin user.');

                $user->setRelation('roles', $user->allRoles)->makeHidden('allRoles');

                if (! $user->trashed()) {
                    return response()->json([
                        'message' => "User [{$user->email}] is not deleted.",
                        'data' => $user,
                    ]);
                }

                $user->restore();
                $user->load('allRoles');
                abort_if($this->hasSuperAdminRole($user) && ! $request->user()?->hasRole('Super Admin'), 403, 'You cannot modify a Super Admin user.');

                $user->setRelation('roles', $user->allRoles)->makeHidden('allRoles');
                $user->load('permissions');

                return response()->json([
                    'message' => "User [{$user->email}] restored successfully.",
                    'data' => $user,
                ]);
            });
        } finally {
            setPermissionsTeamId($originalTeam);
        }
    }

    /**
     * Permanently delete a user.
     */
    public function forceDelete(Request $request, int|string $user): JsonResponse
    {
        $originalTeam = getPermissionsTeamId();
        try {
            return DB::transaction(function () use ($request, $user) {
                app(UserDirectoryAccess::class)->lockAdministrators();
                if ($user instanceof User) {
                    $user->refresh();
                }
                abort_unless($request->user()?->can('user-permanent-delete'), 403);

                $user = User::withTrashed()
                    ->with('allRoles')
                    ->findOrFail($user);

                $user->setRelation('roles', $user->allRoles)->makeHidden('allRoles');

                if ($user->id === 1) {
                    return response()->json(['message' => 'The Master Admin cannot be permanently deleted.'], 403);
                }

                if ($this->hasSuperAdminRole($user)) {
                    if (! $request->user()?->hasRole('Super Admin')) {
                        return response()->json(['message' => 'You cannot permanently delete a Super Admin user.'], 403);
                    }
                    $superAdminCount = $this->superAdminCount();
                    if ($superAdminCount <= 1 || $this->isLastActiveSuperAdmin($user)) {
                        return response()->json([
                            'message' => 'Cannot permanently delete the last Super Admin user.',
                        ], 403);
                    }
                }

                $email = $user->email;
                $user->tokens()->delete();
                DB::table('sessions')->where('user_id', $user->id)->delete();
                $user->forceDelete();

                return response()->json([
                    'message' => "User [{$email}] permanently deleted successfully.",
                ]);
            });
        } finally {
            setPermissionsTeamId($originalTeam);
        }
    }

    // ─── Specialised Actions ──────────────────────────────────────────────────

    /**
     * Toggle the user's is_active status.
     * Automatically revokes all tokens when deactivating.
     */
    public function toggleActive(Request $request, User $user): JsonResponse
    {
        $originalTeam = getPermissionsTeamId();
        try {
            return DB::transaction(function () use ($request, $user) {
                app(UserDirectoryAccess::class)->lockAdministrators();
                if ($user instanceof User) {
                    $user->refresh();
                }
                abort_unless($request->user()?->can('user-activate'), 403);

                if ($user->id === 1) {
                    return response()->json(['message' => 'The Master Admin cannot be deactivated.'], 403);
                }

                if ($this->hasSuperAdminRole($user) && ! $request->user()?->hasRole('Super Admin')) {
                    return response()->json(['message' => 'You cannot modify a Super Admin user.'], 403);
                }

                $newState = ! $user->is_active;

                if (! $newState && $this->isLastActiveSuperAdmin($user)) {
                    return response()->json(['message' => 'Cannot deactivate the last active Super Admin user.'], 403);
                }

                $user->update(['is_active' => $newState]);

                // Revoke all active API tokens and web sessions when deactivating the account
                if (! $newState) {
                    $user->tokens()->delete();
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                }

                $status = $newState ? 'activated' : 'deactivated';
                $displayName = trim(implode(' ', array_filter([$user->first_name, $user->last_name]))) ?: $user->name;

                return response()->json([
                    'message' => "User \"{$displayName}\" ({$user->email}) has been {$status} successfully.",
                    'is_active' => $newState,
                    'user_id' => $user->id,
                    'status' => $status,
                ]);
            });
        } finally {
            setPermissionsTeamId($originalTeam);
        }
    }

    /** Lift a temporary login suspension without changing the active account flag. */
    public function unsuspend(Request $request, User $user): JsonResponse
    {
        abort_unless(
            $request->user()?->can('user-activate') || $request->user()?->can('user-edit'),
            403
        );

        if ($this->hasSuperAdminRole($user) && ! $request->user()?->hasRole('Super Admin')) {
            return response()->json(['message' => 'You cannot modify a Super Admin user.'], 403);
        }

        if ($user->suspended_until === null) {
            return response()->json([
                'message' => 'This user has no suspension timer to clear.',
                'is_suspended' => false,
                'suspended_until' => null,
                'is_active' => $user->is_active,
            ]);
        }

        $user->unsuspend();
        // Reset this account's failed-login threshold so the next valid login
        // does not immediately recreate the same suspension. Keep the shared
        // IP limiter intact so this action cannot remove protection for others.
        RateLimiter::clear('login_email:'.Str::lower($user->email));

        $displayName = trim(implode(' ', array_filter([$user->first_name, $user->last_name]))) ?: $user->name;

        return response()->json([
            'message' => "The suspension for user \"{$displayName}\" ({$user->email}) has been lifted.",
            'is_suspended' => false,
            'suspended_until' => null,
            'is_active' => $user->is_active,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Replace all roles on the user in a single operation.
     */
    public function syncRoles(Request $request, User $user): JsonResponse
    {
        $originalTeam = getPermissionsTeamId();
        try {
            return DB::transaction(function () use ($request, $user) {
                app(UserDirectoryAccess::class)->lockAdministrators();
                if ($user instanceof User) {
                    $user->refresh();
                }
                abort_unless($request->user()?->can('user-sync-roles'), 403);

                $request->validate([
                    'roles' => ['required', 'array'],
                    'roles.*' => ['string', 'exists:roles,name'],
                    'team_id' => ['sometimes', 'nullable', 'integer', 'exists:teams,id'],
                ]);

                abort_if($this->hasSuperAdminRole($user) && ! $request->user()?->hasRole('Super Admin'), 403, 'You cannot modify a Super Admin user.');

                $rolesToAssign = $request->input('roles');
                if (in_array('Super Admin', $rolesToAssign) && ! $request->user()?->hasRole('Super Admin')) {
                    return response()->json(['message' => 'You cannot assign the Super Admin role without being a Super Admin.'], 403);
                }

                if ($this->hasSuperAdminRole($user) && ! in_array('Super Admin', $rolesToAssign, true) && ($this->superAdminCount() <= 1 || $this->isLastActiveSuperAdmin($user))) {
                    return response()->json(['message' => 'Cannot remove the last Super Admin role.'], 403);
                }

                if ($request->has('team_id')) {
                    setPermissionsTeamId($request->team_id ?: null);
                }

                $user->unsetRelation('roles');
                $user->syncRoles($rolesToAssign);
                $user->load('allRoles');
                $user->setRelation('roles', $user->allRoles)->makeHidden('allRoles');

                return response()->json([
                    'message' => "Roles synced for user [{$user->email}].",
                    'data' => $user->roles,
                ]);
            });
        } finally {
            setPermissionsTeamId($originalTeam);
        }
    }

    /**
     * Replace all direct permissions on the user in a single operation.
     *
     * Note: This does not affect permissions inherited via roles.
     */
    public function syncPermissions(Request $request, User $user): JsonResponse
    {
        $originalTeam = getPermissionsTeamId();
        try {
            return DB::transaction(function () use ($request, $user) {
                app(UserDirectoryAccess::class)->lockAdministrators();
                if ($user instanceof User) {
                    $user->refresh();
                }
                abort_unless($request->user()?->can('user-sync-permissions'), 403);

                if ($this->hasSuperAdminRole($user) && ! $request->user()?->hasRole('Super Admin')) {
                    return response()->json(['message' => 'You cannot modify a Super Admin user.'], 403);
                }

                $request->validate([
                    'permissions' => ['required', 'array'],
                    'permissions.*' => ['string', 'exists:permissions,name'],
                ]);

                setPermissionsTeamId($user->allRoles()->whereNotNull('model_has_roles.team_id')->value('model_has_roles.team_id'));
                $user->unsetRelation('permissions');
                $user->syncPermissions($request->input('permissions'));
                $user->load('permissions');

                return response()->json([
                    'message' => "Direct permissions synced for user [{$user->email}].",
                    'data' => $user->permissions,
                ]);
            });
        } finally {
            setPermissionsTeamId($originalTeam);
        }
    }

    /**
     * Return the full login history for a user (paginated).
     * Useful for the audit log screen in the admin panel.
     */
    public function loginHistory(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->can('audit-log-view'), 403);

        $history = $user->loginHistories()
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', $request->input('status')),
            )
            ->orderByDesc('attempted_at')
            ->paginate(min(max((int) $request->input('per_page', 25), 1), 100));

        return response()->json($history);
    }

    private function authorizeTwoFactorRequirement(Request $request, ?User $target): void
    {
        if (!$request->has('two_factor_required')) return;
        abort_unless($request->user()?->can('user-security-requirement'), 403, 'You do not have permission to manage user two-step login requirements.');
        $required = $request->boolean('two_factor_required');
        if ($required === (bool) $target?->two_factor_required) return;
        $request->validate(['security_current_password' => 'required|string|max:1024', 'security_code' => 'nullable|string|max:64']);
        $proof = Request::create($request->path(), 'POST', ['current_password' => $request->input('security_current_password')]);
        $proof->setUserResolver(fn () => $request->user());
        $proof->server->set('REMOTE_ADDR', $request->ip());
        if ($request->hasSession()) $proof->setLaravelSession($request->session());
        $twoFactor = app(\App\Services\TwoFactorService::class);
        $twoFactor->reauthenticate($proof);
        $twoFactor->authorizeSensitiveChange($request->user(), (string)$request->input('security_code', ''), $proof, 'user_security');
        $request->attributes->set('two_factor_policy_verified', true);
    }

    private function hasSuperAdminRole(User $user): bool
    {
        return $user->allRoles()->where('name', 'Super Admin')->exists();
    }

    private function superAdminCount(): int
    {
        return User::whereHas('allRoles', fn ($query) => $query->where('name', 'Super Admin'))->count();
    }

    private function isLastActiveSuperAdmin(User $user): bool
    {
        return $user->is_active
            && $this->hasSuperAdminRole($user)
            && User::where('is_active', true)
                ->whereHas('allRoles', fn ($query) => $query->where('name', 'Super Admin'))
                ->count() <= 1;
    }
}
