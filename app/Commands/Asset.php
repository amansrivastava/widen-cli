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
        $row = "";
        $data = $response->json();
        foreach($data['metadata']['fields'] as $key => $value) {
            $value = implode(',', $value);
            $row .= "<tr><td>{$key}</td><td>{$value}</td></tr>";
        }
        render(<<<HTML
                <table>
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tr><td>id</td><td>{$data['id']}</td></tr>
                <tr><td>external_id</td><td>{$data['external_id']}</td></tr>
                <tr><td>filename</td><td>{$data['filename']}</td></tr>
                <tr><td>created_date</td><td>{$data['created_date']}</td></tr>
                <tr><td>last_update_date</td><td>{$data['last_update_date']}</td></tr>
                <tr><td>file_upload_date</td><td>{$data['file_upload_date']}</td></tr>
                <tr><td>deleted_date</td><td>{$data['deleted_date']}</td></tr>
                <tr><td>released_and_not_expired</td><td>{$data['released_and_not_expired']}</td></tr>
                {$row}
            </table>
        HTML);
    }

}
