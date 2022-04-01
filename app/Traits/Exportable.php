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

    public function getFileName() {
        $this->filename = $this->option('filename') ?? 'export.csv';
    }

    public function addHeaderToFile($data) {
        Storage::put($this->filename, $data);
    }

    public function addToFile($data) {
        Storage::append($this->filename, $data);
    }
}
