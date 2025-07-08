<!DOCTYPE html>
<html>
<head>
    <title>Invoice Order #{{ $order->id }}</title>
    <style>
        body { font-family: sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .content { margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>INVOICE</h1>
        <h2>GoRepair</h2>
    </div>

    <div class="customer-info">
        <strong>Nomor Order:</strong> #{{ $order->id }}<br>
        <strong>Tanggal:</strong> {{ $order->created_at->format('d M Y') }}<br>
        <strong>Pelanggan:</strong> {{ $order->customer->name }}<br>
        <strong>Email:</strong> {{ $order->customer->email }}<br>
    </div>

    <div class="content">
        <table>
            <thead>
                <tr>
                    <th>Deskripsi Layanan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $order->category->name }}</strong><br>
                        <small>{{ $order->description }}</small>
                    </td>
                    <td>{{ ucfirst($order->status) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>