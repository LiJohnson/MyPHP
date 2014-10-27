#!/bin/sh
#
# generate php document
# 
# ref http://www.phpdoc.org/docs/latest/index.html
phpdoc run \
	--directory="." \
	--target="./doc" \
	--defaultpackagename="MyPHP" \
	--title="MyPHP" \
#	--template="responsive-twig" \
	--ignore="test/*,lib/*" 
