<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dokumen {{ $document->doc_type }}
                <span class="text-gray-400 font-normal">#{{ $document->id }}</span>
            </h2>
            <x-status-badge :status="$document->status" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            {{-- Ringkasan --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="grid md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Nomor Aju</dt>
                        <dd class="font-mono text-gray-900">{{ $document->nomor_aju ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Nomor Pendaftaran</dt>
                        <dd class="font-mono text-gray-900">{{ $document->nomor_daftar ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Disubmit</dt>
                        <dd class="text-gray-900">{{ $document->submitted_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Response Diterima</dt>
                        <dd class="text-gray-900">{{ $document->response_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>

                @if ($document->error_message)
                    <div class="mt-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                        {{ $document->error_message }}
                    </div>
                @endif

                @if (in_array($document->status, ['draft', 'error']))
                    <form method="POST" action="{{ route('documents.submit', $document) }}" class="mt-4">
                        @csrf
                        <x-primary-button>Kirim Ulang ke CEISA</x-primary-button>
                    </form>
                @endif
            </div>

            {{-- Payload --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Payload Terkirim</h3>
                <pre class="bg-gray-50 border border-gray-200 rounded-md p-4 text-xs overflow-x-auto">{{ json_encode($document->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>

            {{-- Response CEISA --}}
            @if ($document->ceisa_response)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-3">Response CEISA</h3>
                    <pre class="bg-gray-50 border border-gray-200 rounded-md p-4 text-xs overflow-x-auto">{{ json_encode($document->ceisa_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            @endif

            {{-- Riwayat Webhook --}}
            @if ($document->webhookLogs->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-3">Riwayat Webhook</h3>
                    <ul class="divide-y divide-gray-100 text-sm">
                        @foreach ($document->webhookLogs->sortByDesc('received_at') as $log)
                            <li class="py-2 flex items-center justify-between">
                                <span class="text-gray-700">{{ $log->event ?? 'update' }}</span>
                                <span class="text-gray-400">{{ $log->received_at?->format('d/m/Y H:i:s') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <a href="{{ route('dashboard') }}" class="inline-block text-sm text-gray-600 hover:underline">&larr; Kembali ke Dashboard</a>
        </div>
    </div>
</x-app-layout>
