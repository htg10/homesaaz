<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserFieldPermission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\FieldPermissionService;
use App\Services\UserAccessService;
use App\Services\OtpLoginService;

class UserController extends Controller
{
    public function list()
    {
        return view('user.list');
    }

    public function getPermissionTables()
    {
        try {
            $blocked = [
                'Online_Tbl_Users', 'Online_Tbl_AdminUsers', 'users', 'personal_access_tokens',
                'password_reset_tokens', 'sessions', 'user_field_permissions', 'migrations'
            ];

            $tables = collect(FieldPermissionService::permissionTables())
                ->reject(fn ($table) => in_array($table, $blocked, true))
                ->values();

            return response()->json([
                'status' => true,
                'data' => $tables,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to load tables',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getTableFields(Request $request)
    {
        $request->validate(['table' => 'required|string|max:150']);
        $table = (string) $request->input('table');
        $availableTables = FieldPermissionService::permissionTables();

        if (!in_array($table, $availableTables, true)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid table/module',
            ], 404);
        }

        $blockedFields = ['password','pwd','adminpwd','remember_token','token','api_token','password_hash','secret','client_secret'];
        $fields = collect(FieldPermissionService::allFields($table))
            ->reject(fn ($field) => in_array(strtolower((string) $field), $blockedFields, true))
            ->values();

        return response()->json([
            'status' => true,
            'table' => $table,
            'fields' => $fields,
        ]);
    }

    public function getUserFieldPermissions($userId)
    {
        try {
            $user = User::findOrFail($userId);
            if ((int) $user->user_role === 1) {

                return response()->json([
                    'status' => true,
                    'is_admin' => true,
                    'data' => []
                ]);
            }
            $permissions = UserFieldPermission::where('user_id',$user->ID ?? $user->getKey())->where('can_view', 1)
                ->orderBy('table_name')
                ->orderBy('field_name')
                ->get()
                ->groupBy('table_name')
                ->map(function ($rows) {return $rows->pluck('field_name')->values();});
            return response()->json([
                'status' => true,
                'is_admin' => false,
                'data' => $permissions
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Unable to load user permissions'
            ], 404);
        }
    }

    public function getDataTable(Request $request)
    {
        try {
            $query = User::query();
            if (!empty($request->input('search.value'))) {
                $search = trim($request->input('search.value'));
                $query->where(function ($q) use ($search) {
                    $q->where('FirstName','LIKE',"%{$search}%")
                    ->orWhere('LastName','LIKE',"%{$search}%")
                    ->orWhere('EmailID','LIKE',"%{$search}%");
                });
            }
            $totalRecords = User::count();
            $totalFiltered = $query->count();
            $start = max((int) $request->input('start', 0),0);
            $length = (int) $request->input('length',10);
            $length = min(max($length, 1),100);
            $users = $query->orderBy('ID', 'DESC')->skip($start)->take($length)->get();
            $data = $users->map(
                function ($user, $key) use ($start) {
                    return [
                        'DT_RowIndex' => $start + $key + 1,
                        'ID' =>$user->ID,
                        'FirstName' =>$user->FirstName ?? '-',
                        'LastName' =>$user->LastName ?? '-',
                        'EmailID' =>$user->EmailID ?: '-',
                        'MobileNo' =>$user->{config('auth_otp.phone_column')} ?: '-',
                        'user_role' =>$user->user_role,
                        'Role' =>$user->user_role == 1 ? 'Admin' : 'User',
                        'profileImage' =>User::profileImageUrlOrDefault($user->profileImage)
                    ];
                }
            );

            return response()->json([
                'success' => true,
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' =>$totalRecords,
                'recordsFiltered' =>$totalFiltered,
                'data' =>$data,
                'total' =>$totalFiltered
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'draw' => intval(
                    $request->input('draw', 1)
                ),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' =>
                    $e->getMessage()
            ], 500);
        }
    }


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
                    'ID' =>
                        $user->ID,
                    'FirstName' =>
                        $user->FirstName,
                    'LastName' =>
                        $user->LastName,
                    'EmailID' =>
                        $user->EmailID,
                    'MobileNo' =>
                        $user->{config('auth_otp.phone_column')},
                    'user_role' =>
                        $user->user_role,
                    'profileImage' =>
                        $user->profileImage,
                    'profileImageUrl' =>
                        User::profileImageUrl($user->profileImage),
                    'access' =>
                        UserAccessService::accessForUser($user)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }

    public function saveUser(Request $request)
    {
        try {
            $userId = $request->input(
                'ID',
                $request->input('id')
            );
            $isEdit = !empty($userId);
            $firstName = $request->input(
                'FirstName',
                $request->input('name')
            );
            $lastName = $request->input(
                'LastName',
                ''
            );
            $email = $request->input(
                'EmailID',
                $request->input('email')
            );
            $password = $request->input(
                'Pwd',
                $request->input('password')
            );
            $userRole = $request->input(
                'user_role',
                0
            );
            $request->merge([
                'FirstName' =>$firstName,
                'LastName' =>$lastName,
                'EmailID' =>$email,
                'Pwd' =>$password,
                'user_role' =>$userRole
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
                'FirstName' =>'required|string|max:100',
                'LastName' =>'nullable|string|max:100',
                'EmailID' =>'nullable|email|max:255',
                'user_role' =>'required|in:0,1',
                'field_permissions' =>'nullable|array',
                'field_permissions.*' =>'nullable|array',
                'field_permissions.*.*' =>'string|max:150'
            ];
            if ($isEdit) {
                $rules['profileImage'] ='nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120';
            } else {
                $rules['profileImage'] ='required|image|mimes:jpg,jpeg,png,webp,gif|max:5120';
            }
            $validator = Validator::make(
                $request->all(),
                $rules,
                [
                    'EmailID.email' => 'Please enter a valid email address.',
                    'profileImage.required' => 'Profile image is required.',
                    'profileImage.image' => 'Profile image must be an image file.',
                    'profileImage.mimes' => 'Profile image must be JPG, PNG, WEBP or GIF.',
                    'profileImage.max' => 'Profile image must be less than 5 MB.',
                ]
            );


            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' =>
                        $validator->errors()
                ], 422);
            }
            $result = DB::transaction(
                function () use (
                    $request,
                    $isEdit,
                    $userId
                ) {

                    $data = [
                        config('auth_otp.phone_column') =>
                            OtpLoginService::normalizePhone((string) $request->input('MobileNo')),
                        'FirstName' =>$request->input('FirstName'),
                        'LastName' =>$request->input('LastName'),
                        'EmailID' =>trim((string) $request->input('EmailID')),
                        'user_role' =>$request->input('user_role'),
                        'IsActive' =>$request->input('IsActive',1)
                    ];
                    if (
                        $request->filled('Pwd')
                    ) {
                        $data['Pwd'] =
                            $request->input(
                                'Pwd'
                            );
                    }
                    if (!$isEdit && !isset($data['Pwd'])) {
                        // Password field removed from the form. Login is OTP based, so store a
                        // random placeholder in case the Pwd column does not allow NULL.
                        $data['Pwd'] = \Illuminate\Support\Str::random(32);
                    }
                    if (
                        $request->hasFile(
                            'profileImage'
                        )
                    ) {

                        $file =
                            $request->file(
                                'profileImage'
                            );


                        $filename =
                            time()
                            . '_'
                            . uniqid()
                            . '.'
                            . $file
                                ->getClientOriginalExtension();


                        $folder =
                            public_path(
                                'uploads/users'
                            );


                        if (
                            !file_exists($folder)
                        ) {

                            mkdir(
                                $folder,
                                0755,
                                true
                            );
                        }


                        $file->move(
                            $folder,
                            $filename
                        );


                        $data['profileImage'] =
                            'uploads/users/'
                            . $filename;

                        // Edit: remove the old image file
                        if ($isEdit) {
                            $old = User::whereKey($userId)->value('profileImage');
                            if ($old && str_starts_with($old, 'uploads/users/') && is_file(public_path($old))) {
                                @unlink(public_path($old));
                            }
                        }
                    }


                    /*
                     * CREATE / UPDATE USER
                     */
                    if ($isEdit) {

                        $user =
                            User::findOrFail(
                                $userId
                            );


                        $user->update($data);


                        $message =
                            'User updated successfully';

                    } else {

                        $data['LastLoginDate'] =
                            now();


                        /*
                         * IMPORTANT:
                         * Earlier code did:
                         *
                         * User::create($data);
                         *
                         * but did not store created user.
                         *
                         * We need $user to save
                         * permissions.
                         */
                        $user =
                            User::create($data);


                        $message =
                            'User created successfully';
                    }


                    /*
                     * Resolve actual user ID.
                     */
                    $permissionUserId =
                        $user->ID
                        ?? $user->getKey();


                    /*
                     |--------------------------------------------------------------------------
                     | DELETE OLD FIELD PERMISSIONS
                     |--------------------------------------------------------------------------
                     |
                     | This makes Update easy:
                     | remove old → insert current selection.
                     |
                     */

                    UserFieldPermission::where(
                        'user_id',
                        $permissionUserId
                    )->delete();


                    /*
                     |--------------------------------------------------------------------------
                     | ADMIN
                     |--------------------------------------------------------------------------
                     |
                     | Admin does NOT need permission rows.
                     | Admin will get all fields through
                     | FieldPermissionService.
                     |
                     */

                    if (
                        (int) $user->user_role !== 1
                    ) {

                        $fieldPermissions =
                            $request->input(
                                'field_permissions',
                                []
                            );


                        /*
                         * Prepare bulk insert.
                         */
                        $rowsToInsert = [];


                        foreach (
                            $fieldPermissions
                            as $table => $fields
                        ) {

                            // Actual DB table OR configured virtual module (GRN/GateEntry).
                            $availableTables = FieldPermissionService::permissionTables();
                            if (!in_array($table, $availableTables, true)) {
                                continue;
                            }

                            // Fields are discovered dynamically from SQL Server. Virtual
                            // stored-procedure modules use config/field_permissions.php.
                            $actualColumns = collect(FieldPermissionService::allFields($table))
                                ->map(fn ($field) => strtolower((string) $field))
                                ->toArray();


                            /*
                             * Never allow sensitive fields.
                             */
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

                                'secret',

                                'client_secret'
                            ];


                            foreach (
                                array_unique($fields)
                                as $field
                            ) {

                                $fieldLower =
                                    strtolower(
                                        trim($field)
                                    );


                                /*
                                 * Field must exist.
                                 */
                                if (
                                    !in_array(
                                        $fieldLower,
                                        $actualColumns,
                                        true
                                    )
                                ) {

                                    continue;
                                }


                                /*
                                 * Sensitive field blocked.
                                 */
                                if (
                                    in_array(
                                        $fieldLower,
                                        $blockedFields,
                                        true
                                    )
                                ) {

                                    continue;
                                }


                                $rowsToInsert[] = [

                                    'user_id' =>
                                        $permissionUserId,

                                    'table_name' =>
                                        $table,

                                    'field_name' =>
                                        $field,

                                    'can_view' =>
                                        1,

                                    'created_at' =>
                                        now(),

                                    'updated_at' =>
                                        now()
                                ];
                            }
                        }


                        /*
                         * Bulk insert permissions.
                         *
                         * Faster than:
                         * UserFieldPermission::create()
                         * inside every loop.
                         */
                        if (
                            !empty($rowsToInsert)
                        ) {

                            $chunkSize = 300;

                            foreach (
                                array_chunk(
                                    $rowsToInsert,
                                    $chunkSize
                                )
                                as $chunk
                            ) {

                                DB::table(
                                    'user_field_permissions'
                                )->insert($chunk);
                            }
                        }
                    }


                    /*
                     | NEW ACCESS: modules + module fields + locations
                     */
                    UserAccessService::saveForUser(
                        $permissionUserId,
                        $request
                    );


                    return [

                        'user' =>
                            $user,

                        'message' =>
                            $message
                    ];
                }
            );


            return response()->json([

                'success' => true,

                'message' =>
                    $result['message'],

                'user_id' =>
                    $result['user']->ID
                    ?? $result['user']->getKey()

            ]);


        } catch (\Exception $e) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Error: '
                    . $e->getMessage()

            ], 500);
        }
    }

    public function deleteUser($id)
    {
        try {
            DB::transaction(
                function () use ($id) {
                    $user =User::findOrFail($id);
                    $userId = $user->ID?? $user->getKey();
                    UserFieldPermission::where('user_id',$userId)->delete();
                    UserAccessService::deleteForUser($userId);
                    $user->delete();
                }
            );
            return response()->json([
                'success' => true,
                'message' =>
                    'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' =>'Delete failed: '. $e->getMessage()
            ], 500);
        }
    }
}