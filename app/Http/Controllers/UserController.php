<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportFilter;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * @return Response
     */
    public function index()
    {
        $users = User::with('reports', 'roles')->get();
        $roles = Role::all();
        $permissions = Permission::all();
        $reports = Report::all();
        $technicalUsers = User::whereHas('roles', function ($query) {
            $query->whereRaw('LOWER(name) IN (?, ?)', ['tecnico', 'técnico']);
        })
            ->select(['id', 'name', 'username', 'email', 'codigo_vendedor'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'permissions' => $permissions,
            'reports' => $reports,
            'technicalUsers' => $technicalUsers,
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'type' => $request->type,
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'cedula' => $request->cedula ?: null,
                'codigo_vendedor' => $request->codigo_vendedor ?: null,
                'password' => bcrypt($request->password),
        ]);
            $user->reports()->sync($request->reports);
            $user->syncPermissions($request->permissions);
            $user->syncRoles($request->roles);

            $selectedRoles = collect($request->roles ?? [])->map(function ($role) {
                return mb_strtolower(trim($role));
            });

            if ($selectedRoles->contains('asesor')) {
                $technicalUserIds = collect($request->technical_users ?? [])
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if (!empty($technicalUserIds)) {
                    User::whereIn('id', $technicalUserIds)
                        ->whereHas('roles', function ($query) {
                            $query->whereRaw('LOWER(name) IN (?, ?)', ['tecnico', 'técnico']);
                        })
                        ->update(['advisor_id' => $user->id]);
                }
            }

            DB::commit();

            $users = User::with('reports', 'roles')->get();

            return response()->json($users, 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = User::find($id);

            $user->update([
                'type' => $request->type,
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'cedula' => $request->cedula ?: null,
                'codigo_vendedor' => $request->codigo_vendedor ?: null,
            ]);

            if ($request->change_password) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            $user->syncPermissions($request->permissions);
            $user->syncRoles($request->roles);

            $selectedRoles = collect($request->roles ?? [])->map(function ($role) {
                return mb_strtolower(trim($role));
            });

            if ($selectedRoles->contains('asesor')) {
                // Solo sincronizar técnicos cuando el payload incluye el campo.
                if ($request->exists('technical_users')) {
                    $technicalUserIds = collect($request->technical_users ?? [])
                        ->map(function ($id) {
                            return (int) $id;
                        })
                        ->filter(function ($id) {
                            return $id > 0;
                        })
                        ->unique()
                        ->values()
                        ->all();

                    User::where('advisor_id', $user->id)
                        ->whereHas('roles', function ($query) {
                            $query->whereRaw('LOWER(name) IN (?, ?)', ['tecnico', 'técnico']);
                        })
                        ->when(!empty($technicalUserIds), function ($query) use ($technicalUserIds) {
                            $query->whereNotIn('id', $technicalUserIds);
                        })
                        ->update(['advisor_id' => null]);

                    if (!empty($technicalUserIds)) {
                        User::whereIn('id', $technicalUserIds)
                            ->whereHas('roles', function ($query) {
                                $query->whereRaw('LOWER(name) IN (?, ?)', ['tecnico', 'técnico']);
                            })
                            ->update(['advisor_id' => $user->id]);
                    }
                }
            } else {
                User::where('advisor_id', $user->id)
                    ->whereHas('roles', function ($query) {
                        $query->whereRaw('LOWER(name) IN (?, ?)', ['tecnico', 'técnico']);
                    })
                    ->update(['advisor_id' => null]);
            }

            DB::commit();

            $users = User::with('reports', 'roles')->get();

            return response()->json($users, 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * @return JsonResponse
     */
    public function update_reports(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = User::findOrFail($request->user_id);
            $user->reports()->sync($request->reports);

            DB::commit();
            return response()->json('success', 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * @return JsonResponse
     */
    public function update_filters(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = User::with('reports.filters')->find($request->user_id);
            $user->reports()->find($request->report_id)->filters()->syncWithPivotValues($request->filters, ['user_id' => $request->user_id]);

            DB::commit();

            return response()->json('success', 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * @return JsonResponse
     */
    public function destroy($id)
    {
        User::destroy($id);

        $users = User::with('reports', 'roles')->get();

        return response()->json($users, 200);
    }

    /**
     * @return Response
     */
    public function show($id)
    {
        $user = User::with('roles', 'permissions', 'reports', 'technicalUsers.roles')
            ->find($id);

        $roles = Role::all();
        $permissions = Permission::all();
        $reports = Report::all();
        $filters = ReportFilter::all();
        $technicalUsers = User::whereHas('roles', function ($query) {
            $query->whereRaw('LOWER(name) IN (?, ?)', ['tecnico', 'técnico']);
        })
            ->select(['id', 'name', 'username', 'email', 'codigo_vendedor', 'advisor_id'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Users/Show', [
            'user' => $user,
            'roles' => $roles,
            'permissions' => $permissions,
            'reports' => $reports,
            'filters' => $filters,
            'technicalUsers' => $technicalUsers,
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function set_default(Request $request)
    {
        $user = User::find($request->user_id);

        $user->reports()->updateExistingPivot($request->report_id, [
            'show' => $request->state,
        ]);

        return response()->json('success', 200);
    }
}
