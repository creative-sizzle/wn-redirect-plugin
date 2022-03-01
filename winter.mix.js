const mix = require('laravel-mix');

mix.setPublicPath(__dirname);

// Your mix configuration below
mix.postCss('assets/css/redirect.css', 'assets/dist/css')
    .postCss('assets/css/statistics.css', 'assets/dist/css')
    .postCss('assets/css/test-lab.css', 'assets/dist/css')

mix.js('assets/js/test-lab.js', 'assets/dist/js')
    .js('assets/js/statistics.js', 'assets/dist/js/statistics.js')

mix.babelConfig({
    presets: [
        [
            '@babel/preset-env',
            {
                corejs: 3,
                useBuiltIns: 'usage',
                targets: '> 0.5%, last 4 versions, not dead, Firefox ESR, not ie > 0',
            },
        ],
    ],
})
