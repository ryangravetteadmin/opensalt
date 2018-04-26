const Encore = require('@symfony/webpack-encore');
const path = require('path');

const vendorDir = './vendor';
const npmDir = './node_modules/';
const assetsDir = './app/Resources/assets';
const buildDir = './web/build';

var sharedScripts = [
    npmDir+'/html5-boilerplate/dist/js/plugins.js',
    npmDir+'/jquery/dist/jquery.js',
    npmDir+'/jquery-migrate/dist/jquery-migrate'+(Encore.isProduction()?'.min':'')+'.js',
    npmDir+'/jquery-ui-bundle/jquery-ui.js',
    npmDir+'/bootstrap-sass/assets/javascripts/bootstrap.js',
    npmDir+'/jquery.fancytree/dist/jquery.fancytree-all.js',
    assetsDir+'/js/site.js'
];
if (Encore.isProduction()) {
    // Remove jquery-migrate from production code
    // Comment the next line out if you want the minified version
    //sharedScripts.splice(2, 1);
}

const apxScripts = [
    assetsDir+'/js/cftree/view-documentclass.js',
    assetsDir+'/js/cftree/view-trees.js',
    assetsDir+'/js/cftree/view-edit.js',
    assetsDir+'/js/cftree/view-modes.js',
    assetsDir+'/js/cftree/viewx.js',
    assetsDir+'/js/cftree/apxglobal.js',
    assetsDir+'/js/cftree/copy-framework.js'
];

const fs = require('fs');
if (!fs.existsSync('./build/js')) {
    if (!fs.existsSync('./build')) {
        fs.mkdirSync('./build');
    }
    fs.mkdirSync('./build/js');
}
fs.writeFileSync('./build/js/apx.js', apxScripts.map((f) => {
    return fs.readFileSync(f).toString();
}).join(';'));

const mainScripts = [
    npmDir+'/datatables.net/js/jquery.dataTables.js',
    npmDir+'/datatables.net-bs/js/dataTables.bootstrap.js',
    npmDir+'/datatables.net-fixedheader/js/dataTables.fixedHeader.js',
    npmDir+'/datatables.net-scroller/js/dataTables.scroller.js',
    npmDir+'/datatables.net-select/js/dataTables.select.js',
    //npmDir+'/ui-contextmenu/jquery.ui-contextmenu.js',
    npmDir+'/bootstrap-multiselect/dist/js/bootstrap-multiselect.js',
    npmDir+'/select2/dist/js/select2.full.js',
    vendorDir+'/tetranz/select2entity-bundle/Tetranz/Select2EntityBundle/Resources/public/js/select2entity.js',
    npmDir+'/twbs-pagination/jquery.twbsPagination.js',
    npmDir+'/bootstrap-notify/bootstrap-notify.min.js',
    assetsDir+'/js/application.js',
    assetsDir+'/js/lsdoc/index.js',
    npmDir+'/papaparse/papaparse.min.js',
    npmDir+'/dropzone/dist/dropzone.js',
    npmDir+'/ajaxq/ajaxq.js',
    './build/js/apx.js'
];

Encore
    .setOutputPath('web/build/')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()

    .addEntry('modernizr', npmDir+'/html5-boilerplate/dist/js/vendor/modernizr-3.5.0.min.js')
    .addEntry('site', mainScripts)
    .addEntry('comments', [
        npmDir+'/jquery-comments/js/jquery-comments.js',
        assetsDir+'/js/lsdoc/comments.js'
    ])

    .createSharedEntry('base', sharedScripts)

    .addStyleEntry('main', [
//        npmDir+'/fancytree/dist/skin-lion/ui.fancytree.css',
//        vendorDir+'/mervick/material-design-icons/scss/material-icons.scss',
        assetsDir+'/sass/application.scss',
        npmDir+'/dropzone/dist/dropzone.css'
//        vendorDir+'/fortawesome/font-awesome/css/font-awesome.css'
    ])
    .addStyleEntry('commentscss', [
        npmDir+'/jquery-comments/css/jquery-comments.css',
        assetsDir+'/sass/comments.scss'
    ])
    /*
    .enableSassLoader(function(sassOptions) {}, {
        resolveUrlLoader: true
    })
    */

    .autoProvideVariables({
        $: 'jquery',
        jQuery: 'jquery',
        'window.jQuery': 'jquery'
    })
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning()
    .configureBabel(function(babelConfig) {
        babelConfig.compact = true;
        babelConfig.presets.push([
            'env',
            {
                modules: false,
                targets: {
                    browsers: '> 1%',
                    uglify: true
                },
                useBuiltIns: true
            }
        ]);
    })
    .addLoader({
        test: /\.s[ac]ss$/,
        use: [
            {
              loader: './node_modules/extract-text-webpack-plugin/dist/loader.js',
              options: {
                omit: 1,
                remove: true
              }
            },
            {
              loader: 'style-loader',
              options: {
                sourceMap: !Encore.isProduction()
              }
            },
            {
              loader: 'css-loader',
              options: {
                minimize: Encore.isProduction(),
                sourceMap: !Encore.isProduction(),
                importLoaders: 0
              }
            },
            {
              loader: 'resolve-url-loader',
              options: {
                sourceMap: !Encore.isProduction()
                ,keepQuery: true
                ,root: __dirname+'/web'
                //,debug: true
              }
            },
            {
              loader: 'sass-loader',
              options: {
                sourceMap: true // Needed for resolve-url-loader
              }
            }
        ]
        ,exclude: /docker/
    })
    .addLoader({
        test: /jquery-(migrate|ui)(|.min).js$/,
        use: [
            {
              loader: 'imports-loader?define=>false'
            }
        ]
    })
;
/*
console.log(Encore.getWebpackConfig());
console.log(Encore.getWebpackConfig().plugins);
console.log(Encore.getWebpackConfig().module.rules);
console.log(JSON.stringify(Encore.getWebpackConfig().module.rules, false, 2));
*/

const config = Encore.getWebpackConfig();
config.context = __dirname;
config.resolve.alias = {
  'jquery': path.resolve(__dirname, npmDir+'/jquery/dist/jquery.js'),
  //'jquery-ui': path.resolve(__dirname, npmDir+'/jquery-ui/jquery-ui.js'),
  'jquery-ui': path.resolve(__dirname, npmDir+'/jquery-ui-bundle/jquery-ui.js'),
  //'datatables.net': path.resolve(__dirname, npmDir+'/datatables.net/js/jquery.dataTables.js'),
  //'jquery-ui/ui/widgets/menu': path.resolve(__dirname, npmDir+'/jquery-ui/ui/widgets/menu.js'),
  //'simplemde': path.resolve(__dirname, npmDir+'/simplemde/dist/simplemde.min.js'),
  //'papaparse': path.resolve(__dirname, npmDir+'/papaparse/papaparse.min.js'),
  //'markdown-it-underline': path.resolve(__dirname, npmDir+'/markdown-it-underline/index.js'),
  'render-md': path.resolve(__dirname, assetsDir+'/js/cftree/render-md.js'),
  'util-salt': path.resolve(__dirname, assetsDir+'/js/util-salt.js'),
  'ajaxq': path.resolve(__dirname, npmDir+'/ajaxq/ajaxq.js')
};
config.resolve.modules = [
  'node_modules',
  path.resolve(__dirname, './web/assets/img'),
  path.resolve(__dirname, '.')
  //path.resolve(__dirname, npmDir)
];

module.exports = config;