<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionAnswerSeeder extends Seeder
{
    private const TOTAL_PER_SUBJECT = 150;

    public function run(): void
    {
        mt_srand(2026); // agar hasil acak konsisten setiap kali seeder dijalankan

        $map = [
            'PJOK'       => 'pjokQuestions',
            'Matematika' => 'matematikaQuestions',
            'IPA'        => 'ipaQuestions',
        ];

        foreach ($map as $subjectName => $method) {
            $subject = Subject::where('name', $subjectName)->first();

            if (! $subject) {
                $this->command?->warn("Mata pelajaran '{$subjectName}' tidak ditemukan, dilewati.");

                continue;
            }

            $pool = self::{$method}();
            shuffle($pool);
            $pool = array_slice($pool, 0, self::TOTAL_PER_SUBJECT);

            DB::transaction(function () use ($subject, $pool, $subjectName) {
                // Bersihkan soal lama milik pelajaran ini (jawabannya ikut terhapus via cascadeOnDelete)
                Question::withTrashed()->where('subject_id', $subject->id)->forceDelete();

                foreach ($pool as $item) {
                    $question = Question::create([
                        'subject_id'  => $subject->id,
                        'payload'     => $item['question'],
                        'score'       => 1,
                        'description' => null,
                        'is_active'   => true,
                    ]);

                    foreach ($item['options'] as $option) {
                        Answer::create([
                            'question_id' => $question->id,
                            'text'        => $option['text'],
                            'is_correct'  => $option['correct'],
                            'is_active'   => true,
                        ]);
                    }
                }

                $this->command?->info("{$subjectName}: ".count($pool)." soal berhasil dibuat.");
            });
        }
    }

    // Bentuk satu soal pilihan ganda dari 1 jawaban benar + beberapa jawaban salah.
     
    private static function buildMcq(string $question, string $correct, array $wrongs): array
    {
        $options = [['text' => $correct, 'correct' => true]];

        foreach ($wrongs as $w) {
            $options[] = ['text' => $w, 'correct' => false];
        }

        shuffle($options);

        return ['question' => $question, 'options' => $options];
    }

    private static function pickDistractors(array $pairs, int|string $excludeKey, int $n, bool $wantValue, array $fallbackPool = []): array
    {
        $excludeValue = $wantValue ? ($pairs[$excludeKey] ?? '') : $excludeKey;
        $excludeNormalized = mb_strtolower(trim((string) $excludeValue));

        $seen = [$excludeNormalized => true];
        $result = [];

        $candidates = $pairs;
        unset($candidates[$excludeKey]);
        $keys = array_keys($candidates);
        shuffle($keys);

        foreach ($keys as $k) {
            $val = $wantValue ? (string) $candidates[$k] : (string) $k;
            $norm = mb_strtolower(trim($val));
            if (isset($seen[$norm])) {
                continue;
            }
            $seen[$norm] = true;
            $result[] = $val;
            if (count($result) >= $n) {
                break;
            }
        }

        if (count($result) < $n && ! empty($fallbackPool)) {
            $extra = $fallbackPool;
            shuffle($extra);
            foreach ($extra as $val) {
                $norm = mb_strtolower(trim($val));
                if (isset($seen[$norm])) {
                    continue;
                }
                $seen[$norm] = true;
                $result[] = $val;
                if (count($result) >= $n) {
                    break;
                }
            }
        }

        return $result;
    }

    private static function buildLookupQuestions(array $pairs, string $askValue, ?string $askKey = null, int $distractorCount = 3, array $valueFallbackPool = []): array
    {
        $items = [];

        foreach ($pairs as $key => $value) {
            $wrongValues = self::pickDistractors($pairs, $key, $distractorCount, true, $valueFallbackPool);
            // jaga-jaga: jika pool unik + fallback tetap kurang dari yang diminta, lewati soal ini
            // agar tidak menghasilkan pilihan jawaban duplikat.
            if (count($wrongValues) < $distractorCount) {
                continue;
            }
            $items[] = self::buildMcq(sprintf($askValue, $key), (string) $value, $wrongValues);

            if ($askKey !== null) {
                $wrongKeys = self::pickDistractors($pairs, $key, $distractorCount, false);
                if (count($wrongKeys) < $distractorCount) {
                    continue;
                }
                $items[] = self::buildMcq(sprintf($askKey, $value), (string) $key, $wrongKeys);
            }
        }

        return $items;
    }

    private static function numericDistractors(int|float $correct, int $minDelta = 1, int $maxDeltaPercent = 30): array
    {
        $wrongs = [];
        $attempts = 0;

        while (count($wrongs) < 3 && $attempts < 50) {
            $attempts++;
            $deltaBase = max($minDelta, (int) round(abs($correct) * mt_rand(5, $maxDeltaPercent) / 100));
            $delta = mt_rand(1, max(1, $deltaBase)) * (mt_rand(0, 1) ? 1 : -1);
            $w = $correct + $delta;

            if ($w == $correct || $w < 0) {
                continue;
            }

            $wStr = (string) (is_float($correct) ? round($w, 2) : (int) $w);

            if (! in_array($wStr, $wrongs, true)) {
                $wrongs[] = $wStr;
            }
        }

        // jaring pengaman jika perulangan acak di atas belum cukup menghasilkan 3 opsi
        $i = 1;
        while (count($wrongs) < 3) {
            $w = (string) ($correct + $i);
            if ($w !== (string) $correct && ! in_array($w, $wrongs, true)) {
                $wrongs[] = $w;
            }
            $i++;
        }

        return $wrongs;
    }

    private static function gcd(int $a, int $b): int
    {
        return $b === 0 ? max($a, 1) : self::gcd($b, $a % $b);
    }

    // MATEMATIKA (150 soal, dihitung secara algoritmik agar jawaban pasti benar)

    private static function matematikaQuestions(): array
    {
        $items = [];
        // 1) Operasi hitung bilangan bulat (target 40 soal unik, dicoba hingga 80x agar aman)
        $count = 0;
        for ($i = 0; $count < 40 && $i < 80; $i++) {
            $op = ['+', '-', 'x', ':'][$i % 4];

            switch ($op) {
                case '+':
                    $a = mt_rand(10, 500);
                    $b = mt_rand(10, 500);
                    $correct = $a + $b;
                    $question = "Hasil dari {$a} + {$b} adalah...";
                    break;
                case '-':
                    $a = mt_rand(100, 900);
                    $b = mt_rand(1, $a - 1);
                    $correct = $a - $b;
                    $question = "Hasil dari {$a} - {$b} adalah...";
                    break;
                case 'x':
                    $a = mt_rand(2, 50);
                    $b = mt_rand(2, 50);
                    $correct = $a * $b;
                    $question = "Hasil dari {$a} x {$b} adalah...";
                    break;
                default: // ':'
                    $b = mt_rand(2, 25);
                    $q = mt_rand(2, 50);
                    $a = $b * $q;
                    $correct = $q;
                    $question = "Hasil dari {$a} : {$b} adalah...";
                    break;
            }

            if (! isset($items[$question])) {
                $items[$question] = self::buildMcq($question, (string) $correct, self::numericDistractors($correct));
                $count++;
            }
        }

        // 2) Penjumlahan pecahan sepenyebut (target 10 soal unik, dicoba hingga 30x)
        $denoms = [2, 3, 4, 5, 6, 8, 10, 12];
        $count = 0;
        for ($i = 0; $count < 10 && $i < 30; $i++) {
            $d = $denoms[$i % count($denoms)];
            $n1 = mt_rand(1, $d - 1);
            $n2 = mt_rand(1, $d - 1);
            $sumN = $n1 + $n2;
            $g = self::gcd($sumN, $d);
            $correct = ($sumN / $g).'/'.($d / $g);
            $question = "Hasil dari {$n1}/{$d} + {$n2}/{$d} adalah...";

            $wrongs = [];
            while (count($wrongs) < 3) {
                $wn = max(1, $sumN + mt_rand(-2, 2));
                $cand = $wn.'/'.$d;
                if ($cand !== $correct && ! in_array($cand, $wrongs, true)) {
                    $wrongs[] = $cand;
                }
            }

            if (! isset($items[$question])) {
                $items[$question] = self::buildMcq($question, $correct, $wrongs);
                $count++;
            }
        }

        // 3) Pecahan ke bentuk desimal (10 soal)
        $fracToDecimal = [
            '1/2' => '0,5', '1/4' => '0,25', '3/4' => '0,75', '1/5' => '0,2',
            '2/5' => '0,4', '3/5' => '0,6', '4/5' => '0,8', '1/8' => '0,125',
            '1/10' => '0,1', '3/10' => '0,3',
        ];
        foreach ($fracToDecimal as $frac => $dec) {
            $decVal = (float) str_replace(',', '.', $dec);
            $wrongs = [];
            $guard = 0;
            while (count($wrongs) < 3 && $guard < 50) {
                $guard++;
                $w = round($decVal + (mt_rand(-30, 30) / 100), 3);
                if ($w <= 0) {
                    continue;
                }
                $wStr = rtrim(rtrim(number_format($w, 3, '.', ''), '0'), '.');
                $wStr = str_replace('.', ',', $wStr);
                if ($wStr !== $dec && ! in_array($wStr, $wrongs, true)) {
                    $wrongs[] = $wStr;
                }
            }
            $q = "Bentuk desimal dari pecahan {$frac} adalah...";
            $items[$q] = self::buildMcq($q, $dec, $wrongs);
        }

        // 4) Desimal ke bentuk persen (10 soal)
        $decToPercent = [
            '0,1' => '10%', '0,15' => '15%', '0,2' => '20%', '0,25' => '25%',
            '0,3' => '30%', '0,4' => '40%', '0,5' => '50%', '0,6' => '60%',
            '0,75' => '75%', '0,9' => '90%',
        ];
        foreach ($decToPercent as $dec => $persen) {
            $val = (int) rtrim($persen, '%');
            $wrongs = [];
            $guard = 0;
            while (count($wrongs) < 3 && $guard < 50) {
                $guard++;   
                $w = $val + mt_rand(-3, 3) * 5;
                if ($w <= 0 || $w === $val) {
                    continue;
                }
                $wStr = $w.'%';
                if (! in_array($wStr, $wrongs, true)) {
                    $wrongs[] = $wStr;
                }
            }
            $q = "Bentuk persen dari {$dec} adalah...";
            $items[$q] = self::buildMcq($q, $persen, $wrongs);
        }

        // 5) Persentase dari suatu bilangan (target 20 soal unik, dicoba hingga 50x)
        $percents = [5, 10, 15, 20, 25, 30, 40, 50, 60, 70, 75, 80, 90];
        $count = 0;
        for ($i = 0; $count < 20 && $i < 50; $i++) {
            $p = $percents[$i % count($percents)];
            $base = 20 * mt_rand(1, 25); // kelipatan 20, hasil bagi 100 selalu bulat
            $correct = ($p * $base) / 100;
            $question = "Hasil dari {$p}% dari {$base} adalah...";
            if (! isset($items[$question])) {
                $items[$question] = self::buildMcq($question, (string) $correct, self::numericDistractors($correct));
                $count++;
            }
        }

        // 6) Aljabar sederhana - mencari nilai x (target 20 soal unik, dicoba hingga 50x)
        $count = 0;
        for ($i = 0; $count < 20 && $i < 50; $i++) {
            $a = mt_rand(2, 9);
            $x = mt_rand(1, 20);
            $b = mt_rand(1, 50);
            $sign = $i % 2 === 0 ? '+' : '-';
            $c = $sign === '+' ? ($a * $x + $b) : ($a * $x - $b);
            $question = "Jika {$a}x {$sign} {$b} = {$c}, maka nilai x yang memenuhi adalah...";
            if (! isset($items[$question])) {
                $items[$question] = self::buildMcq($question, (string) $x, self::numericDistractors($x, 1, 25));
                $count++;
            }
        }

        // 7) Geometri: luas & keliling bangun datar (target 20 soal unik, dicoba hingga 50x)
        $count = 0;
        for ($i = 0; $count < 20 && $i < 50; $i++) {
            switch ($i % 4) {
                case 0: // persegi
                    $s = mt_rand(3, 30);
                    if ($i % 8 < 4) {
                        $correct = $s * $s;
                        $question = "Luas persegi dengan panjang sisi {$s} cm adalah...cm2";
                    } else {
                        $correct = 4 * $s;
                        $question = "Keliling persegi dengan panjang sisi {$s} cm adalah...cm";
                    }
                    break;
                case 1: // persegi panjang
                    $p = mt_rand(6, 40);
                    $l = mt_rand(3, $p - 1);
                    if ($i % 8 < 4) {
                        $correct = $p * $l;
                        $question = "Luas persegi panjang dengan panjang {$p} cm dan lebar {$l} cm adalah...cm2";
                    } else {
                        $correct = 2 * ($p + $l);
                        $question = "Keliling persegi panjang dengan panjang {$p} cm dan lebar {$l} cm adalah...cm";
                    }
                    break;
                case 2: // segitiga
                    $alas = 2 * mt_rand(3, 20);
                    $tinggi = mt_rand(3, 20);
                    $correct = ($alas * $tinggi) / 2;
                    $question = "Luas segitiga dengan alas {$alas} cm dan tinggi {$tinggi} cm adalah...cm2";
                    break;
                default: // lingkaran, jari-jari kelipatan 7 agar hasil dengan pi=22/7 bulat
                    $r = 7 * mt_rand(1, 10);
                    if ($i % 8 < 4) {
                        $correct = (22 / 7) * $r * $r;
                        $question = "Luas lingkaran dengan jari-jari {$r} cm (menggunakan pi=22/7) adalah...cm2";
                    } else {
                        $correct = 2 * (22 / 7) * $r;
                        $question = "Keliling lingkaran dengan jari-jari {$r} cm (menggunakan pi=22/7) adalah...cm";
                    }
                    break;
            }
            $correct = (int) round($correct);
            if (! isset($items[$question])) {
                $items[$question] = self::buildMcq($question, (string) $correct, self::numericDistractors($correct));
                $count++;
            }
        }

        // 8) Konversi satuan (target 20 soal unik, dicoba hingga 50x)
        $conversions = [
            ['dari' => 'km', 'ke' => 'm', 'faktor' => 1000],
            ['dari' => 'm', 'ke' => 'cm', 'faktor' => 100],
            ['dari' => 'kg', 'ke' => 'gram', 'faktor' => 1000],
            ['dari' => 'ton', 'ke' => 'kg', 'faktor' => 1000],
            ['dari' => 'jam', 'ke' => 'menit', 'faktor' => 60],
            ['dari' => 'menit', 'ke' => 'detik', 'faktor' => 60],
            ['dari' => 'liter', 'ke' => 'ml', 'faktor' => 1000],
        ];
        $count = 0;
        for ($i = 0; $count < 20 && $i < 50; $i++) {
            $c = $conversions[$i % count($conversions)];
            $val = mt_rand(2, 20);
            $correct = $val * $c['faktor'];
            $question = "{$val} {$c['dari']} = ... {$c['ke']}";
            if (! isset($items[$question])) {
                $items[$question] = self::buildMcq($question, (string) $correct, self::numericDistractors($correct));
                $count++;
            }
        }

        return array_values($items);
    }

    // IPA (pool > 150 soal berbasis data fakta, lalu diacak & diambil 150)

    private static function ipaQuestions(): array
    {
        $items = [];

        // A. Organ tubuh manusia & fungsinya
        $organFungsi = [
            'Jantung' => 'memompa darah ke seluruh tubuh',
            'Paru-paru' => 'tempat pertukaran oksigen dan karbon dioksida',
            'Ginjal' => 'menyaring darah dan membentuk urine',
            'Hati' => 'menetralkan racun dan menghasilkan cairan empedu',
            'Lambung' => 'mencerna makanan secara kimiawi dengan bantuan asam lambung',
            'Usus halus' => 'menyerap sari-sari makanan',
            'Usus besar' => 'menyerap air dan mengubah sisa makanan menjadi feses',
            'Otak' => 'menjadi pusat pengendali seluruh aktivitas tubuh',
            'Kulit' => 'melindungi tubuh dan mengatur suhu tubuh',
            'Mata' => 'menjadi indra penglihatan',
            'Telinga' => 'menjadi indra pendengaran dan menjaga keseimbangan tubuh',
            'Tulang' => 'menjadi rangka penopang tubuh dan tempat melekatnya otot',
            'Otot' => 'menggerakkan bagian-bagian tubuh',
            'Pankreas' => 'menghasilkan hormon insulin dan enzim pencernaan',
            'Trakea' => 'menyalurkan udara pernapasan menuju paru-paru',
            'Kerongkongan' => 'menyalurkan makanan dari mulut menuju lambung',
            'Pembuluh darah' => 'menyalurkan darah ke seluruh bagian tubuh',
            'Kelenjar keringat' => 'menghasilkan keringat untuk mengatur suhu tubuh',
            'Hidung' => 'menjadi indra penciuman sekaligus jalur masuk udara pernapasan',
            'Lidah' => 'menjadi indra pengecap rasa',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $organFungsi,
            'Apa fungsi utama dari organ %s pada tubuh manusia?',
            'Organ tubuh manusia yang berfungsi untuk %s adalah...'
        ));

        // B. Tata surya - urutan planet dari Matahari
        $planetUrutan = [
            1 => 'Merkurius', 2 => 'Venus', 3 => 'Bumi', 4 => 'Mars',
            5 => 'Jupiter', 6 => 'Saturnus', 7 => 'Uranus', 8 => 'Neptunus',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $planetUrutan,
            'Planet dengan urutan ke-%s dari Matahari adalah...',
            'Planet %s berada pada urutan keberapa dari Matahari?'
        ));

        // Tata surya - ciri khas planet
        $planetCiri = [
            'Merkurius' => 'planet terdekat dengan Matahari',
            'Venus' => 'planet terpanas di tata surya',
            'Bumi' => 'satu-satunya planet yang dihuni oleh makhluk hidup',
            'Mars' => 'dikenal sebagai planet merah',
            'Jupiter' => 'planet terbesar di tata surya',
            'Saturnus' => 'planet dengan cincin yang paling jelas terlihat',
            'Uranus' => 'planet yang berotasi dengan posisi hampir rebah',
            'Neptunus' => 'planet terjauh dari Matahari',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $planetCiri,
            'Planet %s dikenal karena...',
            'Planet manakah yang %s?'
        ));

        // C. Klasifikasi hewan
        $hewanNapas = [
            'Kucing' => 'paru-paru', 'Ayam' => 'paru-paru', 'Ikan mas' => 'insang',
            'Katak' => 'paru-paru dan kulit', 'Ular' => 'paru-paru', 'Buaya' => 'paru-paru',
            'Kupu-kupu' => 'trakea', 'Nyamuk' => 'trakea', 'Sapi' => 'paru-paru',
            'Kambing' => 'paru-paru', 'Paus' => 'paru-paru', 'Lumba-lumba' => 'paru-paru',
            'Cicak' => 'paru-paru', 'Penyu' => 'paru-paru', 'Burung elang' => 'paru-paru',
            'Bebek' => 'paru-paru', 'Kadal' => 'paru-paru', 'Kelinci' => 'paru-paru',
            'Hiu' => 'insang', 'Lebah' => 'trakea',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $hewanNapas,
            'Hewan %s bernapas dengan menggunakan...'
        ));

        $hewanBiak = [
            'Kucing' => 'vivipar (melahirkan)', 'Ayam' => 'ovipar (bertelur)', 'Ikan mas' => 'ovipar (bertelur)',
            'Katak' => 'ovipar (bertelur)', 'Ular' => 'ovipar (bertelur)', 'Buaya' => 'ovipar (bertelur)',
            'Kupu-kupu' => 'ovipar (bertelur)', 'Nyamuk' => 'ovipar (bertelur)', 'Sapi' => 'vivipar (melahirkan)',
            'Kambing' => 'vivipar (melahirkan)', 'Paus' => 'vivipar (melahirkan)', 'Lumba-lumba' => 'vivipar (melahirkan)',
            'Cicak' => 'ovipar (bertelur)', 'Penyu' => 'ovipar (bertelur)', 'Burung elang' => 'ovipar (bertelur)',
            'Bebek' => 'ovipar (bertelur)', 'Kadal' => 'ovipar (bertelur)', 'Kelinci' => 'vivipar (melahirkan)',
            'Hiu' => 'ovovivipar (bertelur-melahirkan)', 'Lebah' => 'ovipar (bertelur)',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $hewanBiak,
            'Hewan %s berkembang biak dengan cara...',
            null,
            3,
            ['bertunas (vegetatif)', 'membelah diri (fragmentasi)', 'spora']
        ));

        $hewanGolongan = [
            'Kucing' => 'mamalia', 'Ayam' => 'aves (unggas)', 'Ikan mas' => 'pisces (ikan)',
            'Katak' => 'amfibi', 'Ular' => 'reptil', 'Buaya' => 'reptil',
            'Kupu-kupu' => 'serangga (insekta)', 'Nyamuk' => 'serangga (insekta)', 'Sapi' => 'mamalia',
            'Kambing' => 'mamalia', 'Paus' => 'mamalia', 'Lumba-lumba' => 'mamalia',
            'Cicak' => 'reptil', 'Penyu' => 'reptil', 'Burung elang' => 'aves (unggas)',
            'Bebek' => 'aves (unggas)', 'Kadal' => 'reptil', 'Kelinci' => 'mamalia',
            'Hiu' => 'pisces (ikan)', 'Lebah' => 'serangga (insekta)',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $hewanGolongan,
            'Hewan %s termasuk dalam golongan...'
        ));

        // D. Perubahan wujud zat
        $wujudZat = [
            'Mencair' => 'perubahan wujud benda padat menjadi cair',
            'Membeku' => 'perubahan wujud benda cair menjadi padat',
            'Menguap' => 'perubahan wujud benda cair menjadi gas',
            'Mengembun' => 'perubahan wujud benda gas menjadi cair',
            'Menyublim' => 'perubahan wujud benda padat menjadi gas',
            'Mengkristal' => 'perubahan wujud benda gas menjadi padat',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $wujudZat,
            'Apa yang dimaksud dengan peristiwa %s?',
            'Peristiwa perubahan wujud berupa %s disebut...'
        ));

        $wujudContoh = [
            'Es batu yang meleleh menjadi air' => 'Mencair',
            'Air yang didinginkan hingga menjadi es' => 'Membeku',
            'Air yang dipanaskan hingga menjadi uap' => 'Menguap',
            'Titik-titik air yang muncul pada tutup gelas berisi es' => 'Mengembun',
            'Kapur barus yang lama-kelamaan habis di udara terbuka' => 'Menyublim',
            'Uap air di udara yang berubah menjadi kristal es (salju)' => 'Mengkristal',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $wujudContoh,
            'Peristiwa "%s" merupakan contoh perubahan wujud...'
        ));

        // E. Gaya & energi
        $gayaEnergi = [
            'Gaya gravitasi' => 'gaya tarik bumi terhadap suatu benda',
            'Gaya gesek' => 'gaya yang timbul akibat sentuhan dua permukaan benda',
            'Gaya magnet' => 'gaya tarik atau tolak yang ditimbulkan oleh magnet',
            'Gaya otot' => 'gaya yang dihasilkan oleh otot tubuh manusia atau hewan',
            'Gaya listrik' => 'gaya yang ditimbulkan oleh muatan listrik',
            'Energi kinetik' => 'energi yang dimiliki oleh benda karena geraknya',
            'Energi potensial' => 'energi yang dimiliki oleh benda karena kedudukan atau posisinya',
            'Energi panas' => 'energi yang berpindah karena adanya perbedaan suhu',
            'Energi cahaya' => 'energi yang dipancarkan oleh sumber cahaya',
            'Energi bunyi' => 'energi yang dihasilkan oleh benda yang bergetar',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $gayaEnergi,
            'Apa yang dimaksud dengan %s?',
            'Pengertian "%s" merupakan penjelasan dari istilah...'
        ));

        // F. Ekosistem & rantai makanan
        $ekosistem = [
            'Produsen' => 'organisme yang mampu membuat makanannya sendiri, contohnya tumbuhan hijau',
            'Konsumen tingkat I' => 'organisme pemakan tumbuhan (herbivora)',
            'Konsumen tingkat II' => 'organisme pemakan konsumen tingkat I (karnivora pemakan herbivora)',
            'Konsumen tingkat III' => 'organisme puncak yang memakan konsumen tingkat II',
            'Pengurai' => 'organisme yang menguraikan sisa-sisa makhluk hidup yang telah mati',
            'Herbivora' => 'hewan pemakan tumbuhan',
            'Karnivora' => 'hewan pemakan daging atau hewan lain',
            'Omnivora' => 'hewan pemakan tumbuhan sekaligus hewan lain',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $ekosistem,
            'Dalam ekosistem, %s adalah...',
            'Peran dalam ekosistem yang sesuai dengan "%s" adalah...'
        ));

        return $items;
    }

    // PJOK (pool >= 150 soal berbasis data fakta, lalu diacak & diambil 150)

    private static function pjokQuestions(): array
    {
        $items = [];

        // A. Cabang olahraga bola besar & bola kecil
        $cabangOlahraga = [
            'Sepak bola' => 'dimainkan oleh 11 pemain per tim dengan tujuan memasukkan bola ke gawang lawan',
            'Bola basket' => 'dimainkan oleh 5 pemain per tim dengan tujuan memasukkan bola ke dalam keranjang (ring)',
            'Bola voli' => 'dimainkan oleh 6 pemain per tim dan bola tidak boleh menyentuh lantai di area sendiri',
            'Futsal' => 'dimainkan oleh 5 pemain per tim di lapangan berukuran lebih kecil dari sepak bola',
            'Bulu tangkis' => 'dimainkan menggunakan raket dan shuttlecock',
            'Tenis meja' => 'dimainkan menggunakan bet dan bola pingpong di atas meja',
            'Kasti' => 'olahraga bola kecil beregu yang dimainkan menggunakan pemukul kayu',
            'Softball' => 'dimainkan oleh 9 pemain per tim menggunakan pemukul dan bola kecil',
            'Sepak takraw' => 'dimainkan menggunakan bola anyaman rotan dan dimainkan dengan kaki melewati net',
            'Bola tangan' => 'dimainkan dengan cara melempar dan menangkap bola menggunakan tangan menuju gawang lawan',
            'Hoki' => 'dimainkan menggunakan tongkat (stick) untuk menggiring bola menuju gawang lawan',
            'Golf' => 'bertujuan memasukkan bola ke dalam lubang menggunakan tongkat dengan pukulan sesedikit mungkin',
            'Panahan' => 'olahraga menembakkan anak panah ke arah sasaran menggunakan busur',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $cabangOlahraga,
            'Berikut ini adalah ciri dari cabang olahraga %s, yaitu...',
            'Cabang olahraga yang %s adalah...'
        ));

        // B. Atletik
        $atletik = [
            'Lari jarak pendek (sprint)' => 'nomor lari yang menempuh jarak 100m, 200m, atau 400m dengan kecepatan maksimal',
            'Lari jarak menengah' => 'nomor lari dengan jarak tempuh 800m sampai 1500m',
            'Lari jarak jauh' => 'nomor lari dengan jarak tempuh di atas 3000m hingga marathon',
            'Lari estafet' => 'nomor lari beregu yang menggunakan tongkat yang diberikan secara berantai',
            'Lompat jauh' => 'nomor atletik yang mengutamakan jarak lompatan sejauh mungkin ke bak pasir',
            'Lompat tinggi' => 'nomor atletik yang mengutamakan ketinggian lompatan melewati mistar',
            'Lompat galah' => 'nomor lompat yang menggunakan bantuan tongkat/galah untuk melewati mistar',
            'Tolak peluru' => 'nomor atletik melempar peluru logam sejauh mungkin dengan cara ditolak dari bahu',
            'Lempar lembing' => 'nomor atletik melempar lembing sejauh mungkin',
            'Lempar cakram' => 'nomor atletik melempar cakram sejauh mungkin dengan cara diputar',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $atletik,
            'Nomor atletik yang disebut %s memiliki ciri...',
            'Nomor atletik yang %s disebut...'
        ));

        // C. Komponen kebugaran jasmani
        $kebugaran = [
            'Kekuatan (strength)' => 'kemampuan otot untuk menggunakan tenaga secara maksimal',
            'Daya tahan (endurance)' => 'kemampuan tubuh untuk bekerja dalam waktu lama tanpa mengalami kelelahan berarti',
            'Kecepatan (speed)' => 'kemampuan berpindah tempat dalam waktu sesingkat mungkin',
            'Kelentukan (flexibility)' => 'kemampuan tubuh untuk bergerak leluasa pada persendian',
            'Kelincahan (agility)' => 'kemampuan mengubah arah tubuh dengan cepat tanpa kehilangan keseimbangan',
            'Keseimbangan (balance)' => 'kemampuan mempertahankan posisi tubuh',
            'Koordinasi (coordination)' => 'kemampuan menggabungkan beberapa gerakan menjadi satu gerakan yang efektif',
            'Daya ledak (power)' => 'kemampuan menggunakan kekuatan maksimal dalam waktu sesingkat mungkin',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $kebugaran,
            'Komponen kebugaran jasmani %s adalah...',
            'Komponen kebugaran jasmani yang merupakan %s disebut...'
        ));

        // D. Gaya renang
        $renang = [
            'Gaya bebas (crawl)' => 'gerakan tangan mengayuh ke depan secara bergantian dan kaki menendang naik-turun',
            'Gaya dada (breast stroke)' => 'gerakan tangan seperti membelah air ke samping dan kaki seperti katak',
            'Gaya punggung (back stroke)' => 'posisi punggung menghadap ke permukaan air saat berenang',
            'Gaya kupu-kupu (butterfly)' => 'kedua tangan digerakkan bersamaan ke depan dan kaki menendang seperti lumba-lumba',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $renang,
            'Ciri dari %s dalam renang adalah...',
            'Gaya renang yang memiliki ciri %s disebut...'
        ));

        // E. Kesehatan & gizi
        $gizi = [
            'Karbohidrat' => 'zat gizi utama sebagai sumber tenaga bagi tubuh',
            'Protein' => 'zat gizi untuk pertumbuhan dan perbaikan sel tubuh',
            'Lemak' => 'zat gizi sebagai cadangan energi dan pelindung organ tubuh',
            'Vitamin' => 'zat gizi yang membantu menjaga daya tahan tubuh',
            'Mineral' => 'zat gizi yang membantu proses metabolisme dan pertumbuhan tulang',
            'Serat' => 'zat gizi yang membantu melancarkan pencernaan',
            'Vitamin C' => 'vitamin yang meningkatkan daya tahan tubuh dan banyak terdapat pada buah jeruk',
            'Vitamin A' => 'vitamin yang baik untuk kesehatan mata dan banyak terdapat pada wortel',
            'Vitamin D' => 'vitamin yang membantu penyerapan kalsium dan diperoleh dari sinar matahari pagi',
            'Kalsium' => 'mineral yang penting untuk pertumbuhan dan kekuatan tulang serta gigi',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $gizi,
            'Zat gizi %s berperan sebagai...',
            'Zat gizi yang berperan sebagai %s adalah...'
        ));

        // F. Pencak silat
        $pencakSilat = [
            'Kuda-kuda' => 'sikap dasar posisi kaki dalam pencak silat untuk menjaga keseimbangan',
            'Pukulan' => 'teknik serangan menggunakan tangan dalam pencak silat',
            'Tendangan' => 'teknik serangan menggunakan kaki dalam pencak silat',
            'Elakan' => 'teknik menghindar dengan cara memindahkan posisi togok/badan',
            'Tangkisan' => 'teknik membendung atau menahan serangan lawan',
            'Bantingan' => 'teknik menjatuhkan lawan ke tanah',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $pencakSilat,
            'Istilah %s dalam pencak silat merupakan...',
            'Teknik yang merupakan %s dalam pencak silat disebut...'
        ));

        // G. Peraturan & istilah pertandingan
        $peraturan = [
            'Pertandingan sepak bola' => 'berlangsung selama 2x45 menit',
            'Pertandingan bola basket' => 'berlangsung dalam 4 babak (kuarter)',
            'Satu set dalam bola voli' => 'dimenangkan oleh tim yang lebih dulu mencapai 25 poin dengan selisih minimal 2 poin',
            'Satu set dalam bulu tangkis' => 'menggunakan sistem 21 poin dengan total 3 set kemenangan',
            'Satu set dalam tenis meja' => 'menggunakan sistem 11 poin',
            'Perpanjangan waktu (extra time)' => 'dilakukan apabila skor imbang setelah waktu normal pada pertandingan sistem gugur',
            'Adu penalti' => 'dilakukan apabila skor masih imbang setelah perpanjangan waktu',
            'Kartu kuning' => 'diberikan wasit sebagai peringatan kepada pemain yang melakukan pelanggaran',
            'Kartu merah' => 'diberikan wasit kepada pemain yang harus dikeluarkan dari lapangan',
            'Wasit' => 'orang yang bertugas memimpin dan mengawasi jalannya pertandingan',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $peraturan,
            '%s memiliki aturan, yaitu...',
            'Aturan "%s" berlaku pada...'
        ));

        // H. P3K & keselamatan olahraga
        $keselamatan = [
            'Pemanasan (warming up)' => 'aktivitas sebelum berolahraga untuk mempersiapkan otot dan mencegah cedera',
            'Pendinginan (cooling down)' => 'aktivitas setelah berolahraga untuk mengembalikan kondisi tubuh secara bertahap',
            'Cedera keseleo' => 'cedera akibat pergeseran sendi yang melebihi batas normal',
            'P3K' => 'pertolongan pertama yang diberikan sebelum korban dibawa ke tenaga medis',
            'Kram otot' => 'kondisi otot yang menegang secara tiba-tiba akibat kelelahan',
            'Denyut nadi' => 'jumlah detak jantung yang dihitung untuk mengukur intensitas latihan',
            'Dehidrasi' => 'kekurangan cairan tubuh akibat berolahraga tanpa minum yang cukup',
            'Sportivitas' => 'sikap menjunjung tinggi kejujuran dan menghormati lawan dalam bertanding',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $keselamatan,
            'Istilah %s dalam olahraga berarti...',
            'Istilah yang berarti %s adalah...'
        ));

        // I. Senam
        $senam = [
            'Senam lantai' => 'senam yang dilakukan tanpa alat di atas matras',
            'Senam irama' => 'senam yang dilakukan mengikuti irama musik dengan atau tanpa alat',
            'Guling depan (forward roll)' => 'gerakan senam lantai berguling ke arah depan',
            'Guling belakang (backward roll)' => 'gerakan senam lantai berguling ke arah belakang',
            'Sikap lilin' => 'gerakan senam lantai dengan posisi tubuh terbalik ditopang oleh pundak dan tangan',
            'Kayang' => 'gerakan senam lantai membentuk badan seperti busur dengan bertumpu pada tangan dan kaki',
        ];
        $items = array_merge($items, self::buildLookupQuestions(
            $senam,
            '%s merupakan gerakan senam yang...',
            'Gerakan senam yang %s disebut...'
        ));

        return $items;
    }
}