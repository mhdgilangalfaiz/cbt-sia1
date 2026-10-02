<x-filament-panels::page>
    <style>
        .rw-card { border: 1px solid rgba(128,128,128,.3); border-radius: .75rem; padding: 1.25rem; margin-bottom: 1.5rem; }
        .rw-ringkas { display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: center; margin-top: .75rem; }
        .rw-skor { font-size: 3rem; font-weight: 800; line-height: 1; }
        .rw-muted { opacity: .7; }
        .rw-muted-normal { opacity: .7; font-weight: 400; }
        .rw-status { margin-top: .25rem; font-weight: 700; }
        .rw-lulus { color: #10b981; }
        .rw-gagal { color: #ef4444; }
        .rw-badge { display: inline-block; padding: .125rem .625rem; border-radius: 9999px; font-size: .75rem; font-weight: 700; color: #fff; }
        .rw-badge-benar { background: #10b981; }
        .rw-badge-salah { background: #ef4444; }
        .rw-badge-kosong { background: #6b7280; }
        .rw-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: .75rem; }
        .rw-teks { margin-bottom: 1rem; line-height: 1.6; }
        .rw-opsi-list { display: flex; flex-direction: column; gap: .5rem; }
        .rw-opsi { display: flex; align-items: center; gap: .75rem; padding: .625rem .875rem; border: 1px solid rgba(128,128,128,.35); border-radius: .5rem; }
        .rw-mark { flex-shrink: 0; width: 1.5rem; text-align: center; font-weight: 800; }
        .rw-note { margin-left: auto; font-size: .75rem; font-weight: 700; white-space: nowrap; }
        .rw-benar { border-color: #10b981; background: rgba(16,185,129,.12); }
        .rw-salah { border-color: #ef4444; background: rgba(239,68,68,.12); }
        .rw-info { background: rgba(245,158,11,.12); border: 1px solid rgba(245,158,11,.5); border-radius: .5rem; padding: .75rem 1rem; margin-bottom: 1.5rem; }
    </style>

    {{-- Ringkasan --}}
    <div class="rw-card">
        <div class="rw-muted">{{ $summary['title'] }} &middot; dikerjakan {{ $summary['submitted'] }}</div>
        <div class="rw-ringkas">
            <div class="rw-skor">{{ rtrim(rtrim(number_format($summary['score'], 2), '0'), '.') }}</div>
            <div>
                <div>
                    Benar <strong>{{ $summary['correct'] }}</strong> &middot;
                    Salah <strong>{{ $summary['wrong'] }}</strong> &middot;
                    dari {{ $summary['total'] }} soal
                </div>
                <div class="rw-status {{ $summary['passed'] ? 'rw-lulus' : 'rw-gagal' }}">
                    {{ $summary['passed'] ? 'LULUS' : 'BELUM LULUS' }}
                    <span class="rw-muted-normal">
                        (nilai minimal {{ rtrim(rtrim(number_format($summary['threshold'], 2), '0'), '.') }})
                    </span>
                </div>
            </div>
        </div>
    </div>

    @unless ($summary['key_visible'])
        <div class="rw-info">
            Kunci jawaban akan ditampilkan setelah ujian berakhir
            @if ($summary['key_at']) ({{ $summary['key_at'] }}) @endif.
        </div>
    @endunless

    {{-- Pembahasan per soal --}}
    @foreach ($review as $item)
        <div class="rw-card">
            <div class="rw-head">
                <strong>Soal {{ $loop->iteration }}</strong>
                @if (! $item['answered'])
                    <span class="rw-badge rw-badge-kosong">Tidak dijawab</span>
                @elseif ($item['is_correct'])
                    <span class="rw-badge rw-badge-benar">Benar</span>
                @else
                    <span class="rw-badge rw-badge-salah">Salah</span>
                @endif
            </div>

            @if ($item['payload'] === null)
                <p class="rw-muted">Soal ini sudah dihapus dari bank soal.</p>
            @else
                <div class="rw-teks">{!! $item['payload'] !!}</div>

                <div class="rw-opsi-list">
                    @foreach ($item['options'] as $opsi)
                        @php
                            $kelas = '';
                            if ($opsi['chosen'] && $item['is_correct']) $kelas = 'rw-benar';
                            elseif ($opsi['chosen']) $kelas = 'rw-salah';
                            elseif ($opsi['is_key']) $kelas = 'rw-benar';
                        @endphp
                        <div class="rw-opsi {{ $kelas }}">
                            <span class="rw-mark">
                                @if ($opsi['chosen'] && $item['is_correct']) ✓
                                @elseif ($opsi['chosen']) ✗
                                @elseif ($opsi['is_key']) ✓
                                @endif
                            </span>
                            <span>{{ $opsi['text'] }}</span>
                            <span class="rw-note">
                                @if ($opsi['chosen'])
                                    Jawaban kamu
                                @elseif ($opsi['is_key'])
                                    Jawaban benar
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach

    <div>
        <x-filament::button tag="a" color="gray"
            :href="\App\Filament\Test\Resources\ExamAttempts\ExamAttemptResource::getUrl()">
            Kembali ke Riwayat
        </x-filament::button>
    </div>
</x-filament-panels::page>