#!/bin/sh

: ${PHP:="/usr/local/bin/php"}
: ${HOSTNAME=`hostname`}
: ${APIGILITY_DOWNLOAD_PHP:="/var/www/packages.zendframework.com/bin/stat/apigility_download.php"}
: ${APIGILITY_LOG_DIR:="/usr/local/apache/logs/packages.zendframework.com"}

: ${REPORT_SPREADSHEET:="/var/www/packages.zendframework.com/bin/stat/data/apigility_download.xlsx"}
: ${REPORT_ZIP:="/tmp/Apigility-DownloadReport-`date +%F`.zip"}
: ${MUTT:="/usr/bin/mutt"}
: ${ZIP:="/usr/bin/zip"}
: ${SENDER:=`cat /var/www/packages.zendframework.com/bin/stat/data/sender`}
: ${MAIL_MESSAGE:="Apigility downloads report: `date +%F`"}
: ${MAIL_RECIPIENT_LIST:=`cat /var/www/packages.zendframework.com/bin/stat/data/apigility.email`}
: ${MSG_FILE:="/tmp/email_apigility_stat_summary.txt"}

${PHP} -d "memory_limit=-1" "${APIGILITY_DOWNLOAD_PHP}" "${APIGILITY_LOG_DIR}" > "${MSG_FILE}" 
${ZIP} -qj "${REPORT_ZIP}" "${REPORT_SPREADSHEET}"

${MUTT} -e "my_hdr From:${SENDER}" -s "${MAIL_MESSAGE}" -a "${REPORT_ZIP}" ${MAIL_RECIPIENT_LIST} < "${MSG_FILE}"

/bin/rm -f "${REPORT_ZIP}" "${MSG_FILE}"
