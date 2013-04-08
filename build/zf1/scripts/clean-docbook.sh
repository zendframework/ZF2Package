#!/bin/bash
if [ $# -ne 1 ];then
    echo "Incorrect arguments supplied; cannot clean DocBook!" ;
    echo "Usage: $0 [doc dir]" ; 
    exit 1 ;
fi
DOC_DIR=$1

rm -rf \
    "$DOC_DIR/autom4te.cache" \
    "$DOC_DIR/html/*.html" \
    "$DOC_DIR/html/*.css" \
    "$DOC_DIR/html/HTML.manifest" \
    "$DOC_DIR/config.*" \
    "$DOC_DIR/configure" \
    "$DOC_DIR/Makefile" \
    "$DOC_DIR/output" \
    "$DOC_DIR/*.txt" \
    "$DOC_DIR/manual.db5.xml"

if [ -d "$DOC_DIR/build" ];then
    rm -rf "$DOC_DIR/build/docbook-xsl"
fi
