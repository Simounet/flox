const webpack = require("webpack");
const BundleAnalyzerPlugin =
  require("webpack-bundle-analyzer").BundleAnalyzerPlugin;
const path = require("path");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const TerserPlugin = require("terser-webpack-plugin");
const { VueLoaderPlugin } = require("vue-loader");

const withReport = !!process.env.npm_config_withreport;
const isProduction = process.env.NODE_ENV === "production";

module.exports = {
  mode: "development",
  entry: {
    app: "./app/app.js",
    vendor: ["vue", "axios", "vuex", "debounce", "vue-router"],
  },
  watchOptions: {
    poll: true,
  },
  output: {
    path: path.resolve("../public/assets"),
    filename: "[name].js",
    assetModuleFilename: "img/[name][ext][query]",
  },
  resolve: {
    alias: {
      vue: "@vue/compat",
    },
  },
  module: {
    rules: [
      {
        test: /\.vue$/,
        loader: "vue-loader",
        options: {
          compilerOptions: {
            compatConfig: {
              MODE: 2,
            },
          },
        },
      },
      {
        test: /\.js$/,
        use: "babel-loader",
        exclude: /node_modules/,
      },
      {
        test: /\.(png|jpg|svg|woff|woff2|eot|ttf)$/,
        type: "asset/resource",
      },
      {
        test: /\.(scss|css)$/,
        use: [
          isProduction ? MiniCssExtractPlugin.loader : "style-loader",
          "css-loader",
          "postcss-loader",
          "sass-loader",
        ],
      },
    ],
  },
  plugins: [new VueLoaderPlugin(), new MiniCssExtractPlugin()].concat(
    withReport ? [new BundleAnalyzerPlugin({ analyzerMode: "static" })] : []
  ),
  optimization: {
    minimize: true,
    minimizer: [
      new TerserPlugin({
        extractComments: false,
        terserOptions: {
          format: {
            comments: false,
          },
        },
      }),
    ],
  },
};

if (isProduction) {
  module.exports.mode = "production";
}
