<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Carbon;

use App\Helpers\UsersHelper;

use App\Models\User;
use App\Models\Roles;


class AuthController extends Controller
{

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth:api', ['except' => ['login']]);
    }
    public function actionTest()
    {
        return response()->json(["hola" => "mundo"], 201);
    }


    private function getRolesInfo(User $user)
    {
        $rolesWithPermissions = [];
        $roleList = $user->getRoleNames();
        foreach ($roleList as $roleName) {
            // Obtener la instancia del rol
            $role = Role::findByName($roleName);

            // Obtener los permisos del rol
            $permissions = $role->permissions; // Colección de permisos

            // Agregar al array
            $rolesWithPermissions[] = [
                'role' => $roleName,
                'permissions' => $permissions->pluck('name'), // Extraer solo los nombres de permisos
            ];
        }
        return $rolesWithPermissions;
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function actionLogin(Request $request)
    {




        $credentials = $request->only('email', 'password');


        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email|max:255',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), Response::HTTP_BAD_REQUEST);
            }
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
            }

            // Get the authenticated user.
            $user = auth()->user();

            $rolesWithPermissions = $this->getRolesInfo($user);

            // (optional) Attach the role to the token.
            $token = $this->respondWithToken(JWTAuth::claims(['roles' => $user->getRoleNames(),'permissions'=> $user->getPermissionsViaRoles()->pluck('name') ,'permission-roles' => $rolesWithPermissions])->fromUser($user));

            return response()->json($token);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function actionRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|string|exists:roles,name'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), Response::HTTP_BAD_REQUEST);
        }

        $user = User::create([
            'name' => ucwords(strtolower($request->get('name'))),
            'email' => strtolower($request->get('email')),
            'password' => UsersHelper::setPasswordEncrypt($request->get('password')),
        ]);

        $user->assignRole($request->get('role'));
        $token = $this->respondWithToken(JWTAuth::fromUser($user));

        $usr = $this->getOutputUserInfoNormalized($user);

        return response()->json(['user' => $usr, "token" => $token], Response::HTTP_CREATED);
    }

    private function getOutputUserInfoNormalized(User $user,$payload=null)
    {
        $expDate = null;

        if ($payload!==null && !empty($payload->get('exp'))) {
            $expDate = (Carbon::createFromTimestamp($payload->get('exp')))->format('Y-m-d H:i:s');
        }

        $rolesWithPermissions = $this->getRolesInfo($user);
        $usr['name'] = $user->name;
        $usr['email'] = $user->email;
        $usr['created_at'] = $user->created_at;
        $usr['updated_at'] = $user->updated_at;
        if (!empty($expDate)) {
            $usr['token_expired_at'] = $expDate;
        }
        $usr['email_verified_at'] = $user->email_verified_at;

        $usr['roles'] = $user->getRoleNames();
        $usr['permissions'] = $user->getPermissionsViaRoles()->pluck('name');
        $usr['permission-roles'] = $rolesWithPermissions;
        return $usr;
    }

    // Get authenticated user
    public function actionMe()
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Invalid token'], Response::HTTP_BAD_REQUEST);
        }

        $payload = JWTAuth::parseToken()->getPayload();
        $usr = $this->getOutputUserInfoNormalized($user,$payload);


        return response()->json($usr);
    }

    /**
     * Refresh and creates a new token
     */
    public function actionRefresh() {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Invalid token'], Response::HTTP_BAD_REQUEST);
        }

        $token = JWTAuth::refresh(true,true);
        $token = $this->respondWithToken($token);

        //$usr = $this->getOutputUserInfoNormalized($user,$payload);


        return response()->json($token);
    }

    /**
     * Function for Logout
     */
    public function actionLogout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json(['message' => 'Successfully logged out'], Response::HTTP_ACCEPTED);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Invalid token'], Response::HTTP_BAD_REQUEST);

        }

    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {

        return  [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ];
    }

    public function actionGetRoles() {
        $roles = Roles::where('guard_name','api')->select(['id','name'])->orderBy('name')->get();
        return response()->json($roles, 200);

    }
}
