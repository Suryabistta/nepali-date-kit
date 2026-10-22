#!/bin/sh
# Build the release ZIP from the committed tree (dev files excluded via .gitattributes).
set -e
cd "$(dirname "$0")/.."
VERSION=$(sed -n 's/^ \* Version: *//p' nepali-date-kit.php)
mkdir -p dist
git archive --format=zip --prefix=nepali-date-kit/ -o "dist/nepali-date-kit-$VERSION.zip" HEAD
echo "dist/nepali-date-kit-$VERSION.zip"
