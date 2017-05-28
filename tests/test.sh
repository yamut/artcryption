#!/usr/bin/env bash

export RED='\033[0;31m'
export GREEN='\033[0;32m'
export NOCOLOR='\033[0m'

export PASS="${GREEN}PASS${NOCOLOR}"
export FAIL="${RED}FAIL${NOCOLOR}"

INFILE1="cat.jpg"
OUTFILE1="cat.jpg.decoded.jpg"

INFILE2="foo.txt"
OUTFILE2="foo.txt.decoded"

php test.php ${INFILE1} ${OUTFILE1} ${INFILE2} ${OUTFILE2}

INFILE1_SIZE=$(ls -l storage/${INFILE1} | awk '{print $5}')
OUTFILE1_SIZE=$(ls -l storage/${OUTFILE1} | awk '{print $5}')

INFILE2_SIZE=$(ls -l storage/${INFILE2} | awk '{print $5}')
OUTFILE2_SIZE=$(ls -l storage/${OUTFILE2} | awk '{print $5}')

if [[ "$INFILE1_SIZE" == "$OUTFILE1_SIZE" ]]
then
    echo -e "Test 1 - filesize: ${PASS}"
else
    echo -e "Test 1 - filesize: ${FAIL}"
fi

if [[ "$INFILE2_SIZE" == "$OUTFILE2_SIZE" ]]
then
    echo -e "Test 2 - filesize: ${PASS}"
else
    echo -e "Test 2 - filesize: ${FAIL}"
fi

if [[ $(diff <(xxd storage/${INFILE2}) <(xxd storage/${OUTFILE2})) ]]
then
    echo -e "Test 2 - binary difference: ${FAIL}"
else
    echo -e "Test 2 - binary difference: ${PASS}"
fi

if [[ $(diff <(xxd storage/${INFILE1}) <(xxd storage/${OUTFILE1})) ]]
then
    echo -e "Test 1 - binary difference: ${FAIL}"
else
    echo -e "Test 1 - binary difference: ${PASS}"
fi

echo "TESTS COMPLETE"
