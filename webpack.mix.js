let mix = require('laravel-mix');

mix.js('src/assets/src/wallet-pay.js', 'src/assets/dist/js/')
    // 2022 - temp fix of laravel-mix incompatibility with Apple Silicon
    // see https://github.com/laravel-mix/laravel-mix/issues/3027
    .disableNotifications()
    .setPublicPath('src/assets/dist')
    .version();
