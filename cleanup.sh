#!/bin/sh
$WEB_ROOT="/users/bhall/public_html"
find $WEB_ROOT -type f -Bmin +15 -name "*.zip" -delete
find $WEB_ROOT -type d -empty -delete
