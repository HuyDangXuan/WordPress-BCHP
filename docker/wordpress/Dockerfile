FROM wordpress:php8.2-apache

RUN a2enmod rewrite

COPY wordpress/wp-content/themes/op-travel-shop /usr/src/wordpress/wp-content/themes/op-travel-shop
COPY wordpress/wp-content/plugins/op-travel-core /usr/src/wordpress/wp-content/plugins/op-travel-core
COPY wordpress/wp-content/plugins/op-travel-sepay /usr/src/wordpress/wp-content/plugins/op-travel-sepay
