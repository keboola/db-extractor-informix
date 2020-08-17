# Not open-source drivers are stored in "keboola-drivers" S3
# Open-source drivers can be downloaded directly (info if you need to replace ODBC driver).
FROM quay.io/keboola/aws-cli AS keboola-drivers
ARG AWS_SECRET_ACCESS_KEY
ARG AWS_ACCESS_KEY_ID
RUN /usr/bin/aws s3 cp s3://keboola-drivers/informix-odbc/INFORMIX_CLIENT_SDK_4.50.FC4W1_Linux_x86.tar /tmp/informix-odbc.tar

FROM php:7.4-cli

ARG COMPOSER_FLAGS="--prefer-dist --no-interaction"
ARG DEBIAN_FRONTEND=noninteractive
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_PROCESS_TIMEOUT 3600

WORKDIR /code/

COPY docker/php-prod.ini /usr/local/etc/php/php.ini
COPY docker/composer-install.sh /tmp/composer-install.sh

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        locales \
        unzip \
        ssh \
        libncurses5 \
        unixodbc \
        unixodbc-dev \
	&& rm -r /var/lib/apt/lists/* \
	&& sed -i 's/^# *\(en_US.UTF-8\)/\1/' /etc/locale.gen \
	&& locale-gen \
	&& chmod +x /tmp/composer-install.sh \
	&& /tmp/composer-install.sh

ENV LANGUAGE=en_US.UTF-8
ENV LANG=en_US.UTF-8
ENV LC_ALL=en_US.UTF-8

# PHP ODBC
# https://github.com/docker-library/php/issues/103#issuecomment-353674490
RUN set -ex; \
    docker-php-source extract; \
    { \
        echo '# https://github.com/docker-library/php/issues/103#issuecomment-353674490'; \
        echo 'AC_DEFUN([PHP_ALWAYS_SHARED],[])dnl'; \
        echo; \
        cat /usr/src/php/ext/odbc/config.m4; \
    } > temp.m4; \
    mv temp.m4 /usr/src/php/ext/odbc/config.m4; \
    docker-php-ext-configure odbc --with-unixODBC=shared,/usr; \
    docker-php-ext-install odbc; \
    docker-php-source delete

# Install ODBC driver
COPY --from=keboola-drivers /tmp/informix-odbc.tar /tmp/informix-odbc.tar
ENV INFORMIXDIR="/opt/IBM/Informix_Client-SDK/"
ENV LIB_DIR="${INFORMIXDIR}/lib"
ENV LD_LIBRARY_PATH="${LIB_DIR}:${LIB_DIR}/esql:${LIB_DIR}/cli"
ENV CSDK_COMPONENTS="SDK,SDK-ODBC,SDK-ODBC-DEMO,SDK-ESQL,SDK-ESQL-ACM,GLS,GLS-WEURAM,GLS-EEUR,GLS-JPN,GLS-KOR,GLS-CHN,GLS-OTH,DBA-DBA"
RUN mkdir /tmp/informix-odbc \
    && tar -C /tmp/informix-odbc -xvf /tmp/informix-odbc.tar \
    && cp /tmp/informix-odbc/csdk.properties /tmp/informix-odbc/csdk.properties.bak \
    && sed -i 's/CHOSEN_FEATURE_LIST\s*=.*/CHOSEN_FEATURE_LIST=${CSDK_COMPONENTS}/g' /tmp/informix-odbc/csdk.properties \
    && sed -i 's/CHOSEN_INSTALL_FEATURE_LIST\s*=.*/CHOSEN_INSTALL_FEATURE_LIST=${CSDK_COMPONENTS}/g' /tmp/informix-odbc/csdk.properties \
    && /tmp/informix-odbc/installclientsdk -i silent -DLICENSE_ACCEPTED=TRUE \
    && odbcinst -i -d -f /opt/IBM/Informix_Client-SDK/etc/odbcinst.ini \
    && cp /etc/odbcinst.ini /etc/odbcinst.ini.bak \
    && sed -i 's/\/extra\/informix\//\/opt\/IBM\/Informix_Client-SDK\//g' /etc/odbcinst.ini \
    && rm -R /tmp/informix-odbc \
    && rm /tmp/informix-odbc.tar

## Composer - deps always cached unless changed
# First copy only composer files
COPY composer.* /code/

# Download dependencies, but don't run scripts or init autoloaders as the app is missing
RUN composer install $COMPOSER_FLAGS --no-scripts --no-autoloader

# Copy rest of the app
COPY . /code/

# Run normal composer - all deps are cached already
RUN composer install $COMPOSER_FLAGS

CMD ["php", "/code/src/run.php"]
