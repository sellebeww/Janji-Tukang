<?php

namespace App\Transcription;

interface Transcriber
{
    /** Ubah berkas audio menjadi teks cerita (lalu masuk ke AgreementDrafter). */
    public function transcribe(string $audioPath): string;
}
