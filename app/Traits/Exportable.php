<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait Exportable
{
    /**
     * Output filename.
     *
     * @var string
     */
    protected string $filename;

    public function getFileName($filename = 'export.csv') {
        $this->filename = $this->option('filename') ?? $filename;
    }

    public function addHeaderToFile($data, $filename) {
        Storage::disk('local')->put($filename, $data);
    }

    public function addToFile($data, $filename) {
        Storage::disk('local')->append($filename, $data);
    }
}
