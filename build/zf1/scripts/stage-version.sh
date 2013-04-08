#!/bin/bash
if [ $# -eq 0 ];then
    echo "No arguments supplied; cannot add date to release notes!" ;
    exit 1 ;
fi
README_PATH=$1
sed -e 's/ (\[INSERT REV NUM HERE\])//' \
    < $README_PATH/README.md \
    > $README_PATH/README.md.new
RELEASE_DATE=`date +%Y-%m-%d` ; sed -e "s/<Month> <Day>, <Year>/$RELEASE_DATE/" \
    < $README_PATH/README.md.new \
    > $README_PATH/README.md
rm $README_PATH/README.md.new
