<?php

namespace App\Support;

trait GeneratePDF
{
    public function generatePDF(string $content)
    {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
        ]);
        // to support unicode characters
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->WriteHTML($content);
        $mpdf->Output();
    }
}
