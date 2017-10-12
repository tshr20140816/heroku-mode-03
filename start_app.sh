#!/bin/bash

set -x

export TZ=JST-9

unset BASIC_USER
unset BASIC_PASSWORD

printenv

hostname

uname -a

cat /proc/version

cat /proc/cpuinfo

echo apache
vendor/bin/heroku-php-apache2 -C apache.conf www
