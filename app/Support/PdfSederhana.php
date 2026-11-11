<?php

namespace App\Support;

/**
 * Pembuat PDF ringan tanpa library tambahan: teks, garis, dan tabel di kertas A4.
 * Memakai font bawaan PDF (Helvetica), jadi tidak perlu menyematkan file font.
 * Cukup untuk laporan data; untuk tata letak rumit gunakan library seperti dompdf.
 */
class PdfSederhana
{
    private const LEBAR = 595.28;   // A4 dalam point
    private const TINGGI = 841.89;
    private const MARGIN = 50;

    /** Lebar huruf Helvetica dan Helvetica-Bold (per 1000 unit), karakter 32..126 */
    private const W_NORMAL = [278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584];
    private const W_TEBAL = [278,333,474,556,556,889,722,238,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,333,333,584,584,584,611,975,722,722,722,722,667,611,778,722,278,556,722,611,833,722,778,667,778,722,667,611,722,667,944,667,667,611,333,278,333,584,556,333,556,611,556,611,556,333,611,611,278,278,556,278,889,611,611,611,611,389,556,333,611,556,778,556,556,500,389,280,389,584];

    /** @var string[] isi setiap halaman */
    private array $halaman = [];
    private string $isi = '';
    private float $y = 0;

    public function __construct(private string $judulDokumen = 'Dokumen', private string $kaki = '')
    {
        $this->halamanBaru();
    }

    /* ---------- elemen ---------- */

    public function teks(string $teks, float $ukuran = 10, bool $tebal = false, string $rata = 'kiri', ?array $warna = null): static
    {
        $lebarMaks = self::LEBAR - 2 * self::MARGIN;
        foreach ($this->bungkus($teks, $lebarMaks, $ukuran, $tebal) as $baris) {
            $tinggi = $ukuran * 1.4;
            $this->pastikanRuang($tinggi);
            $x = self::MARGIN;
            if ($rata === 'tengah') {
                $x = (self::LEBAR - $this->lebarTeks($baris, $ukuran, $tebal)) / 2;
            } elseif ($rata === 'kanan') {
                $x = self::LEBAR - self::MARGIN - $this->lebarTeks($baris, $ukuran, $tebal);
            }
            $this->tulis($x, $this->y - $ukuran, $baris, $ukuran, $tebal, $warna);
            $this->y -= $tinggi;
        }

        return $this;
    }

    public function jarak(float $pt): static
    {
        $this->y -= $pt;

        return $this;
    }

    public function garis(float $tebal = 0.8): static
    {
        $this->pastikanRuang(6);
        $this->isi .= sprintf("%.2F w %.2F %.2F m %.2F %.2F l S\n", $tebal, self::MARGIN, $this->y, self::LEBAR - self::MARGIN, $this->y);
        $this->y -= 6;

        return $this;
    }

    public function judulBagian(string $judul): static
    {
        $this->pastikanRuang(40);
        $this->jarak(8)->teks($judul, 12, true, 'kiri', [0.04, 0.15, 0.39]);
        $this->isi .= sprintf("0.85 0.88 0.93 RG 0.8 w %.2F %.2F m %.2F %.2F l S 0 0 0 RG\n", self::MARGIN, $this->y + 2, self::LEBAR - self::MARGIN, $this->y + 2);

        return $this->jarak(6);
    }

    /** Daftar "Label : nilai" dua kolom */
    public function pasangan(array $data, float $lebarLabel = 150, float $ukuran = 10): static
    {
        $lebarNilai = self::LEBAR - 2 * self::MARGIN - $lebarLabel - 12;
        foreach ($data as $label => $nilai) {
            $baris = $this->bungkus((string) $nilai, $lebarNilai, $ukuran, false);
            $tinggi = max(1, count($baris)) * $ukuran * 1.4;
            $this->pastikanRuang($tinggi);
            $this->tulis(self::MARGIN, $this->y - $ukuran, (string) $label, $ukuran, false, [0.4, 0.42, 0.47]);
            $this->tulis(self::MARGIN + $lebarLabel, $this->y - $ukuran, ':', $ukuran);
            foreach ($baris as $i => $b) {
                $this->tulis(self::MARGIN + $lebarLabel + 12, $this->y - $ukuran - $i * $ukuran * 1.4, $b, $ukuran, true);
            }
            $this->y -= $tinggi;
        }

        return $this;
    }

    /**
     * @param string[]   $kepala judul kolom
     * @param string[][] $baris  isi tabel
     * @param float[]    $lebar  proporsi lebar kolom (jumlahnya bebas, akan dinormalisasi)
     */
    public function tabel(array $kepala, array $baris, array $lebar, float $ukuran = 9): static
    {
        $total = self::LEBAR - 2 * self::MARGIN;
        $jumlah = array_sum($lebar);
        $kolom = array_map(fn ($l) => $l / $jumlah * $total, $lebar);
        $pad = 4;

        $gambarBaris = function (array $sel, bool $judul) use ($kolom, $pad, $ukuran) {
            $isiSel = [];
            $maks = 1;
            foreach ($sel as $i => $t) {
                $isiSel[$i] = $this->bungkus((string) $t, $kolom[$i] - 2 * $pad, $ukuran, $judul);
                $maks = max($maks, count($isiSel[$i]));
            }
            $tinggi = $maks * $ukuran * 1.3 + 2 * $pad;

            return [$isiSel, $tinggi];
        };

        $cetak = function (array $isiSel, float $tinggi, bool $judul) use ($kolom, $pad, $ukuran) {
            $x = self::MARGIN;
            if ($judul) {
                $this->isi .= sprintf("0.93 0.95 0.98 rg %.2F %.2F %.2F %.2F re f 0 0 0 rg\n", $x, $this->y - $tinggi, array_sum($kolom), $tinggi);
            }
            foreach ($kolom as $i => $w) {
                $this->isi .= sprintf("0.8 0.83 0.88 RG 0.5 w %.2F %.2F %.2F %.2F re S 0 0 0 RG\n", $x, $this->y - $tinggi, $w, $tinggi);
                foreach ($isiSel[$i] ?? [] as $n => $b) {
                    $this->tulis($x + $pad, $this->y - $pad - $ukuran - $n * $ukuran * 1.3 + 1, $b, $ukuran, $judul);
                }
                $x += $w;
            }
            $this->y -= $tinggi;
        };

        [$selKepala, $tKepala] = $gambarBaris($kepala, true);
        $this->pastikanRuang($tKepala + 20);
        $cetak($selKepala, $tKepala, true);

        foreach ($baris as $b) {
            [$sel, $t] = $gambarBaris($b, false);
            if ($this->y - $t < self::MARGIN + 20) {
                $this->halamanBaru();
                $cetak($selKepala, $tKepala, true);   // ulangi judul kolom di halaman baru
            }
            $cetak($sel, $t, false);
        }

        return $this->jarak(4);
    }

    /* ---------- keluaran ---------- */

    public function keluaran(): string
    {
        $this->tutupHalaman();
        $n = count($this->halaman);

        $obj = [];
        $obj[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = [];
        $obj[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $obj[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $obj[5] = '<< /Title ' . $this->str($this->judulDokumen) . ' /Producer (SIDARYUS) /CreationDate (D:' . date('YmdHis') . ') >>';

        $id = 6;
        foreach ($this->halaman as $i => $isi) {
            // nomor halaman di kaki
            $kaki = trim($this->kaki . '   Halaman ' . ($i + 1) . ' dari ' . $n);
            $isi .= $this->perintahTeks(self::MARGIN, 28, $kaki, 8, false, [0.45, 0.47, 0.52]);

            $hal = $id++;
            $aliran = $id++;
            $kids[] = "{$hal} 0 R";
            $obj[$hal] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>', self::LEBAR, self::TINGGI, $aliran);
            $obj[$aliran] = '<< /Length ' . strlen($isi) . " >>\nstream\n" . $isi . "\nendstream";
        }
        $obj[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $n . ' >>';
        ksort($obj);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $posisi = [];
        foreach ($obj as $no => $isi) {
            $posisi[$no] = strlen($pdf);
            $pdf .= "{$no} 0 obj\n{$isi}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref' . "\n0 " . (count($obj) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($obj); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $posisi[$i]);
        }
        $pdf .= 'trailer' . "\n<< /Size " . (count($obj) + 1) . " /Root 1 0 R /Info 5 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    /* ---------- pembantu ---------- */

    private function halamanBaru(): void
    {
        if ($this->isi !== '') {
            $this->tutupHalaman();
        }
        $this->isi = '';
        $this->y = self::TINGGI - self::MARGIN;
    }

    private function tutupHalaman(): void
    {
        if ($this->isi !== '' || $this->halaman === []) {
            $this->halaman[] = $this->isi;
            $this->isi = '';
        }
    }

    private function pastikanRuang(float $tinggi): void
    {
        if ($this->y - $tinggi < self::MARGIN + 20) {
            $this->halamanBaru();
        }
    }

    private function tulis(float $x, float $y, string $teks, float $ukuran, bool $tebal = false, ?array $warna = null): void
    {
        $this->isi .= $this->perintahTeks($x, $y, $teks, $ukuran, $tebal, $warna);
    }

    private function perintahTeks(float $x, float $y, string $teks, float $ukuran, bool $tebal, ?array $warna): string
    {
        $c = $warna ? sprintf('%.2F %.2F %.2F rg ', ...$warna) : '';

        return sprintf("BT %s/%s %.1F Tf %.2F %.2F Td %s Tj ET%s\n", $c, $tebal ? 'F2' : 'F1', $ukuran, $x, $y, $this->str($teks), $warna ? ' 0 0 0 rg' : '');
    }

    /** Ubah ke Windows-1252 dan escape untuk string PDF */
    private function str(string $teks): string
    {
        $enc = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $teks);
        $enc = $enc === false ? preg_replace('/[^\x20-\x7E]/', '?', $teks) : $enc;

        return '(' . strtr($enc, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)', "\r" => '', "\n" => ' ']) . ')';
    }

    private function lebarTeks(string $teks, float $ukuran, bool $tebal): float
    {
        $tabel = $tebal ? self::W_TEBAL : self::W_NORMAL;
        $w = 0;
        $enc = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $teks) ?: $teks;
        for ($i = 0, $n = strlen($enc); $i < $n; $i++) {
            $o = ord($enc[$i]);
            $w += ($o >= 32 && $o <= 126) ? $tabel[$o - 32] : 556;
        }

        return $w * $ukuran / 1000;
    }

    /** @return string[] baris yang muat di lebar tertentu */
    private function bungkus(string $teks, float $lebarMaks, float $ukuran, bool $tebal): array
    {
        $hasil = [];
        foreach (preg_split('/\R/', $teks) as $paragraf) {
            $baris = '';
            foreach (preg_split('/\s+/', trim($paragraf)) as $kata) {
                $coba = $baris === '' ? $kata : "{$baris} {$kata}";
                if ($this->lebarTeks($coba, $ukuran, $tebal) <= $lebarMaks) {
                    $baris = $coba;
                    continue;
                }
                if ($baris !== '') {
                    $hasil[] = $baris;
                }
                // kata yang lebih panjang dari kolom dipotong per huruf
                while ($this->lebarTeks($kata, $ukuran, $tebal) > $lebarMaks && mb_strlen($kata) > 1) {
                    $potong = mb_strlen($kata);
                    while ($potong > 1 && $this->lebarTeks(mb_substr($kata, 0, $potong), $ukuran, $tebal) > $lebarMaks) {
                        $potong--;
                    }
                    $hasil[] = mb_substr($kata, 0, $potong);
                    $kata = mb_substr($kata, $potong);
                }
                $baris = $kata;
            }
            $hasil[] = $baris;
        }

        return $hasil;
    }
}
