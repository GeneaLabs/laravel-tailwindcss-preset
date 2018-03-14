<?php namespace GeneaLabs\LaravelTailwindcssPreset\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Console\PresetCommand;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Console\Helper\ProgressBar;

class TailwindVuePreset extends Command
{
    protected $archiveFolder = "";
    protected $sourcePath = "";
    protected $signature = "preset:tailwind-vue {--no-auth}";
    protected $description = "";

    public function __construct()
    {
        parent::__construct();

        $this->archiveFolder = "replaced-by-tailwindcss-preset-" . now();
        $this->sourcePath = realpath(__DIR__ . "/../../../resources");
        ProgressBar::setFormatDefinition('custom', '%message% 0[%bar%]%max%');
        ProgressBar::setFormatDefinition('custom-no-max', '%message% [%bar%]');
    }

    public function handle()
    {
        if (! $this->option("no-auth")) {
            $this->addAuthResources();
        }

        $this->updateNodePackages();
        $this->updateComposerPackages();
        $this->removeNodeModules();
        $this->removeNodeModules();
        $this->addDefaultResources();
        $this->installNpmModules();
        $this->installComposerPackages();
        $this->compileAssets();
    }

    protected function addAuthResources()
    {
        $this->output->write("Adding auth resources ...");

        $files = collect(app("files")->allFiles($this->sourcePath))
            ->filter(function (SplFileInfo $file) {
                return str_contains($file->getPath(), "resources/views/auth");
            });

        $files->each(function (SplFileInfo $file) {
            $path = trim(str_replace($this->sourcePath, "", $file->getPathName()), "/");

            $this->archiveFile($path);
            $this->insertNewFile($path);
        });

        $this->info(" ✅");
    }

    protected function addDefaultResources()
    {
        $this->output->write("Adding default resources ...");
        $files = collect(app("files")->allFiles($this->sourcePath))
            ->reject(function (SplFileInfo $file) {
                return str_contains($file->getPath(), "resources/views/auth");
            });

        $files->each(function (SplFileInfo $file) {
            $path = trim(str_replace($this->sourcePath, "", $file->getPathName()), "/");

            $this->archiveFile($path);
            $this->insertNewFile($path);
        });

        $this->info(" ✅");
    }

    protected function archiveFile(string $path)
    {
        if (app("files")->exists(resource_path($path))) {
            if (! app("files")->exists(dirname(resource_path("{$this->archiveFolder}/{$path}")))) {
                app("files")->makeDirectory(dirname(resource_path("{$this->archiveFolder}/{$path}")), 0755, true);
            }

            app("files")->move(
                resource_path($path),
                resource_path("{$this->archiveFolder}/{$path}")
            );
        }
    }

    protected function insertNewFile(string $path)
    {
        if (! app("files")->exists(dirname(resource_path($path)))) {
            app("files")->makeDirectory(dirname(resource_path($path)), 0755, true);
        }

        app("files")->copy("{$this->sourcePath}/{$path}", resource_path($path));
    }

    protected function compileAssets()
    {
        $this->output->write("Mixing project assets ...");
        $this->executeCommand("cd " . base_path() . " && yarn run prod");
    }

    protected function ensureComponentDirectoryExists()
    {
        $directories = [
            resource_path("assets/js/components"),
            resource_path("assets/scss/components"),
        ];

        foreach ($directories as $directory) {
            if (! app("files")->isDirectory($directory)) {
                app("files")->makeDirectory($directory, 0755, true);
            }
        }
    }

    protected function executeCommand(string $command)
    {
        $exitCode = 0;
        $output = "";

        exec("{$command} 2>&1", $output, $exitCode);

        if ($exitCode === 0) {
            $this->info(" ✅");

            return;
        }

        $this->info(" 🔴");
        $this->error(implode("\n", $output));
    }

    protected function installComposerPackages()
    {
        $this->output->write("Installing composer packages ...");
        $this->executeCommand("cd " . base_path() . " && composer update");
    }

    protected function installNpmModules()
    {
        $this->output->write("Installing node modules ...");
        $this->executeCommand("cd " . base_path() . " && yarn");
    }

    protected function removeComposerPackages()
    {
        app("files")->deleteDirectory(base_path("vendor"));
        app("files")->delete(base_path("composer.lock"));
    }

    protected function removeNodeModules()
    {
        app("files")->deleteDirectory(base_path("node_modules"));
        app("files")->delete(base_path("yarn.lock"));
    }

    protected function updateComposerDependencies(array $dependencies) : array
    {
        $dependencies["genealabs/laravel-casts"] = "*";
        $dependencies["genealabs/laravel-mixpanel"] = "*";
        $dependencies["genealabs/laravel-model-caching"] = "*";
        $dependencies["genealabs/laravel-null-carbon"] = "*";
        $dependencies["genealabs/laravel-optimized-postgres"] = "*";
        $dependencies["genealabs/laravel-authorization-addons"] = "*";

        return collect($dependencies)
            ->sortKeys()
            ->toArray();
    }

    protected function updateComposerDevDependencies(array $devDependencies) : array
    {
        $devDependencies["genealabs/laravel-whoops-atom"] = "*";

        return collect($devDependencies)
            ->sortKeys()
            ->toArray();
    }

    protected function updateNodeDevDependencies(array $devDependencies) : array
    {
        $devDependencies["tailwindcss"] = "^0.4";
        $devDependencies["@fortawesome/fontawesome"] = "*";
        $devDependencies["@fortawesome/fontawesome-free-brands"] = "*";
        $devDependencies["@fortawesome/fontawesome-free-solid"] = "*";
        $devDependencies["@fortawesome/fontawesome-free-regular"] = "*";
        $devDependencies["@fortawesome/vue-fontawesome"] = "*";

        return collect($devDependencies)
            ->except([
                "bootstrap",
                "bootstrap-sass",
                "jquery",
            ])
            ->sortKeys()
            ->toArray();
    }

    protected function updateNodePackages()
    {
        if (! file_exists(base_path("package.json"))) {
            return;
        }

        $packages = json_decode(file_get_contents(base_path("package.json")), true);
        $packages["devDependencies"] = $this
            ->updateNodeDevDependencies($packages["devDependencies"]);

        file_put_contents(
            base_path("package.json"),
            json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL
        );
    }

    protected function updateComposerPackages()
    {
        if (! file_exists(base_path("composer.json"))) {
            return;
        }

        $packages = json_decode(file_get_contents(base_path("composer.json")), true);

        if (array_key_exists("require", $packages)) {
            $packages["require"] = $this
                ->updateComposerDependencies($packages["require"]);
        }

        if (array_key_exists("require-dev", $packages)) {
            $packages["require-dev"] = $this
                ->updateComposerDevDependencies($packages["require-dev"]);
        }

        file_put_contents(
            base_path("composer.json"),
            json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL
        );
    }
}
