if [ $# -ne 7 ];then
    echo "Not enough arguments supplied; cannot build DocBook!" ;
    echo "Usage: $0 [component] [version] [packages.json] [php] [unzip] [make dir] [export dir]" ; 
    exit 1 ;
fi
COMPONENT=$1
VERSION=$2
PACKAGES=$3
PHP=$4
UNZIP=$5
CURDIR=$6
EXPORT_DIR=$7

PACKAGE_NAME=`${PHP} ${CURDIR}/bin/pyrus-package-filter.php ${COMPONENT} package`
ZIP_PATH=`${PHP} ${CURDIR}/bin/package-version-path.php ${PACKAGE_NAME} ${VERSION} ${PACKAGES}`
${UNZIP} -qod ${EXPORT_DIR}/${COMPONENT}-${VERSION} ${ZIP_PATH}
