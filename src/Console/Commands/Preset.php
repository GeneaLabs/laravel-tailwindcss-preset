<?php namespace GeneaLabs\LaravelTailwindcssPreset\Console\Commands;

use Symfony\Component\Finder\SplFileInfo;

class Preset
{
    protected function addAuthResources()
    {
        $archiveFolder = "replaced-by-tailwindcss-preset-" . now();
        $sourcePath = realpath(__DIR__ . "/../../../");

        collect(app("files")->allFiles(__DIR__ . "/../../../resources"))
            ->filter(function (SplFileInfo $file) {
                return str_contains($file->getPath(), "resources/views/auth");
            })
            ->each(function (SplFileInfo $file) use ($archiveFolder, $sourcePath) {
                $path = str_replace($sourcePath, "", $file->getPath());

                $this->archiveFile($archiveFolder, $path);
                $this->insertNewFile($sourcePath, $path);
            });
    }

    protected function addDefaultResources()
    {
        $archiveFolder = "replaced-by-tailwindcss-preset-" . now();
        $sourcePath = realpath(__DIR__ . "/../../../");

        collect(app("files")->allFiles(__DIR__ . "/../../../resources"))
            ->reject(function (SplFileInfo $file) {
                return str_contains($file->getPath(), "resources/views/auth");
            })
            ->each(function (SplFileInfo $file) use ($archiveFolder, $sourcePath) {
                $path = str_replace($sourcePath, "", $file->getPath());

                $this->archiveFile($archiveFolder, $path);
                $this->insertNewFile($sourcePath, $path);
            });
    }

    protected function archiveFile(
        string $archiveFolder,
        string $path
    ) {
        app("files")->move(
            resource_path($path),
            resource_path("{$archiveFolder}/{$path}")
        );
    }

    protected function insertNewFile(
        string $sourcePath,
        string $path
    ) {
        app("files")->copy("{$sourcePath}/{$path}", resource_path($path));
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
        return collect($dependencies)
            ->push([
                "genealabs/laravel-casts" => "*",
                "genealabs/laravel-mixpanel" => "*",
                "genealabs/laravel-model-caching" => "*",
                "genealabs/laravel-null-carbon" => "*",
                "genealabs/laravel-optimized-postgres" => "*",
                "genealabs/laravel-authorization-addons" => "*",
                "genealabs/laravel-whoops-atom" => "*",
            ])
            ->sort()
            ->toArray();
    }

    protected function updateComposerDevDependencies(array $devDependencies) : array
    {
        return collect($devDependencies)
            ->push([
                "genealabs/laravel-whoops-atom" => "*",
            ])
            ->sort()
            ->toArray();
    }

    protected function updatePackageDevDependencies(array $devDependencies) : array
    {
        return collect($devDependencies)
            ->push([
                "tailwindcss" => "^0.4",
                "@fortawesome/fontawesome" => "*",
                "@fortawesome/fontawesome-free-brands" => "*",
                "@fortawesome/fontawesome-free-solid" => "*",
                "@fortawesome/fontawesome-free-regular" => "*",
                "@fortawesome/vue-fontawesome" => "*",
            ])
            ->except([
                "bootstrap",
                "bootstrap-sass",
                "jquery",
            ])
            ->sort()
            ->toArray();
    }

    protected function updateNodePackages()
    {
        if (! file_exists(base_path("package.json"))) {
            return;
        }

        $packages = json_decode(file_get_contents(base_path("package.json")), true);
        $packages["devDependencies"] = $this
            ->updatePackageDevDependencies($packages["devDependencies"]);

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

        $packages = json_decode(file_get_contents(base_path("package.json")), true);
        $packages["require"] = $this
            ->updateComposerDependencies($packages["require"]);
        $packages["require-dev"] = $this
            ->updateComposerDevDependencies($packages["require-dev"]);

        file_put_contents(
            base_path("composer.json"),
            json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL
        );
    }

    public function install()
    {
        $this->addAuthResources();
        $this->installWithoutAuth();
    }

    public function installWithoutAuth()
    {
        $this->updateNodePackages();
        $this->updateComposerPackages();
        $this->removeNodeModules();
        $this->removeNodeModules();
        $this->addDefaultResources();
        $this->installNpmModules();
        $this->installComposerPackages();
    }
}
