<?php

namespace App\Services;

class TreePdfExportService
{
    public function export(array $tree, string $paperSize): string
    {
        [$width, $height] = match ($paperSize) {
            'A2' => [1191, 1684], 'A3' => [842, 1191], default => [595, 842]
        };
        $lines = ['Bagan Silsilah', 'Mode: '.$tree['mode'].' | Layout: '.$tree['layout'], 'Jumlah Anggota: '.$tree['statistics']['members'], 'Jumlah Generasi: '.$tree['statistics']['generations']];
        foreach (array_slice($tree['nodes'], 0, 100) as $node) {
            $lines[] = ($node['is_alive'] ? '' : '+ ').$node['name'].' (Generasi '.$node['generation'].')';
        }
        $stream = 'BT /F1 12 Tf 40 '.($height - 50).' Td ';
        foreach ($lines as $index => $line) {
            $stream .= ($index ? '0 -16 Td ' : '').'('.$this->escape($line).') Tj ';
        }
        $stream .= 'ET';
        $objects = ["1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n", "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n",
            "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 $width $height] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >> endobj\n",
            '4 0 obj << /Length '.strlen($stream)." >> stream\n$stream\nendstream endobj\n", "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n"];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($i = 1; $i <= 5; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        return $pdf."trailer << /Size 6 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}
