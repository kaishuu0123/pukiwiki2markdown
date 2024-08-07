var webpack = require('webpack');
var WebpackDevServer = require('webpack-dev-server');
var config = require('./webpack.config');

new WebpackDevServer(
  webpack(config),
  {
    publicPath: config.output.publicPath,
    hot: true,
    // quiet: false,
    // noInfo: false,
    // historyApiFallback: true
  }
).listen(
  8081,
  '0.0.0.0',
  function (err, result) {
    if (err) {
      console.log(err);
    }
    console.log('Listening at 0.0.0.0:8081');
  }
);
