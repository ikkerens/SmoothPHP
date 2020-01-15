# SmoothPHP
# This file is part of the SmoothPHP project.
# **********
# Copyright © 2015-2020
# License: https://github.com/Ikkerens/SmoothPHP/blob/master/License.md
# **********
# Dockerfile

FROM nginx:latest

# Set up environment
RUN apt-get update \
	# Required for setting up this env
	&& apt-get install -y composer zip unzip supervisor \
	# Required for generic SmoothPHP applications
	&& apt-get install -y php-fpm php-pdo-mysql php-pdo-pgsql php-gd php-dom php-intl php-apcu \
	# Clean up the image
	&& apt-get clean \
	# Start the php service at least once
	&& service php7.3-fpm start

WORKDIR /var/git/SmoothPHP
COPY . .
RUN composer install --no-dev

# Prepare working directory
WORKDIR /var/www/project
RUN ln -s /var/git/SmoothPHP/framework framework \
	&& mkdir public \
	&& cp /var/git/SmoothPHP/public/index.php ./public/ \
	&& sed -i 's/dev/prod/' ./public/index.php \
	&& mkdir ./cache && chmod 777 ./cache \
	&& touch production.lock

# Configure nginx
COPY ./conf/nginx.conf /etc/nginx/nginx.conf

# Configure supervisor
COPY ./conf/supervisord.conf /etc/supervisor/supervisord.conf 

EXPOSE 80 443
ENTRYPOINT ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
