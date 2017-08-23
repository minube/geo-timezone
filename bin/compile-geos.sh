#!/bin/bash

apt-get install php7.0-dev &&\
apt-get install php7.0-zip &&\
apt-get install php7.0-xml &&\
apt-get install php7.0-mbstring &&\

curl -s -O http://download.osgeo.org/geos/geos-3.6.1.tar.bz2 &&\
  tar -xjvf geos-3.6.1.tar.bz2 &&\
  cd geos-3.6.1/ &&\
  ./configure --enable-php &&\
  make &&\
  make install &&\
  cd ..

ldconfig

git clone https://git.osgeo.org/gogs/geos/php-geos.git  &&\
  cd php-geos  &&\
  ./autogen.sh  &&\
  ./configure  &&\
  make  &&\
  make install