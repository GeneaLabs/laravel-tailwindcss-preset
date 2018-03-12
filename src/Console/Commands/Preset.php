<?php namespace GeneaLabs\LaravelTailwindcssPreset\Console\Commands;

use Symfony\Component\Finder\SplFileInfo;

class Preset
{
    protected $archiveFolder = "";

    protected function addAuthResources()
    {
        $sourcePath = realpath(__DIR__ . "/../../../resources");

        collect(app("files")->allFiles($sourcePath))
            ->filter(function (SplFileInfo $file) {
                return str_contains($file->getPath(), "resources/views/auth");
            })
            ->each(function (SplFileInfo $file) use ($sourcePath) {
                $path = trim(str_replace($sourcePath, "", $file->getPathName()), "/");

                $this->archiveFile($path);
                $this->insertNewFile($sourcePath, $path);
            });
    }

    protected function addDefaultResources()
    {
        $sourcePath = realpath(__DIR__ . "/../../../resources");

        collect(app("files")->allFiles($sourcePath))
            ->reject(function (SplFileInfo $file) {
                return str_contains($file->getPath(), "resources/views/auth");
            })
            ->each(function (SplFileInfo $file) use ($sourcePath) {
                $path = trim(str_replace($sourcePath, "", $file->getPathName()), "/");

                $this->archiveFile($path);
                $this->insertNewFile($sourcePath, $path);
            });
    }

    protected function archiveFile(
        string $path
    ) {
        tap(app("files"), function ($files) use ($path) {
            if ($files->exists(resource_path($path))) {
                if (! $files->exists(dirname(resource_path("{$this->archiveFolder}/{$path}")))) {
                    $files->makeDirectory(dirname(resource_path("{$this->archiveFolder}/{$path}")), 0755, true);
                }

                $files->move(
                    resource_path($path),
                    resource_path("{$this->archiveFolder}/{$path}")
                );
            }
        });
    }

    protected function insertNewFile(
        string $sourcePath,
        string $path
    ) {
        tap(app("files"), function ($files) use ($path, $sourcePath) {
            if (! $files->exists(dirname(resource_path($path)))) {
                $files->makeDirectory(dirname(resource_path($path)), 0755, true);
            }

            $files->copy("{$sourcePath}/{$path}", resource_path($path));
        });
    }

    protected function compileAssets()
    {
        shell_exec("cd " . base_path() . " && yarn run prod");
    }

    protected function ensureComponentDirectoryExists()
    {
        $directories = [
            resource_path("assets/js/components"),
            resource_path("assets/scss/components"),
        ];

        tap(app("files", function ($files) use ($directories) {
            foreach ($directories as $directory) {
                if (! $files->isDirectory($directory)) {
                    $files->makeDirectory($directory, 0755, true);
                }
            }
        }));
    }

    protected function installComposerPackages()
    {
        shell_exec("cd " . base_path() . " && composer update");
    }

    protected function installNpmModules()
    {
        shell_exec("cd " . base_path() . " && yarn");
    }

    protected function removeComposerPackages()
    {
        tap(app("files"), function ($files) {
            $files->deleteDirectory(base_path("vendor"));
            $files->delete(base_path("composer.lock"));
        });
    }

    protected function removeNodeModules()
    {
        tap(app("files"), function ($files) {
            $files->deleteDirectory(base_path("node_modules"));
            $files->delete(base_path("yarn.lock"));
        });
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

    public function install()
    {
        $this->archiveFolder = "replaced-by-tailwindcss-preset-" . now();

        $this->addAuthResources();
        $this->installWithoutAuth();
    }

    public function installWithoutAuth()
    {
        $this->archiveFolder = "replaced-by-tailwindcss-preset-" . now();

        $this->updateNodePackages();
        $this->updateComposerPackages();
        $this->removeNodeModules();
        $this->removeNodeModules();
        $this->addDefaultResources();
        $this->installNpmModules();
        $this->installComposerPackages();
    }
}
