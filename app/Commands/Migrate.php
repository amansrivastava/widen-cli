<?php

namespace App\Commands;

use App\Traits\Token;
use Illuminate\Support\Facades\Storage;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Process\Process;

class Migrate extends Command
{
    use Token;
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'migrate {--token= : Widen Authentication token.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'End-to-end Widen migration.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->title('AcquiaDAM');
        $this->getToken();
        $this->task("Check pre-requisites.", function() {
            $this->checkCommand('acli');
            $this->checkCommand('composer');
            $this->checkCommand('composer1');
            $this->checkCommand('drush');

            if (Storage::missing('composer.json')) {
                throw new \Exception("Unable to read composer.json file.");
            }
        });

        $this->task('Upgrade drupal/media_acquiadam', function() {
            $composer = $this->choice('Composer version', ['composer', 'composer1'], '0');
            $process = Process::fromShellCommandline($composer . ' require drupal/media_acquiadam:^2 -o -n');
            $status = $process->run();
            if($status === 0) {
                $this->info('Module drupal/media_acquiadam upgraded to v2.');
            }
            else {
                throw new \Exception("Failed to update module drupal/media_acquiadam.");
            }
        });

        $this->task('Update database', function() {
            $process = Process::fromShellCommandline('drush updb -y');
            $status = $process->run();
            if($status === 0) {
                return true;
            }
            return false;
        });

        $this->task('Configure module.', function() {
            Process::fromShellCommandline('drush cr')->run();
            Process::fromShellCommandline('drush config:set media_acquiadam.settings token ' . $this->token . ' -y ')->run();
            Process::fromShellCommandline('drush config:set media_acquiadam.settings domain related.widencollective.com -y ')->run();
        });

        $this->task('Re-save media types.', function() {
            return false;
        });

        $this->task('Migrate media.', function() {
            $token = $this->getToken();
            $file = "export" . rand(4,6) . ".csv";
            $this->call('export:webdam-mapping', ['--token' => $token, '-f' => $file]);
            $import = Process::fromShellCommandline('drush acquiadam:update ' . getcwd() . DIRECTORY_SEPARATOR . $file);
        });

        $this->task('Optional media sync.', function() {
            return false;
        });

        $this->task('Export Config.', function() {
            Process::fromShellCommandline('drush cex -y ')->run();
        });

        $this->task('Interactive add to git.', function() {
            return false;
        });

        $this->task('Code commit.', function() {
            return false;
        });

        $this->task('Smoke testing.', function() {
            return false;
        });
    }

    public function checkCommand(string $command) {
        $this->task('Checking ' . $command, function() use ($command) {
            $status = Process::fromShellCommandline('type -P ' . $command)->run();
            if($status === 0) {
                return true;
            }
            return false;
        });
    }
}
