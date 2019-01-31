#!/bin/sh

REV_FILE="rev.txt"
CHKSUM_FILE="sha256sum.txt"

if [ "$1" = "-c" ]; then
    git log -n 1 --pretty=format:'%cI %H%d %s' > $REV_FILE
    find ./assets/ ./app/ -type f \( -name "*.css" -o -name "*.js" -o -name "*.php" \) -exec sha256sum {} \; > $CHKSUM_FILE
    find . -maxdepth 1 -type f \( -name "*.php" -o -name "rev.txt" \) -exec sha256sum {} \; >> $CHKSUM_FILE
    echo "Checksums computed."
    exit
fi

if [ ! -e $CHKSUM_FILE ]; then
    echo "Checksums file \"$CHKSUM_FILE\" not found."
    exit 1
fi

RESULT=$(sha256sum --quiet -c $CHKSUM_FILE)
if [ -n "$RESULT" -o $? -ne 0 ]; then
    echo "$RESULT"
    echo "To recompute checksums use flag \"-c\"."
    exit 2
fi

echo "Verification succeeded."
echo "To recompute checksums use flag \"-c\"."

