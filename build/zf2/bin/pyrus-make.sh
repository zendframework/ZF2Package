#!/bin/bash
if [ $# -ne 3 ];then
    echo "Not enough arguments supplied; cannot build Pyrus package!" ;
    echo "Usage: $0 [component] [pyrus] [pyrus-home]" ; 
    exit 1 ;
fi
COMPONENT=$1
PYRUS_COMMAND=$2
PYRUS_DIR=$3

COMPONENT_DIR=${PYRUS_DIR}/${COMPONENT}

( cd ${COMPONENT_DIR} && ${PYRUS_COMMAND} make )
if [ $? -ne 0 ];then
    echo "Failure creating Pyrus component!" ;
    exit 1 ;
fi

( cd ${COMPONENT_DIR} && ${PYRUS_COMMAND} package -p )
if [ $? -ne 0 ];then
    echo "Failure creating Pyrus package!" ;
    exit 1 ;
fi
exit 0
