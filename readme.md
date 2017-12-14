# Laravel Queueing Benchmark project

Project for benchmarking and testing Laravel queueing system.


## Installation

```bash
sudo apt-get install -y python-software-properties
sudo add-apt-repository -y ppa:ondrej/php
sudo apt-get update -y

apt-cache pkgnames | grep php7.1

sudo apt install php7.1 php7.1-cli php7.1-common php7.1-json php7.1-opcache \
    php7.1-mysql php7.1-mbstring php7.1-mcrypt php7.1-zip php7.1-fpm \
    php7.1-sqlite3 php7.1-pgsql php7.1-intl php7.1-xmlrpc php7.1-xml
 
sudo apt install build-essential tcl
sudo apt install supervisor
sudo apt install redis-server
sudo apt install mysql-server
sudo apt install postgresql postgresql-contrib

sudo systemctl enable supervisor.service
sudo systemctl enable redis.service

sudo systemctl start supervisor.service
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
\q

sudo -u postgres createuser --interactive
sudo -u postgres createdb laravel
```

### Laravel 

```bash
adduser laravel --disable-password

echo '* * * * * laravel php /var/www/laravel/artisan schedule:run >> /dev/null 2>&1' | sudo tee /etc/cron.d/laravel
php artisan migrate
```


Supervisor:

```
[program:laravelOpt]
process_name=%(program_name)s_%(process_num)02d
directory=/var/www/laravel
command=php /var/www/laravel/artisan queue:work ph4DBOptim --queue=high,default,low --sleep=1 --tries=3
user=laravel
numprocs=50
autostart=true
autorestart=true
stderr_logfile=/var/log/laravel-opt.err.log
stdout_logfile=/var/log/laravel-opt.out.log
```


```
[program:laravelPess]
process_name=%(program_name)s_%(process_num)02d
directory=/var/www/laravel
command=php /var/www/laravel/artisan queue:work ph4DBPess --queue=high,default,low --sleep=1 --tries=3
user=laravel
numprocs=50
autostart=true
autorestart=true
stderr_logfile=/var/log/laravel-pess.err.log
stdout_logfile=/var/log/laravel-pess.out.log
```