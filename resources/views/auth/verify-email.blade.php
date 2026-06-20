<x-guest-layout>
    <p class="eyebrow text-gold-700">Hampir selesai</p>
    <h1 class="font-display text-3xl lg:text-4xl font-light tracking-tightest leading-tight mt-5 text-ink-900">
        Cek email Anda <em class="font-semibold not-italic">untuk verifikasi.</em>
    </h1>
    <p class="mt-4 text-sm text-ink-500 leading-relaxed">
        Terima kasih telah mendaftar. Sebelum memulai, mohon klik tautan verifikasi yang baru saja kami kirim ke email Anda.
        Tidak menerima email? Kami akan dengan senang hati mengirim ulang.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-6 text-sm font-medium text-sea-700 bg-sea-50 border border-sea-100 rounded-lg px-3 py-2">
            Tautan verifikasi baru telah dikirim ke email Anda.
        </div>
    @endif

    <div class="mt-8 flex items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-primary">Kirim Ulang Verifikasi</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm font-semibold text-ink-500 hover:text-crimson-700 link-gold">Log out</button>
        </form>
    </div>
</x-guest-layout>
