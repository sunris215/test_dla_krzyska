const MiniCssExtractPlugin = require('mini-css-extract-plugin');
// const BrowserSyncPlugin = require('browser-sync-webpack-plugin');
const StyleLintPlugin = require('stylelint-webpack-plugin');
// const ESLintPlugin = require('eslint-webpack-plugin');
const path = require('path');
const globImporter = require('node-sass-glob-importer');
const jsPath= './assets/js';
const cssPath = './assets/scss';
const outputPath = 'dist';
const localDomain = 'http://mysite.local';
const entryPoints = {
  'scripts': jsPath + '/scripts.js',
  'style': cssPath + '/styles.scss',
};

module.exports = {
  entry: entryPoints,
  output: {
    path: path.resolve(__dirname, outputPath),
    filename: '[name].js',
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: '[name].css',
    }),
    new StyleLintPlugin({
      files: '/assets/scss/**/*.s?(a|c)ss',
      fix: false,
      quiet: true,
      failOnError: false,
      syntax: 'scss',
    }),

    // new ESLintPlugin({
    //   fix: true,
    // }),

    // Uncomment this if you want to use CSS Live reload
    /*
    new BrowserSyncPlugin({
      proxy: localDomain,
      files: [ outputPath + '/*.css' ],
      injectCss: true,
    }, { reload: false, }),
    */
  ],
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /(node_modules|bower_components)/,
        use: [
          {
            loader: 'babel-loader',
          },
        ],
      },
      {
        test: /\.s?[c]ss$/i,
        exclude: /(node_modules|bower_components)/,
        use: [
          MiniCssExtractPlugin.loader,
          'css-loader',
          {
            loader: 'sass-loader',
            options: {
              sourceMap: true,
              sassOptions: {
                sourceMap: true,
                importer: globImporter(),
              },
            }
          }
        ]
      },
      {
        test: /\.sass$/i,
        use: [
          MiniCssExtractPlugin.loader,
          'css-loader',
          {
            loader: 'sass-loader',
            options: {
              sassOptions: { indentedSyntax: true }
            }
          }
        ]
      },
      {
        test: /\.(jpg|jpeg|png|gif|woff|woff2|eot|ttf|svg)$/i,
        exclude: /(node_modules|bower_components)/,
        use: 'url-loader?limit=1024'
      }
    ]
  },
};