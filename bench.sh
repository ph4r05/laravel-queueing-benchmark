#!/bin/bash

php artisan app:changeDb --conn=mysql

echo "Beanstalkd"
php artisan app:feedJobs --batch --conn=2 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=2 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
echo "Redis" ;\
php artisan app:feedJobs --batch --conn=3 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=3 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
echo "Optimistic: " ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=1 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=1 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=2 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=2 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=5 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=5 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=6 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=6 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=9 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=9 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=10 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=10 --verify=1 --repeat 10 ;\
echo "Pessimistic:" ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=true --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=true --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=true --del-tsx-retry=0 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=true --del-tsx-retry=0 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=0 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=0 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=false --del-tsx-retry=5 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=false --del-tsx-retry=5 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=true --del-tsx-retry=5 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=true --del-tsx-retry=5 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=5 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=5 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=0 --work-clone=0.3 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=0 --work-clone=0.3 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=true --del-tsx-retry=1 --work-clone=0.3 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=true --del-tsx-retry=1 --work-clone=0.3 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=false --del-tsx-retry=5 --work-clone=0.3 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=false --del-tsx-retry=5 --work-clone=0.3 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
echo "done"

echo "no-index" ;\
php artisan app:changeDb --idx=0 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=false --del-tsx-retry=5 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 --key='noidx' ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=false --del-tsx-retry=5 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 --key='noidx' ;\
php artisan app:changeDb --idx=1

# Postgresql
php artisan app:changeDb --conn=pgsql
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=true --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=true --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=false --del-tsx-retry=5 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=false --del-tsx-retry=5 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=1 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=1 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=2 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=2 --verify=1 --repeat 10 ;\
echo "done"

echo "no-index"
php artisan app:changeDb --idx=0
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=false --del-tsx-retry=5 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 --key='noidx' ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=false --del-tsx-retry=5 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 --key='noidx' ;\
php artisan app:changeDb --idx=1

# Sqlite
php artisan app:changeDb --conn=sqlite
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=true --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=true --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=false --del-tsx-retry=5 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=false --del-tsx-fetch=false --del-tsx-retry=5 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=0 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=0 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=1 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=1 --verify=1 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=2 --verify=0 --repeat 10 ;\
php artisan app:feedJobs --batch --conn=1 --del-mark=true --del-tsx-fetch=false --del-tsx-retry=1 --work-clone=0 --batch-size=10000 --window-strategy=2 --verify=1 --repeat 10 ;\

