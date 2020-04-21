let mix = require('laravel-mix')

mix
  .setPublicPath('dist')
  .js('resources/js/field.js', 'js')
  .sass('resources/sass/field.scss', 'css')

    .webpackConfig({
        resolve: {
            alias: {
                '@/storage': path.resolve(__dirname, '../../nova/resources/js/storage'),
            },

            modules: [
                path.resolve(__dirname, '../../nova/node_modules/'),
            ],
        }
    })

.sourceMaps();
