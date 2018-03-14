<?php namespace GeneaLabs\LaravelTailwindcssPreset;

use GeneaLabs\LaravelTailwindcssPreset\Console\Commands\TailwindVuePreset;
use Illuminate\Support\ServiceProvider;

class Service extends ServiceProvider
{
    public function boot()
    {
        $this->commands(TailwindVuePreset::class);
    }
}
