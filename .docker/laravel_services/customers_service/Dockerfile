    FROM php:8.2-fpm

    # Arguments defined in docker-compose.yml
    ARG user
    ARG uid

    # Install system dependencies
    RUN apt-get update && apt-get install -y \
        libfreetype-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        git \
        curl \
        libpq-dev \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        zip \
        unzip \
        supervisor \
        && docker-php-ext-configure gd --with-freetype --with-jpeg \
        && docker-php-ext-install -j$(nproc) gd 
    

    #  librdkafka-dev \
    # && pecl install rdkafka \
    # && docker-php-ext-enable rdkafka

    # Clear cache
    RUN apt-get clean && rm -rf /var/lib/apt/lists/*

    # Install PHP extensions
    RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

    # Instala Supervisor
    #RUN apt-get install -y supervisor

    # Get latest Composer
    COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

    RUN git clone https://github.com/edenhill/librdkafka.git \
        && cd librdkafka \
        && ./configure \
        && make \
        && make install \
        && pecl install rdkafka \
        && docker-php-ext-enable rdkafka

    RUN usermod -u $uid www-data

    # Create system user to run Composer and Artisan Commands
    #RUN useradd -G www-data,root -u $uid -d /home/$user $user
    RUN mkdir -p /home/$user/.composer && \
        chown -R $user:$user /home/$user




    # Añade el archivo de configuración de Supervisor
    COPY Supervisord/supervisord.conf /etc/supervisor/conf.d/supervisord.conf



    # Set working directory
    WORKDIR /var/www

    USER www-data

    # Comando para ejecutar Supervisor al iniciar el contenedor

    CMD ["supervisord", "-n"]



