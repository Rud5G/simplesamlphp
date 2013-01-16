#!/usr/bin/env bash


##
# todo: 
# filter double
# check http://simplesamlphp.org/developers

authorsfile=.svn2git/authors.txt

svn log --quiet http://simplesamlphp.googlecode.com/svn | grep -E "r[0-9]+ \| .+ \|" | awk '{print $3}' | grep -v '(no' | sort | uniq > authors.log
echo '(no author) = No Author <no@author.tld>' > $authorsfile 
for line in `cat authors.log` ; do echo "$line = $line <$line> " >> $authorsfile ; done
rm authors.log

##_END
