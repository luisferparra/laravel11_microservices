<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtMiddleware
{


    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $roles_permissions = '', $roles = '', $permissions = ''): Response
    {

        try {
            $payload = JWTAuth::parseToken()->getPayload();
            $userRoles = $payload->get('roles'); // Asumiendo que los roles están en el token
            $userPermissions = $payload->get('permissions');
            $permissionRoles = $payload->get('permission-roles');
            $userId = $payload->get('sub');
            $expirationDate = (int)$payload->get('exp');
            $now = now()->timestamp;
            if ($now > $expirationDate) {
                return response()->json(['now' => $now, 'expiration' => $expirationDate, 'payload' => $payload, 'roles' => $userRoles, 'error' => 'Token Expired or Not Valid'], 401);
            }
            if (!$payload) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }
            // If user is a SuperAdmin... no more to say your highness
            if (!empty($userRoles) && is_array($userRoles) && in_array("SuperAdmin",$userRoles)) {
                return $next($request);

            }
            if (!empty($roles) && $roles != "-" && $this->hasRequiredRole($userRoles, $roles)) {

                return $next($request);

                // return response()->json(['roles' => $userRoles, 'required' => $roles, 'error' => 'Access Denied: Assigned Role has not required rights'], 403);;
            }
            if (!empty($permissions) && $permissions != "-" && $this->hasRequiredRole($userPermissions, $permissions)) {

                return $next($request);
                //return response()->json(['error' => 'Access Denied: Assigned Permissions has not required rights'], 403);;
            }
            if (!empty($roles_permissions) && $roles_permissions != "-" && $this->hasRequiredRolePermission($permissionRoles, $roles_permissions)) {

                return $next($request);
            }
            if ((empty($roles_permissions) || $roles_permissions == "-") &&
                (empty($roles) || $roles == "-") &&
                (empty($permissions) || $permissions == "-")
            ) {

                return $next($request);
            }
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Token has expired'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Token is invalid'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token not found'], 401);
        }
        return response()->json(['error' => 'Not Authorized'], 401);
    }

    private function hasRequiredRolePermission($payloadInput, $middlewareAllowed)
    {
        /** This can come as:
         * CompanyAdmin::read|execute||SuperAdmin::write
         * */
        $blockArr = explode("||", $middlewareAllowed);
        foreach ($blockArr as $block) {
            $blockRule = explode(":", $block);
            // return response()->json(['roles_peraaaaaamissions' => count($blockRule),'error' => 'treeesssss not authenticated'], 401);

            if (count($blockRule) != 2) {
                continue;
            }
            $role = $blockRule[0];

            $permissionList = $blockRule[1];
            $permissionsArr = explode("|", $permissionList);
            if ($this->_hasRequiredRolePermission($payloadInput, $role, $permissionsArr)) {
                return true;
            }
        }
        return false;
    }

    private function _hasRequiredRolePermission($payloadInput, $role, $permissionArr)
    {
        foreach ($payloadInput as $payloadItem) {

            if ($payloadItem['role'] == $role && count(array_intersect($payloadItem['permissions'], $permissionArr)) > 0) {

                return true;
            }
        }
        return false;
    }

    private function hasRequiredRole($payloadRoles, $routeRole)
    {
        $routeRoleArray = explode("|", $routeRole);
        return count(array_intersect($payloadRoles, $routeRoleArray)) > 0;
    }
}
