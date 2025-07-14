<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ServiceRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceRatingController extends Controller
{
    // Customer memberikan rating
    public function store(Request $request, Order $order)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500'
        ]);

        // Pastikan customer yang login adalah pemilik order
        if ($order->customer_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        // Pastikan order sudah completed dan payment validated
        if ($order->status !== 'completed' || !$order->is_payment_validated) {
            return response()->json([
                'message' => 'Order harus sudah selesai dan pembayaran divalidasi'
            ], 400);
        }

        // Cek apakah sudah pernah rating
        if ($order->rating) {
            return response()->json([
                'message' => 'Order ini sudah diberi rating'
            ], 400);
        }

        $rating = ServiceRating::create([
            'order_id' => $order->id,
            'customer_id' => Auth::id(),
            'technician_id' => $order->technician_id,
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);

        return response()->json([
            'message' => 'Rating berhasil ditambahkan',
            'data' => [
                'id' => $rating->id,
                'order_id' => $rating->order_id,
                'customer_id' => $rating->customer_id,
                'technician_id' => $rating->technician_id,
                'rating' => $rating->rating,
                'comment' => $rating->comment,
                'created_at' => $rating->created_at ? $rating->created_at->toDateTimeString() : null,
                'updated_at' => $rating->updated_at ? $rating->updated_at->toDateTimeString() : null,
                // relasi jika perlu
                'order' => $rating->order,
                'customer' => $rating->customer,
                'technician' => $rating->technician,
            ]
        ]);
    }

    // Ambil rating untuk order tertentu
    public function show(Order $order)
    {
        return response()->json([
            'data' => $order->rating
        ]);
    }

    // Ambil semua rating untuk teknisi
    public function getTechnicianRatings($technicianId)
    {
        $ratings = ServiceRating::where('technician_id', $technicianId)
            ->with(['order', 'customer'])
            ->orderBy('created_at', 'desc')
            ->get();

        $averageRating = $ratings->avg('rating');

        return response()->json([
            'data' => $ratings,
            'average_rating' => round($averageRating, 1),
            'total_ratings' => $ratings->count()
        ]);
    }
}