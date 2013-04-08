#!/bin/bash
if [ $# -ne 3 ];then
    echo "Not enough arguments supplied; cannot release files!" ;
    echo "Usage: $0 [product] [stage dir] [release dir]" ; 
    exit 1 ;
fi

PRODUCT=$1
STAGE_DIR=$2
RELEASE_DIR=$3

if [ -d "$RELEASE_DIR" ];then
    echo "Release directory already exists ($RELEASE_DIR)" ;
    exit 0 ;
fi

mkdir -p $RELEASE_DIR
cp $STAGE_DIR/$PRODUCT*.zip $RELEASE_DIR
cp $STAGE_DIR/$PRODUCT*.tar.gz $RELEASE_DIR
