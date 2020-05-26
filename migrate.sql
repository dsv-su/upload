alter table `items` drop column `upload_deleted`;
alter table `items` add column `end_time` bigint(20);
update `items` set `end_time` = `create_time` + 86400 * 10 where state='pending';
update `items` set `end_time` = `upload_time`+ 86400 * 30 where state='completed';
update `items` set `end_time` = greatest(coalesce(`upload_time`, 0), `create_time`) + 86400 * 180 where state='pruned';
alter table `items` modify `end_time` bigint(20) NOT NULL;
