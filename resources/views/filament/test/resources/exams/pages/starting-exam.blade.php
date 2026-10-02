<x-filament-panels::page>
    <style>
        .soal-card { border: 1px solid rgba(128,128,128,.3); border-radius: .75rem; padding: 1.25rem; margin-bottom: 1.5rem; }
        .soal-nomor { font-weight: 700; margin-bottom: .5rem; opacity: .7; }
        .soal-teks { margin-bottom: 1rem; line-height: 1.6; }
        .opsi-list { display: flex; flex-direction: column; gap: .625rem; }
        .opsi { display: flex; align-items: center; gap: .75rem; padding: .625rem .875rem; border: 1px solid rgba(128,128,128,.35); border-radius: .5rem; cursor: pointer; transition: background-color .15s, border-color .15s; }
        .opsi:hover { background-color: rgba(128,128,128,.1); }
        .opsi input { position: absolute; opacity: 0; pointer-events: none; }
        .opsi-huruf { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 2rem; height: 2rem; border-radius: 9999px; border: 1px solid rgba(128,128,128,.5); font-weight: 700; font-size: .875rem; }
        .opsi:has(input:checked) { border-color: var(--primary-500, #10b981); background-color: rgba(16,185,129,.12); }
        .opsi:has(input:checked) .opsi-huruf { background-color: var(--primary-500, #10b981); border-color: var(--primary-500, #10b981); color: #fff; }
        .opsi:has(input:focus-visible) { outline: 2px solid var(--primary-500, #10b981); outline-offset: 2px; }
        .mapel-judul { font-size: 1.25rem; font-weight: 700; margin: 1rem 0; }
        .hasil-card { text-align: center; }
        .hasil-skor { font-size: 3rem; font-weight: 800; line-height: 1; margin: .5rem 0 1rem; }
        .hasil-status { margin-top: .5rem; font-weight: 700; }
        .hasil-lulus { color: #10b981; }
        .hasil-gagal { color: #ef4444; }
        .hasil-muted { opacity: .7; font-weight: 400; }
        .hasil-info { margin-top: 1rem; padding: .75rem 1rem; border: 1px solid rgba(245,158,11,.5); background: rgba(245,158,11,.12); border-radius: .5rem; font-size: .875rem; }
        .hasil-aksi { margin-top: 1.5rem; display: flex; gap: .75rem; justify-content: center; flex-wrap: wrap; }
        .kirim-wrap { display: flex; justify-content: flex-end; margin-top: 1rem; }
        .timer { position: sticky; top: 5rem; z-index: 20; display: flex; justify-content: space-between; align-items: center; padding: .625rem 1rem; margin-bottom: 1rem; border: 1px solid rgba(128,128,128,.35); border-radius: .75rem; backdrop-filter: blur(8px); background: rgba(128,128,128,.18); font-weight: 700; }
        .timer-angka { font-size: 1.5rem; font-variant-numeric: tabular-nums; }
        .timer-warn .timer-angka { color: #ef4444; }
    </style>

    @if ($result)
        <div class="soal-card hasil-card">
            <div class="soal-nomor">Hasil Ujian</div>
            <div class="hasil-skor">{{ rtrim(rtrim(number_format($result['score'], 2), '0'), '.') }}</div>
            <p>Benar {{ $result['correct'] }} dari {{ $result['total'] }} soal</p>
            <p class="hasil-status {{ $result['passed'] ? 'hasil-lulus' : 'hasil-gagal' }}">
                {{ $result['passed'] ? 'LULUS' : 'BELUM LULUS' }}
                <span class="hasil-muted">(nilai minimal {{ rtrim(rtrim(number_format($result['threshold'], 2), '0'), '.') }})</span>
            </p>

            @if ($result['timed_out'])
                <div class="hasil-info">
                    Waktu ujian habis. Nilai dihitung dari jawaban yang tersimpan sebelum waktu berakhir.
                </div>
            @endif

            <div class="hasil-aksi">
                <x-filament::button tag="a"
                    :href="\App\Filament\Test\Resources\ExamAttempts\ExamAttemptResource::getUrl('view', ['record' => $result['attempt_id']])">
                    Lihat pembahasan
                </x-filament::button>
                <x-filament::button tag="a" color="gray"
                    :href="\App\Filament\Test\Resources\Exams\ExamResource::getUrl()">
                    Kembali ke Sesi Ujian
                </x-filament::button>
            </div>
        </div>
    @else
        {{-- Hitung mundur. Saat habis, jawaban dikirim otomatis. Sisa waktu dihitung server. --}}
        @if ($secondsLeft !== null && count($collections))
            <div wire:ignore
                 class="timer"
                 :class="left <= 60 ? 'timer-warn' : ''"
                 x-data="{
                    left: {{ $secondsLeft }},
                    done: false,
                    t: null,
                    init() {
                        const end = Date.now() + this.left * 1000;
                        this.t = setInterval(() => {
                            this.left = Math.max(0, Math.round((end - Date.now()) / 1000));
                            if (this.left === 0 && !this.done) {
                                this.done = true;
                                clearInterval(this.t);
                                $wire.submit();
                            }
                        }, 1000);
                    },
                    destroy() { clearInterval(this.t); },
                    get label() {
                        const h = Math.floor(this.left / 3600);
                        const m = Math.floor((this.left % 3600) / 60);
                        const s = this.left % 60;
                        const p = (n) => String(n).padStart(2, '0');
                        return (h ? h + ':' : '') + p(m) + ':' + p(s);
                    }
                 }">
                <span x-text="done ? 'Waktu habis, mengirim jawaban...' : 'Sisa waktu'"></span>
                <span class="timer-angka" x-text="label"></span>
            </div>
        @endif

        @forelse ($collections as $pelajaran)
            <h3 class="mapel-judul">{{ $pelajaran['name'] }}</h3>

            @foreach ($pelajaran['soals'] as $pertanyaan)
                <div class="soal-card">
                    <div class="soal-nomor">Soal {{ $loop->iteration }}</div>
                    <div class="soal-teks">{!! $pertanyaan['payload'] !!}</div>

                    <div class="opsi-list">
                        @foreach ($pertanyaan['answers'] as $jawaban)
                            <label class="opsi">
                                <input type="radio"
                                       name="jawaban_{{ $pertanyaan['id'] }}"
                                       value="{{ $jawaban['id'] }}"
                                       wire:model.live="answers.{{ $pertanyaan['id'] }}">
                                <span class="opsi-huruf">{{ chr(65 + $loop->index) }}</span>
                                <span>{{ $jawaban['text'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @empty
            <p>Ujian ini belum memiliki mata pelajaran atau soal.</p>
        @endforelse

        @if (count($collections))
            <div class="kirim-wrap">
                <x-filament::button
                    size="lg"
                    wire:click="submit"
                    wire:confirm="Yakin ingin mengirim jawaban? Jawaban tidak dapat diubah setelah dikirim."
                    wire:loading.attr="disabled">
                    Kirim Jawaban
                </x-filament::button>
            </div>
        @endif
    @endif
</x-filament-panels::page>