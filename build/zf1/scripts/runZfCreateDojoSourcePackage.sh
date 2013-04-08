#!/bin/sh
BUILDDIR=$(pwd)
if [ -z $1 ];then
    echo "$0 requires a VERSION argument"
    exit 42;
fi
VERSION=$1
PKGDIR="$BUILDDIR/$VERSION"
TMPDIR="$BUILDDIR/tmp"
ARTIFACTSDIR="$BUILDDIR/artifacts"
if [ ! -d "$PKGDIR" ]; then
    mkdir -p $PKGDIR
fi
if [ ! -d "$TMPDIR" ]; then
    mkdir -p $TMPDIR
fi
if [ ! -d "$ARTIFACTSDIR" ]; then
    mkdir -p $ARTIFACTSDIR
fi

# BUILD DOJO
echo "Preparing to build Dojo release"
# Sync files to tmp dir
echo "    Syncing dojo files to staging area"
if [ ! -d "$BUILDDIR/externals/dojo" ];then
    echo "Could not find dojo at $BUILDDIR/externals/dojo"
    exit 42;
fi
rsync -aC "$BUILDDIR/externals/dojo" "$TMPDIR/"
if [ ! -d "$TMPDIR/dojo" ];then
    echo "Could not find temporary dojo files at $TMPDIR/dojo"
    exit 42;
fi
cd "$TMPDIR/dojo/";

# Remove unnecessary files
echo "    Removing unwanted/unncessary files"
echo "    - all demos"
find . -name 'demos' -print | xargs  rm -Rf
echo "    - dojo tests"
rm -rf dojo/tests
echo "    - benchmark scripts"
rm -rf dijit/bench
echo "    - checkstyle utility"
rm -rf util/checkstyle
echo "    - docscripts utility"
rm -rf util/docscripts
echo "    - jsdoc utility"
rm -rf util/jsdoc
echo "    - dojo website resources"
rm -rf util/resources
echo "    - dijit tests"
mv dijit/tests/_data/countries.json .
mv dijit/tests/_testCommon.js .
rm -rf dijit/tests
mkdir -p dijit/tests/_data
mv countries.json dijit/tests/_data/
mv _testCommon.js dijit/tests/
echo "    - dojox tests"
find dojox/ -name 'tests' -print | xargs rm -Rf

# Move release to packaging directory
echo "    Moving release to packaging directory"
if [ ! -d "$PKGDIR/externals" ];then
    mkdir -p "$PKGDIR/externals"
fi
mv "$TMPDIR/dojo" "$PKGDIR/externals/"
rm -rf "$TMPDIR/dojo"

echo "DONE building Dojo"
