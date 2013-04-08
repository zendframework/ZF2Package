#!/bin/bash
if [ $# -ne 4 ];then
    echo "Not enough arguments supplied; cannot build DocBook!" ;
    echo "Usage: $0 [make] [doc-utils dir] [source dir] [build dir]" ; 
    exit 1 ;
fi
MAKE=$1
DOC_UTILS=$2
SOURCE_DIR=$3
BUILD_DIR=$4

echo "Converting manual to DocBook 5..."
( cd $SOURCE_DIR && autoconf && sh ./configure && make manual.xml 2>&1 | tee err.txt)
( $DOC_UTILS/fix-docbook.sh $SOURCE_DIR/manual.xml )
( xsltproc --xinclude $DOC_UTILS/db4-upgrade.xsl $SOURCE_DIR/manual.xml > $SOURCE_DIR/manual.db5.xml | tee -a $SOURCE_DIR/err.txt)
echo "Building and staging DocBook documentation..."
( $DOC_UTILS/pear/phd -g 'phpdotnet\phd\Highlighter_GeSHi' --xinclude -f zfpackage -d $SOURCE_DIR/manual.db5.xml -o $SOURCE_DIR/output )
rsync --archive --delete $SOURCE_DIR/output/zf-package-chunked-xhtml/ $BUILD_DIR
