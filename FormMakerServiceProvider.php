<?php

namespace p3ym4n\FormMaker;

use Illuminate\Support\ServiceProvider;

class FormMakerServiceProvider extends ServiceProvider {
    
    protected $defer = true;
    
    const NAME = 'formMaker';
    
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot() {
        
        $this->loadTranslationsFrom(__DIR__ . '/Translations', self::NAME);
        
        $this->publishes([
            __DIR__ . '/Translations' => resource_path('lang/vendor/' . self::NAME),
        ]);
    }
}
