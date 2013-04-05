#!/bin/sh
if [ ! $1 ];then
    echo "Requires one argument:"
    echo "    [path] Path to an XML file to correct"
    exit 42
fi
echo "Executing sed on file $1"
exec sed -ir -e 's/<index id="the.index" \/>//' "$1"
