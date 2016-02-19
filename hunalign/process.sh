#!/bin/sh

# Insert preprocessing here (tokenizing, conversion to stemmed text, etc.)

echo 'Running hunalign aligner...'
./hunalign -utf -realign $1.dic $2 $3 2>&1 >$4
echo 'Hunalign finished.'
