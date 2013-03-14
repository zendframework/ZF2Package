#!/bin/sh

: ${PHP:="/usr/local/bin/php"}
: ${HOSTNAME=`hostname`}
: ${DOWNLOAD_REPORT_PHP:="/var/www/packages.zendframework.com/bin/stat/zend_download_report.php"}
: ${FRAMEWORK_LOG_DIR:="/usr/local/apache/logs/packages.zendframework.com"}

: ${REPORT_SPREADSHEET:="/tmp/ZendFramework-DownloadReport-`date +%F`.xlsx"}
: ${REPORT_ZIP:="/tmp/ZendFramework-DownloadReport-`date +%F`.zip"}
: ${RSYNC:="/usr/bin/rsync"}
: ${ZIP:="/usr/bin/zip"}
: ${MUTT:="/usr/bin/mutt"}
: ${MAIL_MESSAGE:="Zend Framework downloads report: `date +%F`"}
: ${MAIL_RECIPIENT_LIST:=`cat /var/www/packages.zendframework.com/bin/stat/data/email`}
: ${SENDER:=`cat /var/www/packages.zendframework.com/bin/stat/data/sender`}
: ${MSG_FILE:="/tmp/email_msg_stat_summary.txt"}

${PHP} -d "memory_limit=-1" "${DOWNLOAD_REPORT_PHP}" -s "${REPORT_SPREADSHEET}" "${FRAMEWORK_LOG_DIR}" > "${MSG_FILE}"
${ZIP} -qj "${REPORT_ZIP}" "${REPORT_SPREADSHEET}"

${MUTT} -e "my_hdr From:${SENDER}" -s "${MAIL_MESSAGE}" -a "${REPORT_ZIP}" ${MAIL_RECIPIENT_LIST} < "${MSG_FILE}"

/bin/rm -f "${REPORT_SPREADSHEET}" "${REPORT_ZIP}" "${MSG_FILE}"

