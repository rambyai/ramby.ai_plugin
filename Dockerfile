# Base image for Apache with PHP
FROM php:8.3-apache

# Enable Apache modules
RUN a2enmod rewrite

# Set the document root (if different)
WORKDIR /var/www/html

# Copy your web files to the container
COPY . /var/www/html

# Expose port 80
EXPOSE 80