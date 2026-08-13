# 必须由应用层保证的约束

这两条规则无法做成数据库约束。实现对应功能时，必须在同一个事务里查重并加锁，而不是依赖 schema。

## 每种货币只能有一个默认且启用的价格表

`price_lists` 表的「每种货币只能有一个默认且启用的价格表」无法做成数据库约束。

原因：MariaDB 对表达式返回字符串列的生成列，允许建 VIRTUAL 列，但拒绝在其上建唯一索引（唯一比较需要确定的 collation，而 CASE 表达式推导不出）；STORED 形式则连列都不允许建。因此包里原本的 `default_active_currency_key` 生成列和 `uq_default_price_list_currency` 唯一索引已被移除，换成普通索引 `idx_price_lists_default_active_currency`。

将来实现价格表功能时，必须在「把某个价格表设为默认」的那个事务里查重并加锁。

## 预约时段防重叠

MariaDB / MySQL 都没有 PostgreSQL 的排他约束（exclusion constraint），所以预约时段防重叠（`service_appointments`）必须在事务内：

1. 锁定该员工的排班范围；
2. 查询重叠的 `tentative` / `confirmed` / `in_progress` 预约；
3. 确认无冲突后再插入。

包内保留了必要索引，但不能单独保证并发下不重叠。
