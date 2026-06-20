<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\WebhookController;
use App\Models\Document;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Pusat notifikasi DJBC: 3 kategori (Respon / Formulir / Informasi) dari webhook_logs.
     * Menampilkan notifikasi terkait dokumen milik user + pengumuman sistem global.
     */
    public function index(Request $request): View
    {
        $docIds = Document::where('user_id', $request->user()->id)->pluck('id');

        $logs = WebhookLog::query()
            ->with('document:id,doc_type,nomor_aju,status')
            ->where(function ($q) use ($docIds) {
                $q->whereIn('document_id', $docIds)
                    // Pengumuman sistem (Informasi) tanpa dokumen bersifat global.
                    ->orWhere(fn ($qq) => $qq->whereNull('document_id')
                        ->where('notification_type', WebhookController::TYPE_INFORMASI));
            })
            ->latest('received_at')
            ->latest('id')
            ->get();

        $grouped = [
            WebhookController::TYPE_RESPON => $logs->where('notification_type', WebhookController::TYPE_RESPON)->values(),
            WebhookController::TYPE_FORMULIR => $logs->where('notification_type', WebhookController::TYPE_FORMULIR)->values(),
            WebhookController::TYPE_INFORMASI => $logs->where('notification_type', WebhookController::TYPE_INFORMASI)->values(),
        ];

        return view('notifications.index', [
            'grouped' => $grouped,
            'counts' => array_map(fn ($c) => $c->count(), $grouped),
            'total' => $logs->count(),
        ]);
    }
}
