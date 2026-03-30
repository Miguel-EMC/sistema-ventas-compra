<?php

namespace App\Support\Pdf;

require_once __DIR__.'/Fpdf/fpdf.php';

class BillingDocumentPdf extends \FPDF
{
    public function __construct()
    {
        parent::__construct('P', 'mm', 'Letter');

        $this->SetMargins(14, 14, 14);
        $this->SetAutoPageBreak(true, 16);
        $this->SetTitle($this->safeText('VentasPOS'));
        $this->SetAuthor($this->safeText('VentasPOS API'));
        $this->SetCreator($this->safeText('VentasPOS API'));
    }

    public function safeText(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            return '';
        }

        $normalized = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $normalized = strtr($normalized, [
            'á' => 'a',
            'Á' => 'A',
            'é' => 'e',
            'É' => 'E',
            'í' => 'i',
            'Í' => 'I',
            'ó' => 'o',
            'Ó' => 'O',
            'ú' => 'u',
            'Ú' => 'U',
            'ñ' => 'n',
            'Ñ' => 'N',
            'ü' => 'u',
            'Ü' => 'U',
            'º' => 'o',
            'ª' => 'a',
            '“' => '"',
            '”' => '"',
            '‘' => "'",
            '’' => "'",
            '–' => '-',
            '—' => '-',
        ]);

        if (\function_exists('iconv')) {
            $converted = \iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $normalized);

            if ($converted !== false) {
                return $converted;
            }
        }

        return preg_replace('/[^\x20-\x7E]/', '', $normalized) ?? $normalized;
    }
}
