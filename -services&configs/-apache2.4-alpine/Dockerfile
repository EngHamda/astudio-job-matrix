# Use the official httpd image as the base image
FROM httpd:2.4-alpine

# Set the environment variables
# ENV APACHE_LOG_DIR /usr/local/apache2/logs
ENV APACHE_DIR /usr/local/apache2
ENV APACHE_LOG_DIR ${APACHE_DIR}/logs
# as it laravel app APACHE_DOC_ROOT must be .../public
ENV APACHE_DOC_ROOT /var/www/public

# Enable necessary Apache modules
RUN sed -i '/LoadModule rewrite_module/s/^#//g' /usr/local/apache2/conf/httpd.conf
# Add more commands to enable other modules if needed

# Copy your custom Apache configuration files
COPY ./-services&configs/-apache-config/httpd.conf /usr/local/apache2/conf/httpd.conf
