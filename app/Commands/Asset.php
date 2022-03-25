<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;
use function Termwind\{render};
use Illuminate\Support\Facades\Http;

class Asset extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'asset {uuid : Asset UUID.} {token : Widen Authentication Token.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Get the asset details.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $asset_id = $this->argument('uuid');
        $headers = [
            'authorization' => 'Bearer ' . $this->argument('token')
        ];
        $client = Http::widen()->withHeaders($headers);
        $response = $client->get("/assets/$asset_id?expand=metadata");
        if($response->failed()) {
            return $this->error("Asset not found.");
        }
        $data = $response->json();
        $rows = [
            ['id', $data['id']],
            ['external_id', $data['external_id']],
            ['filename', $data['filename']],
            ['created_date', $data['created_date']],
            ['last_update_date', $data['last_update_date']],
            ['file_upload_date', $data['file_upload_date']],
            ['deleted_date', $data['deleted_date']],
            ['released_and_not_expired', $data['released_and_not_expired']],
        ];
        foreach($data['metadata']['fields'] as $key => $value) {
            $value = implode(',', $value);
            $rows[] = [$key,$value];
        }
        $this->table(['Key', 'Value'],
            $rows
        );
    }

}
