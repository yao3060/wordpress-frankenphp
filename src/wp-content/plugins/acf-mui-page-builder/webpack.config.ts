import path from 'path';
import webpack from 'webpack';
import MiniCssExtractPlugin from "mini-css-extract-plugin";

const config: webpack.Configuration = {
    entry: './ts/main.tsx',
    output: {
        path: path.resolve(__dirname, 'dist'),
    },
    module: {
        rules: [
            {
                parser: {
                    amd: false,
                },
            },
            {
                test: /\.(tsx?|jsx?)$/,
                use: 'babel-loader',
            },
            {
                test: /\.scss$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    // MiniCssExtractPlugin.loader,
                    'css-loader',
                    'sass-loader',
                ]
            }
        ],
    },
    resolve: {
        alias: {
            "react": "preact/compat",
            "react-dom/test-utils": "preact/test-utils",
            "react-dom": "preact/compat",     // Must be below test-utils
            "react/jsx-runtime": "preact/jsx-runtime"
        },
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: 'css/[name].css',
        }),
    ]
};

export default config;
