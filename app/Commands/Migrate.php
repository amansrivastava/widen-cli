<?php

namespace App\Commands;

use App\Traits\Token;
use GitWrapper\GitCommand;
use GitWrapper\GitWorkingCopy;
use Illuminate\Support\Facades\Storage;
use LaravelZero\Framework\Commands\Command;
use Nette\Utils\Strings;
use Symfony\Component\Process\Process;
use GitWrapper\GitWrapper;

class Migrate extends Command
{
    use Token;
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'migrate {--token= : Widen Authentication token.} {--domain= : Domain value.}';

    /**
     * Domain value
     *
     * @var string
     */
    protected $domain;

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

        $this->info("Checking available binaries.");
        $this->checkCommand('acli');
        $this->checkCommand('composer');
        $this->checkCommand('composer1');
        $this->checkCommand('git');
        $this->checkCommand('drush');

        if (Storage::missing('composer.json')) {
            throw new \Exception("Unable to read composer.json file.");
        }

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
            Process::fromShellCommandline('drush config:set media_acquiadam.settings ' . $this->getDomain() . ' -y ')->run();

        });

        // @TODO: Re-save media types.
        $this->task('Re-save media types.', function() {
            return false;
        });

        $this->task('Migrate media.', function() {
            $file = "export" . rand(4,6) . ".csv";
            $this->call('export:webdam-mapping', ['-f' => $file]);
            $import = Process::fromShellCommandline('drush acquiadam:update ' . getcwd() . DIRECTORY_SEPARATOR . $file);
            $import->run();
        });

        $this->task('[Optional] media sync.', function() {
            $flag = $this->confirm("Do you want to run media sync? (It will take time)", false);
            if($flag == TRUE) {
                Process::fromShellCommandline('drush acquiadam:sync --method=all')->run();
                Process::fromShellCommandline('drush queue:run media_acquiadam_asset_refresh')->run();
                return $flag;
            }
            else {
                return $flag;
            }
        });

        $this->task('Export Config.', function() {
            Process::fromShellCommandline('drush cex -y ')->run();
        });

        $gitWrapper = new GitWrapper();
        $gitWrapper->streamOutput();
        $git = new GitWorkingCopy($gitWrapper, './');
        $this->task('Interactive add to git.', function() use ($git) {
            $status = $git->getStatus();
            $flag = $this->confirm("Do you want to add changes to git ?");
            if(!empty($status) && $flag) {

                $git_repo_status_arr = Strings::split($status, '#\R#');
                foreach($git_repo_status_arr as $file) {
                    if(!empty($file)) {
                        $changeTypeFlag = Strings::before(ltrim($file), " ");
                        $filename = Strings::after(ltrim($file), " ");
                        if($changeTypeFlag == 'M') {
                            $changeType = "modified";
                        }
                        elseif($changeTypeFlag == 'D') {
                            $changeType = "deleted";
                        }
                        else {
                            $changeType = "added";
                        }
                        $flag = $this->confirm("Do you want to add " . $changeType . $filename);
                        if($flag) {
                            $git->add(trim($filename));
                        }
                    }
                }
            }
            return false;
        });

        $this->task('Code commit.', function() use ($git) {
            if ($git->hasChanges()) {
                $git->status();
                $flag = $this->confirm("Do you want to commit the changes ?");
                if($flag == TRUE) {
                    $message = $this->ask("Commit message");
                    $git->commit($message);
                    return true;
                }
            }
            return false;
        });

        $this->task('Smoke testing.', function() {
            return false;
        });

        $this->task('Cleanup.', function() {
            // @TODO: Remove generated files.
            return false;
        });
    }

    public function checkCommand(string $command) {
        $this->task('Checking ' . $command, function() use ($command) {
            $status = Process::fromShellCommandline('command -v ' . $command)->run();
            if($status === 0) {
                return true;
            }
            return false;
        });
    }

    public function getDomain(): string {
        if($this->option('domain')) {
            $this->token = $this->option('domain');
            $this->info("Domain value loaded from option");
        } elseif(env('DOMAIN')) {
            $this->token = env('DOMAIN');
            $this->info("Domain value loaded from env.");
        } else {
            $this->token = $this->secret('Enter Widen domain');
        }
        return $this->domain;
    }
}
