const path = require("path");
const glob = require('glob');
let all_folders = glob.sync("./blocks/**/index.jsx");

module.exports = {
    mode: "none",
    entry: all_folders,
    output: {
        path: path.resolve(__dirname, '..', "js"),
        filename: "block.js",
    },
    module: {
        rules: [
            {
                test: /\.(js|jsx)$/,
                exclude: /node_modules/,
                use: {
                    loader: "babel-loader"
                }
            }
        ]
    }
};
