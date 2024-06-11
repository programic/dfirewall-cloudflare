#!/usr/bin/env bash

# First run
php job.php

# Setup cron
echo "${JOB_INTERVAL} (cd /src && php job.php) >> /dev/stdout 2>&1" | crontab -

# Run cronjob
crond -f