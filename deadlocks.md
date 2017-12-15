## InnoDB transactions

```bash
yum install innotop
innotop -uroot -ppassword
```
https://www.xaprb.com/blog/2006/07/31/how-to-analyze-innodb-mysql-locks/

Disable deadlock detection for debugging:

```sql
set global innodb_deadlock_detect=0;
set global innodb_lock_wait_timeout=1000;
set innodb_lock_wait_timeout=1000;
```

Index experimenting

```sql
alter table laravel.jobs drop index jobs_queue_index;
alter table laravel.jobs add index `jobs_queue_index` (`queue`); 
```

### InnoDB transaction status

InnoDB transaction status:

Edit `.my.cnf` - set user and password.

```ini
[mysql]
user=root
password=root
```

Sql `diag.sql` file: 
```sql
show full processlist;

use laravel;
SELECT
  r.trx_id waiting_trx_id,
  r.trx_mysql_thread_id waiting_thread,
  r.trx_query waiting_query,
  b.trx_id blocking_trx_id,
  b.trx_mysql_thread_id blocking_thread,
  b.trx_query blocking_query
FROM       information_schema.innodb_lock_waits w
INNER JOIN information_schema.innodb_trx b
  ON b.trx_id = w.blocking_trx_id
INNER JOIN information_schema.innodb_trx r
  ON r.trx_id = w.requesting_trx_id;

USE INFORMATION_SCHEMA
SELECT * FROM INNODB_LOCK_WAITS;

USE INFORMATION_SCHEMA
SELECT INNODB_LOCKS.* 
FROM INNODB_LOCKS
JOIN INNODB_LOCK_WAITS
  ON (INNODB_LOCKS.LOCK_TRX_ID = INNODB_LOCK_WAITS.BLOCKING_TRX_ID);

SHOW ENGINE INNODB STATUS \G
```

```bash
mysql -t -vv < diag.sql
```

