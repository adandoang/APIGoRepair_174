<?php
// File: app/Http/Controllers/Api/Admin/OrderController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('customer', 'category', 'technician')->latest()->get();

        return response()->json([
            'message' => 'Semua data order berhasil diambil',
            'data' => $orders
        ]);
    }

    /**
     * Update status order dan menugaskan teknisi.
     */
    public function update(Request $request, Order $order)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['processed', 'assigned', 'completed', 'cancelled'])],
            'technician_id' => [
                'nullable',
                'required_if:status,assigned',
                Rule::exists('users', 'id')->where(function ($query) {
                    return $query->where('role', 'technician');
                }),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Update status order
        $order->status = $request->status;

        // Jika ada technician_id yang dikirim, update juga
        if ($request->status == 'assigned' && $request->has('technician_id')) {
            $order->technician_id = $request->technician_id;
        } else {
            $order->technician_id = null;
        }

        $order->save();

        // Ambil data order terbaru beserta relasinya untuk ditampilkan
        $order->load('customer', 'category', 'technician');

        return response()->json([
            'message' => 'Order berhasil diupdate',
            'data' => $order
        ]);
    }
    public function validatePayment(Order $order)
    {
        // Pastikan ada bukti bayar yang diunggah sebelum divalidasi
        if (is_null($order->payment_proof_url)) {
            return response()->json(['message' => 'Pelanggan belum mengunggah bukti pembayaran.'], 422);
        }

        // Ubah status validasi menjadi true
        $order->is_payment_validated = true;
        
        // Opsional: Ubah juga status order menjadi 'processed' jika alurnya begitu
        $order->status = 'processed';
        
        $order->save();

        return response()->json([
            'message' => 'Pembayaran berhasil divalidasi.',
            'data' => $order->fresh()->load('customer', 'category', 'technician')
        ]);
    }   
    public function downloadInvoice(Order $order)
    {
        // Load view 'invoices.order' dan teruskan data order
        $pdf = PDF::loadView('invoices.order', ['order' => $order]);

        // Unduh file PDF
        return $pdf->download('invoice-order-'.$order->id.'.pdf');
    }
}