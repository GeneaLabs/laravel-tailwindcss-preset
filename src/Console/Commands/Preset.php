<?php namespace GeneaLabs\LaravelTailwindcssPreset\Console\Commands;

class Preset
{
    protected function updatePackages()
    {
        if (! file_exists(base_path('package.json'))) {
            return;
        }

        $packages = json_decode(file_get_contents(base_path('package.json')), true);

        $packages['devDependencies'] = $this
            ->updatePackageArray($packages['devDependencies']);

        file_put_contents(
            base_path('package.json'),
            json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL
        );
    }

    protected function removeNodeModules()
    {
        tap(app("files"), function ($files) {
            $files->deleteDirectory(base_path("node_modules"));
            $files->delete(base_path("yarn.lock"));
        });
    }

    protected function ensureComponentDirectoryExists()
    {
        $directory = resource_path("assets/scss/components");

        if (! app("files")->isDirectory($directory)) {
            app("files")->makeDirectory($directory, 0755, true);
        }
    }

    protected function updatePackageArray(array $devDependencies) : array
    {
        return collect($devDependencies)
            ->push(["tailwindcss" => "^0.4"])
            ->except([
                "bootstrap",
                "bootstrap-sass",
                "jquery",
            ])
            ->sort()
            ->toArray();
    }

    public function install()
    {
        dd($this);
        static::updatePackages();
        static::updateStyles();
        static::updateBootstrapping();
        static::updateWelcomePage();
        static::removeNodeModules();
    }

    protected function installAuthResources()
    {

    }

    protected function removeDefaultResources()
    {

    }

    protected function installNpmModules()
    {

    }

    protected function compileAssets()
    {

    }
}
