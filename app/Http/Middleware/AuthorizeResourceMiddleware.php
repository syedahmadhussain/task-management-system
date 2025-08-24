<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeResourceMiddleware
{
    public function handle(Request $request, Closure $next, string $model, string $parameter = null): Response
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $routeName = $request->route()->getName();
        $action = $this->getActionFromRoute($routeName ?? $this->getActionFromMethod($request->method()));
        $modelClass = "App\\Models\\{$model}";

        if (!class_exists($modelClass)) {
            return response()->json(['error' => 'Model not found'], 500);
        }

        $needsInstance = in_array($action, ['update', 'delete', 'view']);

        if ($needsInstance) {
            $id = $request->route('id') ?: $request->route('task') ?: $request->route('project') ?: $request->route('user');

            if (!$id) {
                return response()->json(['error' => 'Resource ID not found'], 400);
            }

            $modelInstance = $modelClass::find($id);

            if (!$modelInstance) {
                return response()->json(['error' => 'Resource not found'], 404);
            }

            try {
                if (!Gate::forUser($user)->allows($action, $modelInstance)) {
                    return response()->json(['error' => 'Forbidden'], 403);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => 'Authorization failed: ' . $e->getMessage()], 403);
            }
        } else {
            try {
                if (!Gate::forUser($user)->allows($action, $modelClass)) {
                    return response()->json(['error' => 'Forbidden'], 403);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => 'Authorization failed: ' . $e->getMessage()], 403);
            }
        }

        return $next($request);
    }

    private function getActionFromRoute(string $routeName): string
    {
        $parts = explode('.', $routeName);
        $action = end($parts);

        return match($action) {
            'index' => 'viewAny',
            'show' => 'view',
            'store' => 'create',
            'update' => 'update',
            'destroy' => 'delete',
            default => $action
        };
    }

    private function getActionFromMethod(string $method): string
    {
        return match(strtoupper($method)) {
            'GET' => 'viewAny',
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'viewAny'
        };
    }
}
