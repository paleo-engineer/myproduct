const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
    .js('resources/js/modal.js', 'public/js')
    .js('resources/js/strCount.js', 'public/js')
    .js('resources/js/ajaxlike.js', 'public/js')
    .js('resources/js/ajaxbuilding.js', 'public/js')
    .js('resources/js/ajaxsixpacking.js', 'public/js')
    .sass('resources/sass/app.scss', 'public/css')
    .sass('resources/sass/modal.scss', 'public/css');
mix.sourceMaps().js('node_modules/popper.js/dist/popper.js', 'public/js').sourceMaps();