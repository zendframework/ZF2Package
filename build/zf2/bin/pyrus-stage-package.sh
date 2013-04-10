#!/bin/bash
if [ $# -ne 8 ];then
    echo "Not enough arguments supplied; cannot build DocBook!" ;
    echo "Usage: $0 [component] [version] [php] [tar] [working dir] [zf2 source dir] [pyrus dir] [pyrus-templates dir]" ; 
    exit 1 ;
fi
COMPONENT=$1
VERSION=$2
PHP=$3
TAR=$4
CURDIR=$5
SOURCE_DIR=$6
PYRUS_DIR=$7
PYRUS_TEMPLATES=$8

COMPONENT_DIR=${PYRUS_DIR}/${COMPONENT}

rm -Rf ${COMPONENT_DIR}/src
FILTER=`${PHP} ${CURDIR}/bin/pyrus-package-filter.php ${COMPONENT} filter`
(
    cd ${SOURCE_DIR} &&
    ( ${TAR} -cf - ${FILTER} | ${TAR} -C ${COMPONENT_DIR} -xf - )
)
(
    cd ${COMPONENT_DIR} &&
    if [ -d library ] ; then mv library src ; fi &&
    mv RELEASE-0.1.0 RELEASE-${VERSION} &&
    mv API-0.1.0 API-${VERSION} &&
    rm package_compatible.xml &&
    rm stub.php &&
    rm -Rf tests/ www/ example/ docs/ data/
)
${PHP} ${CURDIR}/bin/pyrus-create-stub.php ${COMPONENT} ${VERSION} ${PYRUS_TEMPLATES} ${COMPONENT_DIR}
if [ $? -ne 0 ];then
    echo "Failed creating Pyrus stub file" ;
    exit 1 ;
fi
exit 0
