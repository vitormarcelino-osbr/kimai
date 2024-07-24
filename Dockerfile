FROM kimai/kimai2:apache

WORKDIR /opt/kimai

COPY templates/ /opt/kimai/templates
COPY src/Constants.php /opt/kimai/src/Constants.php

# Customized General Reports
COPY src/Controller/Reporting/CustomReportController.php /opt/kimai/src/Controller/Reporting/CustomReportController.php
COPY src/Reporting/CustomReportFilterType.php /opt/kimai/src/Reporting/CustomReportFilterType.php
COPY src/Reporting/CustomReportQuery.php /opt/kimai/src/Reporting/CustomReportQuery.php
COPY src/Reporting/ReportingService.php /opt/kimai/src/Reporting/ReportingService.php
