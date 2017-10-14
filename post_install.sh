#!/bin/bash

set -x

date

if [ ! -v BASIC_USER ]; then
  echo "Error : BASIC_USER not defined."
  exit
fi

if [ ! -v BASIC_PASSWORD ]; then
  echo "Error : BASIC_PASSWORD not defined."
  exit
fi

if [ ! -v REMOTE_PATH_1 ]; then
  echo "Error : REMOTE_PATH_1 not defined."
  exit
fi

if [ ! -v REMOTE_PATH_2 ]; then
  echo "Error : REMOTE_PATH_2 not defined."
  exit
fi

if [ ! -v REMOTE_PATH_3 ]; then
  echo "Error : REMOTE_PATH_3 not defined."
  exit
fi

export HOME2=${PWD}
export PATH="${HOME2}/usr/local/bin:${PATH}"

mkdir -m 777 delegate
mkdir -m 777 -p delegate/icons

mkdir -m 777 -p usr/local
mkdir -m 777 ccache

if [ -e ccache_cache.zip ]; then
  time unzip -q ccache_cache.zip
  rm -f ccache_cache.zip
fi

export CFLAGS="-O2 -march=native"
export CXXFLAGS="$CFLAGS"

if [ -e ccache.zip ]; then
  mkdir -m 777 -p usr/local/bin
  mv ccache.zip usr/local/bin/
  pushd usr/local/bin/
  time unzip ccache.zip
  rm -f ccache.zip
  popd
  rm -f ccache-3.3.4.tar.gz
else
  time wget https://www.samba.org/ftp/ccache/ccache-3.3.4.tar.gz
  time tar xfz ccache-3.3.4.tar.gz
  pushd ccache-3.3.4
  time ./configure --prefix=${HOME2}/usr/local
  time make -j$(grep -c -e processor /proc/cpuinfo)
  time make install
  popd
fi
pushd usr/local/bin
ln -s ccache gcc
ln -s ccache g++
ln -s ccache cc
ln -s ccache c++
popd

export CCACHE_DIR=${HOME2}/ccache

ccache -s
ccache -z

if [ ! -e delegate9.9.13.tar.gz ]; then
  time wget http://delegate.hpcc.jp/anonftp/DeleGate/delegate9.9.13.tar.gz
fi
time tar xfz delegate9.9.13.tar.gz
rm -f delegate9.9.13.tar.gz
pushd delegate9.9.13

rm ./src/builtin/mssgs/news/artlistfooter.dhtml
cat << '__HEREDOC__' > ./src/builtin/mssgs/news/artlistfooter.dhtml
<HR></HTML>
__HEREDOC__

time make -j$(grep -c -e processor /proc/cpuinfo) ADMIN="admin@localhost"

cp ./src/delegated ../delegate/
cp ./src/builtin/icons/ysato/*.gif ../delegate/icons/
cp ./src/builtin/icons/ysato/*.gif ../www/icons/
cp ${HOME2}/delegate.conf ../delegate/

popd
rm -rf delegate9.9.13

ccache -s

pushd ${HOME2}

rm -rf ./usr
rm -rf ./ccache
rm -f delegate.zip

chmod 755 ./start_web.sh
chmod 755 ./start_worker.sh

htpasswd -c -b .htpasswd ${BASIC_USER} ${BASIC_PASSWORD}

popd

date