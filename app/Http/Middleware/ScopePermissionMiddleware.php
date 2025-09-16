<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScopePermissionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Check if user has the specific permission
        if ($user->hasPermissionTo($permission)) {
            // Add scope information to request for scoped permissions
            if (str_ends_with($permission, '_own')) {
                $request->merge(['permission_scope' => 'own']);
            } elseif (str_ends_with($permission, '_assigned')) {
                $request->merge(['permission_scope' => 'assigned']);
            }
            
            return $next($request);
        }
        
        // Check if user has the broader permission (fallback)
        $basePermission = $this->getBasePermission($permission);
        if ($basePermission && $user->hasPermissionTo($basePermission)) {
            return $next($request);
        }
        
        abort(403, 'You do not have permission to access this resource.');
    }
    
    /**
     * Get the base permission name by removing scope suffixes
     */
    private function getBasePermission(string $permission): ?string
    {
        if (str_ends_with($permission, '_own')) {
            return str_replace('_own', '', $permission);
        }
        
        if (str_ends_with($permission, '_assigned')) {
            return str_replace('_assigned', '', $permission);
        }
        
        return null;
    }
}