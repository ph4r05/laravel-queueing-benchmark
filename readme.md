# Laravel Queueing Benchmark project

Project for benchmarking and testing Laravel queueing system.


## Installation

```bash
sudo apt-get update -y

sudo apt-get install -y python-software-properties
sudo add-apt-repository -y ppa:ondrej/php
sudo apt-get update -y

apt-cache pkgnames | grep php7.1

sudo apt install php7.1 php7.1-cli php7.1-common php7.1-json php7.1-opcache \
     php7.1-mysql php7.1-mbstring php7.1-mcrypt php7.1-zip php7.1-fpm \
     php7.1-sqlite3 php7.1-pgsql php7.1-intl php7.1-xmlrpc php7.1-xml \
     php7.1-gmp php7.1-bcmath
 
sudo apt install php-pear composer \ 
    git rsync htop mytop vim mc libdbd-mysql-perl libdbi-perl \
    build-essential tcl \
    supervisor \
    beanstalkd \
    redis-server \
    mysql-server \
    postgresql postgresql-contrib

sudo systemctl enable supervisor.service
sudo systemctl enable redis.service
sudo systemctl enable beanstalkd.service

sudo systemctl start supervisor.service
sudo systemctl start redis.service
sudo systemctl start beanstalkd.service
```

### MySQL

```
mysql_secure_installation
sudo systemctl enable mysql.service
sudo systemctl start mysql.service
```

```sql
CREATE DATABASE laravel CHARACTER SET utf8 COLLATE utf8_general_ci;
GRANT ALL PRIVILEGES ON laravel.* TO 'laravel'@'localhost' IDENTIFIED BY 'laravellaravel';
FLUSH PRIVILEGES;
```

### Postgres

```bash
sudo systemctl enable postgresql.service
sudo systemctl start postgresql.service

sudo -u postgres psql
CREATE USER laravel WITH NOSUPERUSER CREATEDB CREATEROLE LOGIN PASSWORD 'laravellaravel';
\q

sudo -u postgres createdb laravel
```

### Laravel 

```bash
sudo adduser --disabled-password laravel

sudo mkdir -p /var/www/laravel


echo '* * * * * laravel php /var/www/laravel/artisan schedule:run >> /dev/null 2>&1' | sudo tee /etc/cron.d/laravel
php artisan migrate
```

Beanstalkd Console:

```bash
composer create-project ptrofimov/beanstalk_console -s dev /var/www/beanstalk-console
php -S [vultr-instance-ip]:7654 -t public
```

Supervisor:

```
echo '
[program:laravel]
process_name=%(program_name)s_%(process_num)02d
directory=/var/www/laravel
command=php /var/www/laravel/artisan app:work --queue=high,default,low --sleep=1 --tries=3
user=laravel
numprocs=10
autostart=true
autorestart=true
stderr_logfile=/var/log/laravel.err.log
stdout_logfile=/var/log/laravel.out.log
' | sudo tee /etc/supervisor/conf.d/laravel.conf
```

Reconfigure supervisord:

```bash
supervisorctl reread
supervisorctl update
```