@echo off
cd /d C:\pitcrew
php bin\console app:update-trending-hashtags >> var\log\trending-hashtags.log 2>&1 