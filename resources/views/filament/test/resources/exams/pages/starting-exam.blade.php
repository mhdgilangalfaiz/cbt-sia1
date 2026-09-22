<x-filament-panels::page>
    <ol>
    @foreach ($collections as $pelajaran)
        <li type="A" style="font-weight: 800;">{{ $pelajaran['name'] }}</
        li>
        <ol>
            @foreach ($pelajaran['soals'] as $pertanyaan)
                <li type="1">
                    {!! $pertanyaan['payload'] !!}
                    <ol>
                        @foreach ($pertanyaan['answers'] as $jawaban)
                        <li>
                            <label>
                                <x-filament::input.radio name="jawaban_{{ 
                                 }}"
                            </label>
                        </li>
                        
                        @endforeach
                    </ol>
                </li>
            @endforeach
        </ol>
    
    @endforeach
    </ol>
    {{-- Page content --}}
</x-filament-panels::page>
