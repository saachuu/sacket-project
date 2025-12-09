<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class TicketScannerController extends Controller
{
    /**
     * Menampilkan halaman scanner tiket.
     */
    public function index(): View
    {
        // Global Scanner tidak butuh list event di halaman depan
        return view('admin.scanner.index');
    }

    /**
     * Memverifikasi kode unik tiket via API.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'unique_code' => 'required|string',
        ]);

        // 1. Cari Tiket berdasarkan unique_code saja
        $ticket = OrderItem::where('unique_code', $request->unique_code)
            ->with(['order.event', 'ticketCategory']) // Load relasi order -> event
            ->first();

        // Kasus 1: Tiket tidak ditemukan di database manapun
        if (!$ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tiket Tidak Dikenali!',
            ], 404);
        }

        $event = $ticket->order->event;

        // Kasus 1.5: GLOBAL SCANNER CHECK (Cek Tanggal Event)
        // Kita cek apakah tanggal event == HARI INI?
        // Asumsi kolom di database events adalah 'start_date'
        $eventDate = Carbon::parse($event->start_date);

        if (!$eventDate->isToday()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tiket Salah Tanggal!',
                'detail' => 'Tiket ini untuk event: ' . $event->name . ' pada tanggal ' . $eventDate->format('d M Y')
            ], 400);
        }

        // Kasus 2: Pesanan belum lunas
        if ($ticket->order->status !== 'paid') {
            return response()->json([
                'status' => 'error',
                'message' => 'Tiket Belum Lunas!',
                'data' => [
                    'event_name' => $event->name,
                    'owner' => $ticket->order->name ?? 'User'
                ]
            ], 422);
        }

        // Kasus 3: Tiket sudah pernah di-scan
        if ($ticket->checked_in_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tiket Sudah Digunakan!',
                'checked_in_at' => $ticket->checked_in_at->format('d M Y, H:i:s'),
                'data' => [
                    'event_name' => $event->name,
                    'owner' => $ticket->order->name ?? 'User', // Sesuaikan kolom nama user di tabel orders
                    'ticket_category' => $ticket->ticketCategory->name
                ]
            ], 409);
        }

        // Kasus 4: Tiket valid dan berhasil check-in
        DB::transaction(function () use ($ticket) {
            $ticket->checked_in_at = now();
            $ticket->save();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Check-in Berhasil!',
            'checked_in_at' => $ticket->checked_in_at->format('H:i:s'),
            'data' => [
                'event_name' => $event->name, // PENTING: Kirim nama event ke layar admin
                'owner' => $ticket->order->name ?? 'User',
                'ticket_category' => $ticket->ticketCategory->name
            ]
        ]);
    }
}
