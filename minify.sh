#!/bin/bash

COMPRESSOR_JAR="/tmp/yuicompressor.jar"

function minify_js_google() {
    local minified_file="assets/js/app.min.js"
    local compiler_url="https://dl.google.com/closure-compiler/compiler-latest.zip"
    local compiler_zip="/tmp/closure-compiler.zip"
    local compiler_jar="/tmp/closure-compiler.jar"
    wget -qO $compiler_zip $compiler_url
    unzip -p $compiler_zip \*.jar > $compiler_jar
    java -jar $compiler_jar \
        --js_output_file=$minified_file \
        assets/js/src/app.js \
        assets/js/src/event.js \
        assets/js/src/feed.js \
        assets/js/src/item.js \
        assets/js/src/nav.js
    echo "Miniflux.App.Run();" >> $minified_file
    rm $compiler_jar $compiler_zip
}

function download_compressor() {
    local endpoint="https://api.github.com/repos/yui/yuicompressor/releases/latest"
    local url_regex="https://github.com/yui/yuicompressor/releases/download/v[0-9\.]+/yuicompressor-[0-9\.]+\.jar"
    local download_url=$(wget -qO- $endpoint | grep -oP $url_regex)
    wget -qO $COMPRESSOR_JAR $download_url
}

function minify_js() {
    local minified_file="assets/js/app.min.js"
    echo "Miniflux.App.Run();" \
    | cat assets/js/src/app.js \
        assets/js/src/event.js \
        assets/js/src/feed.js \
        assets/js/src/item.js \
        assets/js/src/nav.js \
        - \
    | java -jar $COMPRESSOR_JAR \
        --type js \
        --line-break 700 \
        -o $minified_file
}

function minify_css() {
    local minified_file="assets/css/app.min.css"
    java -jar $COMPRESSOR_JAR \
        --type css \
        --line-break 700 \
        -o $minified_file \
        assets/css/app.css
}

if [ ! -e $COMPRESSOR_JAR ]; then
    download_compressor
fi
if [ "$1" = "js" ]; then
    #minify_js_google
    minify_js
    echo "Javascript minified."
    exit
fi
if [ "$1" = "css" ]; then
    minify_css
    echo "CSS minified."
    exit
fi

#rm $COMPRESSOR_JAR
echo "Use flag \"js\" to minify javascript and \"css\" to minify css."
