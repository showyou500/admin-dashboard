FROM php:8.2-apache

# Optional: Enable mod_rewrite
RUN a2enmod rewrite

# Copy your project files into the web root
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Expose port 80
EXPOSE 80
