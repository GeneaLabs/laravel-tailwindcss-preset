<?php namespace GeneaLabs\LaravelTailwindcssPreset;

use GeneaLabs\LaravelTailwindcssPreset\Console\Commands\Preset;
use Illuminate\Foundation\Console\PresetCommand;
use Illuminate\Support\ServiceProvider;

class Service extends ServiceProvider
{
    public function boot()
    {
        (new PresetCommand)->macro('tailwindcss', function ($command) {
            (new Preset)->install();
            $command->info('Tailwind CSS scaffolding installed successfully.');
            $command->info('Please run "npm install && npm run dev" to compile your fresh scaffolding.');
        });
    }
}
