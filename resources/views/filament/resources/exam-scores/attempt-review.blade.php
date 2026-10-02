<div class="rv">
    <style>
        .rv-head { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .5rem; padding: .75rem 1rem; margin-bottom: 1rem; border: 1px solid rgba(128,128,128,.3); border-radius: .75rem; }
        .rv-muted { opacity: .65; font-size: .875rem; margin-left: .5rem; }
        .rv-card { border: 1px solid rgba(128,128,128,.3); border-radius: .75rem; padding: 1rem 1.25rem; margin-bottom: 1rem; }
        .rv-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: .5rem; }
        .rv-no { font-weight: 700; opacity: .7; }
        .rv-badge { font-size: .75rem; font-weight: 700; padding: .125rem .6rem; border-radius: 9999px; }
        .rv-ok { background: rgba(16,185,129,.18); color: #10b981; }
        .rv-bad { background: rgba(239,68,68,.18); color: #ef4444; }
        .rv-none { background: rgba(245,158,11,.18); color: #f59e0b; }
        .rv-text { margin-bottom: .75rem; line-height: 1.6; }
        .rv-opts { display: flex; flex-direction: column; gap: .5rem; }
        .rv-opt { display: flex; align-items: center; gap: .75rem; padding: .5rem .75rem; border: 1px solid rgba(128,128,128,.3); border-radius: .5rem; }
        .rv-huruf { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 1.75rem; height: 1.75rem; border-radius: 9999px; border: 1px solid rgba(128,128,128,.5); font-weight: 700; font-size: .8rem; }
        .rv-opt-key { border-color: #10b981; background: rgba(16,185,129,.12); }
        .rv-opt-wrong { border-color: #ef4444; background: rgba(239,68,68,.12); }
        .rv-tag { margin-left: auto; font-size: .75rem; font-weight: 700; white-space: nowrap; }
        .rv-tag-key { color: #10b981; }
        .rv-tag-wrong { color: #ef4444; }
    </style>

    <div class="rv-head">
        <div>
            <strong>{{ $attempt->user?->name }}</strong>
            <span class="rv-muted">NIS {{ $attempt->user?->username }}</span>
        </div>
        <div>
            Nilai <strong>{{ rtrim(rtrim(number_format((float) $attempt->score, 2), '0'), '.') }}</strong>
            <span class="rv-muted">Benar {{ $attempt->correct_count }} / {{ $attempt->total_questions }}</span>
        </div>
    </div>

    @forelse ($review as $item)
        <div class="rv-card">
            <div class="rv-top">
                <span class="rv-no">Soal {{ $item['number'] }}</span>

                @if (! $item['answered'])
                    <span class="rv-badge rv-none">Tidak dijawab</span>
                @elseif ($item['is_correct'])
                    <span class="rv-badge rv-ok">Benar</span>
                @else
                    <span class="rv-badge rv-bad">Salah</span>
                @endif
            </div>

            <div class="rv-text">{!! $item['payload'] !!}</div>

            <div class="rv-opts">
                @foreach ($item['options'] as $opt)
                    <div class="rv-opt {{ $opt['is_key'] ? 'rv-opt-key' : ($opt['chosen'] ? 'rv-opt-wrong' : '') }}">
                        <span class="rv-huruf">{{ chr(65 + $loop->index) }}</span>
                        <span>{{ $opt['text'] }}</span>

                        @if ($opt['chosen'] && $opt['is_key'])
                            <span class="rv-tag rv-tag-key">✓ Dipilih siswa (benar)</span>
                        @elseif ($opt['chosen'])
                            <span class="rv-tag rv-tag-wrong">✗ Dipilih siswa</span>
                        @elseif ($opt['is_key'])
                            <span class="rv-tag rv-tag-key">✓ Jawaban benar</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <p>Belum ada data jawaban untuk percobaan ini.</p>
    @endforelse
</div>