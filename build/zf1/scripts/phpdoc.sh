#!/bin/bash
if [ $# -ne 5 ];then
    echo "Not enough arguments supplied; cannot build DocBook!" ;
    echo "Usage: $0 [make] [doc-utils dir] [source dir] [build dir]" ; 
    exit 1 ;
fi
PHPDOC=$1
SOURCE_DIR=$2
BUILD_DIR=$3
DOC_TITLE=$4
DOC_STYLE=$5

if [ -f $BUILD_DIR/index.html -o `find $SOURCE_DIR -older $BUILD_DIR/index.html 2>/dev/null | wc -l` -gt 0 ] ; then
    echo "PHP API documentation is already staged and up to date." ;
    exit 0;
fi

$PHPDOC project:run \
    --verbose \
    --target $BUILD_DIR \
    --directory $SOURCE_DIR \
    --title "$DOC_TITLE" \
    --template $DOC_STYLE
