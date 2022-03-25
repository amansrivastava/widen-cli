<?php

namespace App\Commands;

use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;
use Illuminate\Support\Facades\Storage;

class ExportCSV extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'export:csv {token : Widen authentication Token.}{--f|filename= : Filename of the output file.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Export DATA in CSV format.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->loadAssets();
    }

    public function loadAssets($scroll_id = NULL, $page = 1) {
        $headers = [
            'authorization' => 'Bearer ' . $this->argument('token')
        ];
        $client = Http::widen()->withHeaders($headers);
        $options = [
            'include_deleted' => "true",
            'include_archived' => "true",
            'limit' => 100,
            'scroll' => "true",
            'expand' => 'metadata'
        ];
        if($scroll_id) {
            $response = $client->get('/assets/search/scroll', [
                'scroll_id' => $scroll_id,
                'expand' => 'metadata'
            ]);
        }
        else{
            $response = $client->get('/assets/search', $options);
            if($response->successful()) {
                $this->info("Total assets are " . $response->json('total_count'));
                $filename = $this->option('filename') ?? 'export.csv';
                Storage::disk('local')->prepend($filename, "webdam_id", "widen_id");
            }
        }
        if($response->successful()) {
            $this->task("[$page] Writing assets ... ", $this->parseAsset($response->json('items')));
            if($response->json('scroll_id') && !empty($response->json('items'))) {
                $this->loadAssets($response->json('scroll_id'), ++$page);
            }
            else {
                $this->task("All assets are exported.");
            }
        }
    }

    public function parseAsset($assets) {
        foreach($assets as $asset) {
            if (!empty($asset['metadata']['fields']['webdam_id'])) {
                $this->writeToFile($asset['metadata']['fields']['webdam_id'][0] . "," . $asset['id'], "\n");
            }
        }
    }

    public function writeToFile($data) {
        $filename = $this->option('filename') ?? 'export.csv';
        Storage::disk('local')->append($filename, $data);
    }
}
