<?php
// app/Http/Middleware/IsTechnician.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsTechnician
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->role == 'technician') {
            return $next($request);
        }

        return response()->json(['message' => 'Akses ditolak. Hanya untuk Teknisi.'], 403);
    }
}