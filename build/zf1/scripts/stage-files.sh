#!/bin/bash
if [ $# -ne 7 ];then
    echo "Not enough arguments supplied; cannot stage files!" ;
    echo "Usage: $0 [stage dir] [source export dir] [dest export dir] [include-files] [exclude files] [manual.xml] [test pattern]" ; 
    exit 1 ;
fi

STAGE_DIR=$1
SOURCE_EXPORT_DIR=$2
DEST_EXPORT_DIR=$3
INCLUDE_FILES=$4
EXCLUDE_FILES=$5
MANUAL_XML=$6
TEST_PATTERN=$7

if [ -d "$STAGE_DIR" ];then
    echo "Staging directory already exists ($STAGE_DIR)" ;
    exit 0 ;
fi

echo "... Copying subset of ZF files..."
mkdir -p $STAGE_DIR
(cd "$SOURCE_EXPORT_DIR" && rsync --archive --delete --recursive \
    --files-from="$INCLUDE_FILES" ./ "$DEST_EXPORT_DIR/")
echo "... Replacing documentation/manual/en/manual.xml.in..."
cp "$MANUAL_XML" "$DEST_EXPORT_DIR/documentation/manual/en/manual.xml.in"
echo "... Altering tests/Zend/AllTests.php..."
mv "$DEST_EXPORT_DIR/tests/Zend/AllTests.php" "$DEST_EXPORT_DIR/tests/Zend/AllTests-orig.php"
awk '{ \
    if ($$0 ~ /require_once .Zend|addTest/ && $$0 !~ /'$TEST_PATTERN'/) \
    { } \
    else \
    { print; } \
    }' \
    "$DEST_EXPORT_DIR/tests/Zend/AllTests-orig.php" > \
    "$DEST_EXPORT_DIR/tests/Zend/AllTests.php"
echo "... Staging files..."
(cd $DEST_EXPORT_DIR && rsync --archive --delete \
    --exclude-from="$EXCLUDE_FILES" ./ "$STAGE_DIR/")
