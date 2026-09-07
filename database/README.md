# database

只认这个目录。以前的 `db/sql/` 草稿已经挪到 `archive/`，不要再跑。

## 新库（从零装）

按文件名顺序执行：

1. `schema.sql` — 表结构（含配送字段和 `service_bookings`）
2. `views.sql` — 仪表盘 / 低库存视图
3. `seed_rbac.sql` — 角色和权限（含 `dashboard.view`）
4. `seed_admin.sql` — `admin@ecoglow.local`
5. `seed_demo.sql` — 演示订单、预约、低库存、留言

CakePHP 自带的 `config/schema/*.sql` 和 `tests/schema.sql` 不要和这套混用。

## 已经在跑的 dest

不要重跑 `schema.sql`。拉代码后只跑还没跑过的补丁，按文件名顺序：

```
patches/20260905_01_alter_order_delivery.sql
patches/20260905_02_schema_service_bookings.sql
patches/20260905_03_seed_service_bookings.sql
patches/20260905_04_schema_suppliers.sql
patches/20260905_05_seed_suppliers.sql
patches/20260905_06_schema_quotations.sql
patches/20260905_07_seed_quotations.sql
patches/20260905_08_alter_customers_address.sql
patches/20260905_09_seed_customer_addresses.sql
patches/20260905_10_seed_standard_staff.sql
patches/20260905_11_schema_invoices.sql
patches/20260905_12_seed_invoices.sql
patches/20260905_13_dest_users_legacy_compat.sql
patches/20260907_14_alter_suppliers_address.sql
patches/20260907_15_alter_quotations_category.sql
```

dest 必须按顺序跑完 01–15。缺 01–07 时订单 / 报价 / 供应商页会 500。

`20260905_13` 只给 dest 遗留的 `users.username` / `users.user_password` 用。新库按上面「从零装」走，不要跑这一条。

补丁 10 只写 `email/password/first_name/role/status`。dest 若这两列仍是 NOT NULL，staff403 种不进去，要先跑 13。

`CREATE TABLE IF NOT EXISTS` / `INSERT IGNORE` 可以再跑一遍。`ALTER TABLE ... ADD COLUMN` 只能跑一次。

## archive/

旧稿，列名对不上现在的库（例如 `warehouses`、`total_cents`、`roles.code`）。留下只是方便对照，不要在 dest 上执行。
