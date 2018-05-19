var path = require('path');
var webpack = require("webpack");
var node_modules = path.resolve(__dirname, 'node_modules');
var autoprefixer = require('autoprefixer');

module.exports = {

    entry: [
        './src/react-app/index.js',
    ],

    output: {
        path: path.join(__dirname, 'public/js'),
        filename: 'bundle.js',
        publicPath: 'js/'
    },

    resolve: {
        extensions: ['.js', '.jsx']
    },

    module: {
        rules: [
            {
                test: /\.jsx?$/,
                use: [
                    'babel-loader?presets[]=es2015&presets[]=react'
                ],
                exclude: /node_modules/
            },
            {
                test: /\.css$/,
                use: ['style-loader', 'css-loader']
            },
            {
                test: /\.scss$/,
                use: ["style-loader", "css-loader", "sass-loader"]
            },
            {
                test: /\.(eot|otf|svg|ttf|woff|woff2)$/,
                use: ['file-loader']
            }
        ]
    },
  plugins: [
  new webpack.DefinePlugin({
  "process.env": {
     NODE_ENV: JSON.stringify("production")
   }
})
  ],
    performance: { hints: false }
};
