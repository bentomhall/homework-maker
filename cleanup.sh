#!/bin/sh
WEB_ROOT="/Users/bhall/public_html"
find $WEB_ROOT"/downloads/" -type f -Bmin +15 -name "*.zip" -delete
find $WEB_ROOT -type d -name "*tmp" -delete