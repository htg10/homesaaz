<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserFieldPermission;
use App\Services\FieldPermissionService;
use App\Services\OtpLoginService;
use App\Services\UserAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Standalone API User Controller.
 *
 * This controller intentionally does NOT extend the web UserController.
 * API user CRUD and multi-table/field permission logic lives here separately.
 */
class UserController extends Controller
{
    /**
     * Return permission-enabled database tables/modules.
     */
    public function getPermissionTables()
    {
        try {
            $blocked = [
                'Online_Tbl_Users',
                'users',
                'personal_access_tokens',
                'password_reset_tokens',
                'sessions',
                'user_field_permissions',
                'migrations',
            ];

            $tables = collect(FieldPermissionService::permissionTables())
                ->reject(fn ($table) => in_array($table, $blocked, true))
                ->values();

            return response()->json([
                'success' => true,
                'status' => true,
                'data' => $tables,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => 'Unable to load permission tables',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return fields for one selected table/module.
     */
    public function getTableFields(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'table' => 'required|string|max:150',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'status' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $table = (string) $request->input('table');
            $availableTables = FieldPermissionService::permissionTables();

            if (!in_array($table, $availableTables, true)) {
                return response()->json([
                    'success' => false,
                    'status' => false,
                    'message' => 'Invalid table/module',
                ], 404);
            }

            $blockedFields = [
                'password',
                'pwd',
                'remember_token',
                'token',
                'api_token',
                'password_hash',
            ];

            $fields = collect(FieldPermissionService::allFields($table))
                ->reject(fn ($field) => in_array(strtolower((string) $field), $blockedFields, true))
                ->values();

            return response()->json([
                'success' => true,
                'status' => true,
                'table' => $table,
                'fields' => $fields,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => 'Unable to load table fields',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return saved multi-table field permissions for a user.
     */
    public function getUserFieldPermissions($userId)
    {
        try {
            $user = User::findOrFail($userId);

            if ((int) $user->user_role === 1) {
                return response()->json([
                    'success' => true,
                    'status' => true,
                    'is_admin' => true,
                    'data' => [],
                ]);
            }

            $permissionUserId = $user->ID ?? $user->getKey();

            $permissions = UserFieldPermission::where('user_id', $permissionUserId)
                ->where('can_view', 1)
                ->orderBy('table_name')
                ->orderBy('field_name')
                ->get()
                ->groupBy('table_name')
                ->map(fn ($rows) => $rows->pluck('field_name')->values());

            return response()->json([
                'success' => true,
                'status' => true,
                'is_admin' => false,
                'data' => $permissions,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => 'Unable to load user permissions',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * API user datatable/list endpoint.
     */
    public function getDataTable(Request $request)
    {
        try {
            $query = User::query();
            $search = trim((string) $request->input('search.value', $request->input('search', '')));
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('FirstName', 'LIKE', "%{$search}%")
                        ->orWhere('LastName', 'LIKE', "%{$search}%")
                        ->orWhere('EmailID', 'LIKE', "%{$search}%");
                });
            }

            $totalRecords = User::count();
            $totalFiltered = $query->count();
            $start = max((int) $request->input('start', 0), 0);
            $length = min(max((int) $request->input('length', 10), 1), 100);
            $users = $query->orderBy('ID', 'DESC')->skip($start)->take($length)->get();
            $data = $users->map(function ($user, $key) use ($start) {
                return [
                    'DT_RowIndex' => $start + $key + 1,
                    'ID' => $user->ID,
                    'FirstName' => $user->FirstName ?? '-',
                    'LastName' => $user->LastName ?? '-',
                    'EmailID' => $user->EmailID ?: '-',
                    'MobileNo' => $user->{config('auth_otp.phone_column')} ?: '-',
                    'user_role' => $user->user_role,
                    'Role' => (int) $user->user_role === 1 ? 'Admin' : 'User',
                    'profileImage' => $user->profileImage
                        ? User::profileImageUrlOrDefault($user->profileImage)
                        : asset('assets/images/default-avatar.svg'),
                ];
            })->values();
            return response()->json([
                'success' => true,
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalFiltered,
                'data' => $data,
                'total' => $totalFiltered,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return one user.
     */
    /**
     * GET /api/users/access-options - modules (with fields) + locations for the User form.
     */
    public function accessOptions()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => UserAccessService::formOptions(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to load access options.',
            ], 500);
        }
    }

    public function getUser($id)
    {
        try {
            $user = User::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'ID' => $user->ID,
                    'FirstName' => $user->FirstName,
                    'LastName' => $user->LastName,
                    'EmailID' => $user->EmailID,
                    'MobileNo' => $user->{config('auth_otp.phone_column')},
                    'user_role' => $user->user_role,
                    'profileImage' => $user->profileImage,
                    'profileImageUrl' => User::profileImageUrl($user->profileImage),
                    'access' => UserAccessService::accessForUser($user),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }
    }

    /**
     * Create/update user and replace all selected multi-table field permissions.
     */
    public function saveUser(Request $request)
    {
        try {
            $userId = $request->input('ID', $request->input('id'));
            $isEdit = !empty($userId);

            $request->merge([
                'FirstName' => $request->input('FirstName', $request->input('name')),
                'LastName' => $request->input('LastName', ''),
                'EmailID' => $request->input('EmailID', $request->input('email')),
                'user_role' => $request->input('user_role', 0),
            ]);

            $rules = [
                'MobileNo' => [
                    'required',
                    function ($attribute, $value, $fail) use ($userId) {
                        $phone = OtpLoginService::normalizePhone((string) $value);
                        if (!OtpLoginService::isValidPhone($phone)) {
                            $fail('Enter a valid 10-digit mobile number.');
                        } elseif (OtpLoginService::phoneTaken($phone, $userId ? (int) $userId : null)) {
                            $fail('This mobile number is already used by another user.');
                        }
                    },
                ],
                'FirstName' => 'required|string|max:100',
                'LastName' => 'nullable|string|max:100',
                'EmailID' => 'nullable|email|max:255',   // optional
                'user_role' => 'required|in:0,1',
                'field_permissions' => 'nullable|array',
                'field_permissions.*' => 'nullable|array',
                'field_permissions.*.*' => 'string|max:150',
                'profileImage' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
            ];

            // New user: profile image is compulsory
            if (!$isEdit) {
                $rules['profileImage'] = 'required|image|mimes:jpg,jpeg,png,webp,gif|max:5120';
            }

            $validator = Validator::make($request->all(), $rules, [
                'EmailID.email' => 'Please enter a valid email address.',
                'profileImage.required' => 'Profile image is required.',
                'profileImage.mimes' => 'Profile image must be JPG, PNG, WEBP or GIF.',
                'profileImage.max' => 'Profile image must be less than 5 MB.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $result = DB::transaction(function () use ($request, $isEdit, $userId) {
                $data = [
                    config('auth_otp.phone_column') => OtpLoginService::normalizePhone((string) $request->input('MobileNo')),
                    'FirstName' => $request->input('FirstName'),
                    'LastName' => $request->input('LastName'),
                    'EmailID' => trim((string) $request->input('EmailID')),
                    'user_role' => $request->input('user_role'),
                    'IsActive' => $request->input('IsActive', 1),
                ];

                if (!$isEdit) {
                    // Password is no longer entered (login is OTP based). Store a random
                    // placeholder in case the Pwd column does not allow NULL.
                    $data['Pwd'] = \Illuminate\Support\Str::random(32);
                }

                if ($request->hasFile('profileImage')) {
                    $file = $request->file('profileImage');
                    $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $folder = public_path('uploads/users');

                    if (!file_exists($folder)) {
                        mkdir($folder, 0755, true);
                    }

                    $file->move($folder, $filename);
                    $data['profileImage'] = 'uploads/users/' . $filename;
                }

                if ($isEdit) {
                    $user = User::findOrFail($userId);
                    $user->update($data);
                    $message = 'User updated successfully';
                } else {
                    $data['LastLoginDate'] = now();
                    $user = User::create($data);
                    $message = 'User created successfully';
                }

                $permissionUserId = $user->ID ?? $user->getKey();

                // Replace old permissions with the current selection.
                UserFieldPermission::where('user_id', $permissionUserId)->delete();

                // Admin always has all fields; no rows are needed for admin users.
                if ((int) $user->user_role !== 1) {
                    $fieldPermissions = $request->input('field_permissions', []);
                    $availableTables = FieldPermissionService::permissionTables();
                    $blockedFields = [
                        'password',
                        'pwd',
                        'adminpwd',
                        'remember_token',
                        'token',
                        'api_token',
                        'password_hash',
                        'secret',
                        'client_secret',
                    ];

                    $rowsToInsert = [];
                    $timestamp = now();

                    foreach ($fieldPermissions as $table => $fields) {
                        if (!in_array($table, $availableTables, true)) {
                            continue;
                        }

                        $actualColumns = collect(FieldPermissionService::allFields($table))
                            ->map(fn ($field) => strtolower((string) $field))
                            ->toArray();

                        foreach (array_unique((array) $fields) as $field) {
                            $fieldLower = strtolower(trim((string) $field));

                            if (!in_array($fieldLower, $actualColumns, true)) {
                                continue;
                            }

                            if (in_array($fieldLower, $blockedFields, true)) {
                                continue;
                            }

                            $rowsToInsert[] = [
                                'user_id' => $permissionUserId,
                                'table_name' => $table,
                                'field_name' => $field,
                                'can_view' => 1,
                                'created_at' => $timestamp,
                                'updated_at' => $timestamp,
                            ];
                        }
                    }

                    if (!empty($rowsToInsert)) {
                        // SQL Server max bound parameters = 2100.
                        // 300 rows x 6 values = 1800 parameters per insert.
                        foreach (array_chunk($rowsToInsert, 300) as $chunk) {
                            DB::table('user_field_permissions')->insert($chunk);
                        }
                    }
                }

                // NEW ACCESS: modules + module fields + locations
                UserAccessService::saveForUser($user->ID ?? $user->getKey(), $request);

                return [
                    'user' => $user,
                    'message' => $message,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'user_id' => $result['user']->ID ?? $result['user']->getKey(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete user and its field permissions.
     */
    public function deleteUser($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $user = User::findOrFail($id);
                $userId = $user->ID ?? $user->getKey();

                UserFieldPermission::where('user_id', $userId)->delete();
                UserAccessService::deleteForUser($userId);
                $user->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}