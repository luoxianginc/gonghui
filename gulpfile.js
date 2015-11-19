var elixir = require('laravel-elixir');
elixir.config.sourcemaps = false;

/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Sass
 | file for our application, as well as publishing vendor resources.
 |
 */

elixir(function(mix) {
    mix.sass('change_password.scss', 'resources/css');
	mix.styles(['animate.min.css', 'change_password.css'], 'public/css/change_password.css', 'resources/css');
	
	mix.browserify('change_password.js', 'resources/js');
	mix.scripts(['common.js', 'change_password.js'], 'public/js/change_password.js', 'resources/js');
});
