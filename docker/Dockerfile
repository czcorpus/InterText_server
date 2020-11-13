FROM php:apache

RUN apt-get update && apt-get -y install git 

RUN git clone https://github.com/czcorpus/InterText_server.git .

RUN docker-php-ext-install mysqli

RUN cp /var/www/html/icons/* /usr/share/apache2/icons

RUN ln -s /usr/local/bin/php /usr/bin/php

#COPY hunalign /var/www/html/hunalign/
