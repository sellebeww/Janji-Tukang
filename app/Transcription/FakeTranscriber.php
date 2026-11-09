<?php

namespace App\Transcription;

/** Satu-satunya driver saat ini. Tidak membaca audio dan tidak memanggil layanan apa pun. */
class FakeTranscriber implements Transcriber
{
    public function transcribe(string $audioPath): string
    {
        return 'Pasang keramik kamar mandi 2x3 meter, total 1.500.000, bayar dua kali. (transkrip palsu)';
    }
}
