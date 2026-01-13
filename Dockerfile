# Use Ubuntu 22.04 (or 20.04 if required) as the base image
FROM ubuntu:22.04

# Set environment variable to avoid interactive prompts
ENV DEBIAN_FRONTEND=noninteractive

ARG XDEBUG

# Step 1: Update package lists and install prerequisites for adding repositories
RUN apt-get update && apt-get install -y \
    software-properties-common \
    locales wget curl git gnupg2 nano apt-transport-https && \
    locale-gen en_US.UTF-8

# Step 2: Add the Ondrej PHP PPA to get PHP 8.2
RUN add-apt-repository ppa:ondrej/php && apt-get update

# Step 3: Install PHP 8.2 and necessary extensions
RUN apt-get install -y \
    php8.2-bcmath php8.2-bz2 php8.2-cli php8.2-common php8.2-curl \
    php8.2-cgi php8.2-dev php8.2-fpm php8.2-gd php8.2-gmp php8.2-imap php8.2-intl \
    php8.2-ldap php8.2-mbstring php8.2-mysql php8.2-odbc php8.2-opcache \
    php8.2-pgsql php8.2-phpdbg php8.2-pspell php8.2-readline php8.2-soap \
    php8.2-sqlite3 php8.2-tidy php8.2-xml php8.2-xmlrpc php8.2-xsl php8.2-zip \
    php8.2-mongodb php8.2-swoole php8.2-xdebug \
    mysql-client cron supervisor

# Step 4: Install Node.js and npm
RUN curl -sL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs

# Step 5: Install and configure SQL Server ODBC driver and PHP SQL Server extensions
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - && \
    curl https://packages.microsoft.com/config/ubuntu/22.04/prod.list > /etc/apt/sources.list.d/mssql-release.list && \
    apt-get update && ACCEPT_EULA=Y apt-get install -y msodbcsql18 unixodbc-dev && ACCEPT_EULA=Y apt-get install -y mssql-tools && \
    echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bash_profile && echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bashrc && \
    pecl install sqlsrv pdo_sqlsrv && \
    echo "; priority=20\nextension=sqlsrv.so\n" > /etc/php/8.2/mods-available/sqlsrv.ini && \
    echo "; priority=30\nextension=pdo_sqlsrv.so\n" > /etc/php/8.2/mods-available/pdo_sqlsrv.ini && \
    phpenmod -v 8.2 sqlsrv pdo_sqlsrv

# Step 6: Configure PHP-FPM and general PHP settings
RUN sed -i "s/;date.timezone =.*/date.timezone = UTC/" /etc/php/8.2/cli/php.ini && \
    sed -i "s/;date.timezone =.*/date.timezone = UTC/" /etc/php/8.2/fpm/php.ini && \
    sed -i "s/memory_limit =.*/memory_limit = 1024M/" /etc/php/8.2/fpm/php.ini && \
    sed -i "s/display_errors = Off/display_errors = Off/" /etc/php/8.2/fpm/php.ini && \
    sed -i "s/upload_max_filesize = .*/upload_max_filesize = 100M/" /etc/php/8.2/fpm/php.ini && \
    sed -i "s/post_max_size = .*/post_max_size = 100M/" /etc/php/8.2/fpm/php.ini && \
    sed -i "s/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/" /etc/php/8.2/fpm/php.ini && \
    sed -i -e "s/pid =.*/pid = \/var\/run\/php8.2-fpm.pid/" /etc/php/8.2/fpm/php-fpm.conf && \
    sed -i -e "s/error_log =.*/error_log = \/proc\/self\/fd\/2/" /etc/php/8.2/fpm/php-fpm.conf && \
    sed -i -e "s/;daemonize\s*=\s*yes/daemonize = no/g" /etc/php/8.2/fpm/php-fpm.conf && \
    sed -i "s/listen = .*/listen = 9000/" /etc/php/8.2/fpm/pool.d/www.conf && \
    sed -i "s/;catch_workers_output = .*/catch_workers_output = yes/" /etc/php/8.2/fpm/pool.d/www.conf

# Step 7: Install Composer
RUN curl https://getcomposer.org/installer > composer-setup.php && \
    php composer-setup.php && mv composer.phar /usr/local/bin/composer && rm composer-setup.php

# Set the working directory
WORKDIR /var/www/html

# Copy Supervisor and PHP config
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /etc/php/8.2/mods-available/xdebug.ini

# Ensure proper permissions for Laravel directories
RUN chown -R www-data:www-data /var/www/html

# Command to start supervisor
CMD ["/usr/bin/supervisord"]
