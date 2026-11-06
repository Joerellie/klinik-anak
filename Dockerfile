FROM php:8.2-apache

# Enable Apache Rewrite Module
RUN a2enmod rewrite

# Install PDO MySQL and PostgreSQL extensions for Supabase support
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy project files to Apache root
COPY . /var/www/html/

# Set working directory & permissions
WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]
