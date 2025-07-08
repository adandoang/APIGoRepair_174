<?php
// app/Http/Middleware/IsAdmin.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->role == 'admin') {
            return $next($request);
        }

        return response()->json(['message' => 'Akses ditolak. Hanya untuk Admin.'], 403);
    }
}