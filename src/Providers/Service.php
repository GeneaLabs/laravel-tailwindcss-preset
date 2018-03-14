<?php namespace GeneaLabs\LaravelTailwindcssPreset;

use GeneaLabs\LaravelTailwindcssPreset\Console\Commands\TailwindVuePreset;
use Illuminate\Support\ServiceProvider;

class Service extends ServiceProvider
{
    public function boot()
    {
        tap(new PresetCommand, function ($presetCommand) {
            $presetCommand->macro('tailwind-vue', function ($command) {
                (new TailwindVuePreset)->install();
                $command->info('Tailwind CSS scaffolding installed successfully.');
                $command->info('Please run "npm install && npm run dev" to compile your fresh scaffolding.');
            });
            $presetCommand->macro('tailwind-vue-without-admin', function ($command) {
                (new TailwindVuePreset)->installWithoutAuth();
                $command->info('Tailwind CSS scaffolding installed successfully.');
                $command->info('Please run "npm install && npm run dev" to compile your fresh scaffolding.');
            });
        });
    }
}
