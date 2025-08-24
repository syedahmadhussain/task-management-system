<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToOrganization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check if org_id is provided in the route or request
        $orgId = $request->route('orgId') ?? $request->get('org_id');
        
        if ($orgId && (int)$user->org_id !== (int)$orgId) {
            return response()->json(['error' => 'Access denied. You do not belong to this organization.'], 403);
        }

        // Add the user's org_id to the request for use in controllers
        $request->merge(['user_org_id' => $user->org_id]);
        
        return $next($request);
    }
}
