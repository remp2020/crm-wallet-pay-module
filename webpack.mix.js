let mix = require('laravel-mix');

mix.js('src/assets/src/wallet-pay.js', 'src/assets/dist/js/')
    .setPublicPath('src/assets/dist')
    .version();
