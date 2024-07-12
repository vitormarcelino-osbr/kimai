FROM kimai/kimai2:apache

WORKDIR /opt/kimai

COPY templates/ /opt/kimai/templates
COPY src/Constants.php /opt/kimai/src/Constants.php

