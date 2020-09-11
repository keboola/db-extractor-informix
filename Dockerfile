# Not open-source drivers are stored in "keboola-drivers" S3
# Open-source drivers can be downloaded directly (info if you need to replace ODBC driver).
FROM quay.io/keboola/aws-cli AS keboola-drivers
ARG AWS_SECRET_ACCESS_KEY
ARG AWS_ACCESS_KEY_ID

# IBM Data Server CLI Driver
# https://www.ibm.com/support/knowledgecenter/SSEPGG_11.5.0/com.ibm.db2.luw.apdv.cli.doc/doc/t0023867.html
# https://www.ibm.com/support/knowledgecenter/SSEPGG_11.5.0/com.ibm.db2.luw.apdv.cli.doc/doc/t0023864.html
RUN /usr/bin/aws s3 cp s3://keboola-drivers/db2-informix-odbc/v11.5.4_linuxx64_odbc_cli.tar.gz  /tmp/db2-informix-odbc.tar.gz

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
# https://www.ibm.com/support/knowledgecenter/SSEPGG_11.5.0/com.ibm.db2.luw.apdv.cli.doc/doc/t0023864.html
COPY --from=keboola-drivers /tmp/db2-informix-odbc.tar.gz /tmp/db2-informix-odbc.tar.gz
ENV DRIVER_DIR="/opt/ibm/odbc_cli/clidriver"
ENV LD_LIBRARY_PATH="${DRIVER_DIR}/lib"
RUN mkdir /opt/ibm \
    && tar -C /opt/ibm -xvf /tmp/db2-informix-odbc.tar.gz \
    && rm /tmp/db2-informix-odbc.tar.gz \
    && echo "[IBM DRIVER]\nDescription=IBM DRIVER\nDriver=${DRIVER_DIR}/lib/libdb2.so\nFileusage=1\nDontdlclose=1\n" > /etc/odbcinst.ini

# Fix SSL configuration to be compatible with older servers
RUN \
    # https://wiki.debian.org/ContinuousIntegration/TriagingTips/openssl-1.1.1
    sed -i 's/CipherString\s*=.*/CipherString = DEFAULT@SECLEVEL=1/g' /etc/ssl/openssl.cnf \
    # https://stackoverflow.com/questions/53058362/openssl-v1-1-1-ssl-choose-client-version-unsupported-protocol
    && sed -i 's/MinProtocol\s*=.*/MinProtocol = TLSv1/g' /etc/ssl/openssl.cnf

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
