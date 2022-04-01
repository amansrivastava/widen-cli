<?php

namespace App\Commands;

use App\Traits\Exportable;
use App\Traits\Token;
use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;
use Illuminate\Support\Facades\Storage;

class ExportCSV extends Command
{
    use Exportable, Token;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'export:csv {--token : Widen authentication Token.} {--f|filename= : Filename of the output file.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Export DATA in CSV format.';

    /**
     * @var Illuminate\Support\Facades\Http
     */
    protected $client;


    /**
     * Search options.
     *
     * @var array
     */
    protected $options = [
        'include_deleted' => "true",
        'include_archived' => "true",
        'limit' => 100,
        'scroll' => "true",
        'expand' => 'metadata',
        'query' => 'iwi: -(isEmpty)'
    ];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->getToken();
        $headers = [
            'authorization' => 'Bearer ' . $this->token
        ];
        $this->client = Http::widen()->withHeaders($headers);
        $connect = $this->client->get('/user');
        $this->getFileName();
        if($connect->successful()) {
            $this->info("Successfully connected to Widen.");
            $this->loadAssets();
        }
        else {
            $this->error("Failed to connect to Widen.");
        }
    }

    public function loadAssets($scroll_id = NULL, $page = 1) {
        // Load by $scroll_id if available.
        if($scroll_id) {
            $response = $this->client->get('/assets/search/scroll', [
                'scroll_id' => $scroll_id,
                'expand' => 'metadata'
            ]);
        }
        else{
            $response = $this->client->get('/assets/search', $this->options);
            if($response->successful()) {
                $this->info("Total assets are " . $response->json('total_count'));
                Storage::disk('local')->put($this->filename, 'webdam_id,widen_id');
            }
        }
        if($response->successful()) {
            $data = $response->json();
            $collection = collect($data['items']);
            $this->task("[$page] Writing " . $collection->count() . " assets to file ... ");
            $collection->each(function ($item) {
                    $data = $item['metadata']['fields']['webdam_id'][0] . "," . $item['id'];
                    $this->addToFile($data);
                    Storage::disk('local')->append($this->filename, $data);
            });
            if($data['scroll_id'] && $collection->isNotEmpty()) {
                $this->loadAssets($data['scroll_id'], ++$page);
            }
            else {
                $this->info("All assets are exported successfully.");
            }
        }
    }
}
