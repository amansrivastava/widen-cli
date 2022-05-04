<?php

namespace App\Commands;

use App\Traits\Exportable;
use App\Traits\Token;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Storage;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Process\Process;
use Spatie\SimpleExcel\SimpleExcelReader;


class ExportWebdamMapping extends Command
{
    use Token, Exportable;
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'export:webdam-mapping {--token= : Widen Authentication token} {--f|filename= : Output filename.} {--w|webdam_ids= : webdam_ids file.} {--a|alias= : Drush site alias.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Export Webdam to Widen mapping sheet.';

    /**
     * @var Illuminate\Support\Facades\Http
     */
    protected $client;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->title("Webdam to Widen mapping");
        $this->getToken();
        $filename = "export" . rand(0,9) . ".csv";
        $this->getFileName($filename);
        $this->checkConnection();
        $alias = $this->option('alias');
        $webdam_ids = 'webdam_ids.csv';
        if(!$this->option('webdam_ids')) {
            $this->task('Generate Webdam ID list', function() use ($alias, $webdam_ids) {
                Process::fromShellCommandline('drush ' . $alias . 'sqlq "select field_acquiadam_asset_id_value from media__field_acquiadam_asset_id" > ' . $webdam_ids)->run();
            });
        }
        else {
            $webdam_ids = $this->option('webdam_ids');
            if(!Storage::exists($webdam_ids)) {
                throw new FileNotFoundException("File $webdam_ids not found");
            }
        }
        $rows = SimpleExcelReader::create($webdam_ids)->getRows();
        $chunks = $rows->chunk(50);
        $this->addHeaderToFile('webdam_id,widen_id', $this->filename);
            $chunks->each(function($chunk){
                $this->task("Writing to file ..." . $chunk->count());
                $ids = implode(" or ", $chunk->collapse()->all());
                $query = 'iwi: ' . $ids;
                $assets = $this->getAssets($query);
                $collection = collect($assets);
                $collection->each(function ($item) {
                    $data = $item['metadata']['fields']['webdam_id'][0] . "," . $item['id'];
                    $this->addToFile($data, $this->filename);
                });
            });
    }

    public function getAssets($query) {
        $options = [
            'include_deleted' => "true",
            'include_archived' => "true",
            'limit' => 100,
            'scroll' => "true",
            'expand' => 'metadata',
            'query' => $query
        ];
        $response = $this->client->get('/assets/search', $options);
        if($response->successful()) {
            return $response->json()['items'];
        }
        else return [];

    }

}
