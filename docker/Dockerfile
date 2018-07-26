FROM alpine

RUN apk add --no-cache \
        runit \
        nginx \
        php7 \
        php7-ctype \
        php7-curl \
        php7-dom \
        php7-fpm \
#        php7-gd \
        php7-iconv \
#        php7-imagick
#        php7-imap \
        php7-json \
        php7-mbstring \
#        php7-opcache \
        php7-openssl \
#        php7-pcntl \
#        php7-pdo_mysql \
#        php7-pdo_pgsql \
        php7-pdo_sqlite \
        php7-phar \
        php7-session \
        php7-simplexml \
        php7-tokenizer \
        php7-xdebug \
        php7-xml \
        php7-xmlwriter \
#        php7-zlib \
    && wget -qO - https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

RUN sed -i \
        -e "s/^\(listen =\).*/\1 \/run\/php7-fpm.sock/" \
        -e "s/^;\(listen.owner =\).*/\1 nginx/" \
        -e "s/^;\(listen.group = \).*/\1 nginx/" \
        -e "s/^\(user =\).*/\1 nginx/" \
        -e "s/^\(group =\).*/\1 nginx/" \
        -e "s/^\(pm.start_servers =\).*/\1 1/" \
        /etc/php7/php-fpm.d/www.conf \
    && sed -i \
        -e "s/^\(error_reporting =\).*/\1 E_ALL/" \
        -e "s/^\(display_errors =\).*/\1 On/" \
        -e "s/^\(display_startup_errors =\).*/\1 On/" \
        /etc/php7/php.ini \
    && echo -e \
        "zend_extension=xdebug.so\n" \
        "xdebug.remote_enable=1\n" \
        "xdebug.remote_autostart=1\n" \
        "xdebug.remote_connect_back=1\n" \
        "xdebug.remote_log=/var/log/xdebug.log\n" \
        > /etc/php7/conf.d/xdebug.ini \
    && touch /var/log/xdebug.log && chown nginx:nginx /var/log/xdebug.log \
    && mkdir -p /run/nginx /etc/service/php-fpm7 /etc/service/nginx \
	&& echo -e "#!/bin/sh\nphp-fpm7 -F" > /etc/service/php-fpm7/run \
    && echo -e "#!/bin/sh\nnginx -g 'daemon off;'" > /etc/service/nginx/run \
	&& chmod -R +x /etc/service/* \
    && sed -i \
        -e "s/\(PS1=\).*/\1'\\\[\\\e[1;34m\\\]\\\w\\\[\\\e[0m\\\]\\\\$ '/" \
        /etc/profile \
    && echo -e \
        "\nalias ll='ls -alF --group-directories-first --color=auto'" \
        >> /etc/profile

COPY docker/vhost.conf /etc/nginx/conf.d/default.conf

VOLUME /var/www

WORKDIR /var/www

EXPOSE 80

CMD runsvdir -P /etc/service
