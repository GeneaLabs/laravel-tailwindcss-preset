<?php namespace GeneaLabs\LaravelTailwindcssPreset;

use GeneaLabs\LaravelTailwindcssPreset\Console\Commands\TailwindVuePreset;
use Illuminate\Foundation\Console\PresetCommand;
use Illuminate\Support\ServiceProvider;

class Service extends ServiceProvider
{
    public function boot()
    {
        tap(new PresetCommand, function ($presetCommand) {
            $presetCommand->macro('tailwind-vue', function () {
                (new TailwindVuePreset)->install();
            });
            $presetCommand->macro('tailwind-vue-without-admin', function () {
                (new TailwindVuePreset)->installWithoutAuth();
            });
        });
    }
}
