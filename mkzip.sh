#!/bin/bash
VER=`grep "version" meta.ini`
VER=`echo "$VER" | cut -c 16-`
REV=`git rev-parse --short HEAD`
PKG="Metrof_Stats-1.$VER-$REV.zip"
#echo $VER
cd ..
zip -r $PKG stats -x "stats/.git*" -x "*~" -x "stats/mkzip.sh"
