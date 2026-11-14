<?php
/**
 * SimplePdf - pembuat file PDF ringan tanpa library tambahan (cukup PHP bawaan XAMPP).
 * Mendukung: halaman A4, teks Helvetica / Helvetica-Bold, garis, kotak berwarna, rata kiri/kanan/tengah.
 * Koordinat memakai titik (pt) dengan acuan pojok KIRI-ATAS halaman.
 */
class SimplePdf
{
    public const W = 595.28;
    public const H = 841.89;

    private array $pages = [];
    private int $cur = -1;

    private const WIDTHS = [
        'R' => [278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584],
        'B' => [278,333,474,556,556,889,722,238,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,333,333,584,584,584,611,975,722,722,722,722,667,611,778,722,278,556,722,611,833,722,778,667,778,722,667,611,722,667,944,667,667,611,333,278,333,584,556,333,556,611,556,611,556,333,611,611,278,278,556,278,889,611,611,611,611,389,556,333,611,556,778,556,556,500,389,280,389,584],
    ];

    public function addPage(): void
    {
        $this->pages[] = '';
        $this->cur = count($this->pages) - 1;
    }

    public function pageCount(): int
    {
        return count($this->pages);
    }

    private function out(string $s): void
    {
        $this->pages[$this->cur] .= $s . "\n";
    }

    private static function num(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }

    private static function color(array $rgb): string
    {
        return implode(' ', array_map(fn($c) => self::num($c / 255), $rgb));
    }

    public static function enc(string $s): string
    {
        $s = str_replace(["\u{2013}", "\u{2014}", "\u{2022}", "\u{00A0}"], ['-', '-', '-', ' '], $s);
        $c = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $s);
        return $c === false ? preg_replace('/[^\x20-\x7E]/', '?', $s) : $c;
    }

    public function textWidth(string $s, float $size, bool $bold = false): float
    {
        $w = 0;
        $tbl = self::WIDTHS[$bold ? 'B' : 'R'];
        $s = self::enc($s);
        for ($i = 0, $n = strlen($s); $i < $n; $i++) {
            $o = ord($s[$i]);
            $w += ($o >= 32 && $o <= 126) ? $tbl[$o - 32] : 556;
        }
        return $w * $size / 1000;
    }

    /** Potong teks agar muat dalam lebar tertentu (ditambah "..."). */
    public function fit(string $s, float $width, float $size, bool $bold = false): string
    {
        if ($this->textWidth($s, $size, $bold) <= $width) {
            return $s;
        }
        while ($s !== '' && $this->textWidth($s . '...', $size, $bold) > $width) {
            $s = mb_substr($s, 0, -1);
        }
        return $s . '...';
    }

    public function text(float $x, float $y, string $s, float $size = 10, bool $bold = false, array $rgb = [63, 48, 40], string $align = 'L', float $width = 0): void
    {
        if ($align === 'R') {
            $x = $x + $width - $this->textWidth($s, $size, $bold);
        } elseif ($align === 'C') {
            $x = $x + ($width - $this->textWidth($s, $size, $bold)) / 2;
        }
        $t = str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], self::enc($s));
        $this->out('BT ' . self::color($rgb) . ' rg /' . ($bold ? 'F2' : 'F1') . ' ' . self::num($size) . ' Tf '
            . self::num($x) . ' ' . self::num(self::H - $y) . " Td ($t) Tj ET");
    }

    public function rect(float $x, float $y, float $w, float $h, array $fill, ?array $stroke = null): void
    {
        $op = $stroke ? 'B' : 'f';
        $this->out(self::color($fill) . ' rg ' . ($stroke ? self::color($stroke) . ' RG 0.6 w ' : '')
            . self::num($x) . ' ' . self::num(self::H - $y - $h) . ' ' . self::num($w) . ' ' . self::num($h) . " re $op");
    }

    public function line(float $x1, float $y1, float $x2, float $y2, array $rgb = [222, 212, 194], float $w = 0.6): void
    {
        $this->out(self::color($rgb) . ' RG ' . self::num($w) . ' w ' . self::num($x1) . ' ' . self::num(self::H - $y1) . ' m '
            . self::num($x2) . ' ' . self::num(self::H - $y2) . ' l S');
    }

    /** Menghasilkan isi file PDF. $footer dipanggil per halaman: fn(SimplePdf $pdf, int $page, int $total). */
    public function render(?callable $footer = null): string
    {
        $total = count($this->pages);
        if ($footer) {
            foreach (array_keys($this->pages) as $i) {
                $this->cur = $i;
                $footer($this, $i + 1, $total);
            }
        }
        $objs = [];
        $objs[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objs[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objs[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $kids = [];
        $n = 5;
        foreach ($this->pages as $content) {
            $pageId = $n++;
            $contId = $n++;
            $kids[] = "$pageId 0 R";
            $objs[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::num(self::W) . ' ' . self::num(self::H) . '] '
                . '/Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $contId . ' 0 R >>';
            $objs[$contId] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "endstream";
        }
        $objs[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $total . ' >>';
        ksort($objs);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objs as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "$id 0 obj\n$body\nendobj\n";
        }
        $xref = strlen($pdf);
        $max = max(array_keys($objs));
        $pdf .= "xref\n0 " . ($max + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= $max; $i++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$i] ?? 0) . "\n";
        }
        $pdf .= "trailer\n<< /Size " . ($max + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
        return $pdf;
    }
}
