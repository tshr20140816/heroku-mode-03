#!/bin/bash

set -x

date
start_date=$(date)

export HOME2=${PWD}

cat /proc/version
cat /proc/cpuinfo

ls -lang /tmp

if [ ! -v DEVELOP_MODE ]; then
  export DEVELOP_MODE='OFF'
fi

export CFLAGS="-O2 -march=native"
export CXXFLAGS="$CFLAGS"

bash ./80_install/post_install_sub01.sh &

git clone --depth 1 https://github.com/tshr20140816/heroku-mode-03.git /tmp/self_repository &

# ***** pngquant *****

pushd /tmp
git clone --depth 1 --recursive https://github.com/kornelski/pngquant.git pngquant
pushd pngquant
./configure --prefix=/tmp/usr
time make
make install
popd
popd

cp /tmp/usr/bin/pngquant ./bin/

# apache
chmod 777 www
mkdir -m 777 www/icons

# ***** delegate *****

mkdir -m 777 -p /tmp/usr/bin
mkdir -m 777 -p /tmp/ccache

export PATH="/tmp/usr/bin:${PATH}"

mkdir -m 777 -p delegate/icons

if [ ${DEVELOP_MODE} = 'OFF' ]; then
  mv 90_binary/ccache_cache.zip /tmp/
  pushd /tmp
  unzip -q ccache_cache.zip
  popd
fi
export CCACHE_DIR=/tmp/ccache

ls -lang 90_binary/delegated

if [ ! -e 90_binary/delegated ]; then
  if [ -e 90_binary/ccache.zip ]; then
    mv 90_binary/ccache.zip /tmp/usr/bin/
    pushd /tmp/usr/bin
    unzip ccache.zip
    popd
  else
    pushd /tmp
    wget https://www.samba.org/ftp/ccache/ccache-3.3.4.tar.gz
    tar xf ccache-3.3.4.tar.gz
    pushd ccache-3.3.4
    ./configure --prefix=/tmp/usr
    make -j$(grep -c -e processor /proc/cpuinfo)
    make install
    popd
    popd
  fi

  pushd /tmp/usr/bin
  ln -s ccache gcc
  ln -s ccache g++
  ln -s ccache cc
  ln -s ccache c++
  popd

  ccache -s
  ccache -z
fi

if [ ! -e 90_binary/delegate9.9.13.tar.gz ]; then
  pushd /tmp
  time wget http://delegate.hpcc.jp/anonftp/DeleGate/delegate9.9.13.tar.gz
  popd
else
  mv 90_binary/delegate9.9.13.tar.gz /tmp/
fi
pushd /tmp
tar xf delegate9.9.13.tar.gz

pushd delegate9.9.13

if [ ! -e ${HOME2}/90_binary/delegated ]; then
  rm ./src/builtin/mssgs/news/artlistfooter.dhtml
  echo "<HR>" > ./src/builtin/mssgs/news/artlistfooter.dhtml

  # time make -j$(grep -c -e processor /proc/cpuinfo) ADMIN="admin@localhost"
  time make ADMIN="admin@localhost"

  cp ./src/delegated ${HOME2}/delegate/
  # cp ./src/delegated ${HOME2}/www/
else
  cp ${HOME2}/90_binary/delegated ${HOME2}/delegate/
  chmod +x ${HOME2}/delegate/delegated
fi
cp ./src/builtin/icons/ysato/*.gif ${HOME2}/delegate/icons/

# apache
cp ./src/builtin/icons/ysato/*.gif ${HOME2}/www/icons/

cp ${HOME2}/delegate.conf ${HOME2}/delegate/

popd
popd

if [ ! -e 90_binary/delegated ]; then
  ccache -s
fi

pushd ${HOME2}

if [ ${DEVELOP_MODE} != 'OFF' ]; then
  pushd /tmp
  zip -9r ccache_cache.zip ./ccache
  mv ccache_cache.zip ${HOME2}/www/
  popd
fi

# ***** phppgadmin *****

pushd www
git clone --depth 1 https://github.com/phppgadmin/phppgadmin.git phppgadmin
cp ../70_etc/config.inc.php phppgadmin/conf/
cp ../70_etc/Connection.php phppgadmin/classes/database/
popd

# ***** favicon.ico *****

chmod +x ./bin/brotli
./bin/brotli -q 11 ./www/favicon.ico

wait

# ***** last update *****

pushd /tmp/self_repository

last_update=$(git log | grep Date | grep -o "\w\{3\} .\+$")

echo "${last_update}" > ${HOME2}/www/last_update.txt

popd

chmod 755 start_web.sh
chmod 755 loggly.php

popd

echo ${start_date}
date
