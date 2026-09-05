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
```

`CREATE TABLE IF NOT EXISTS` / `INSERT IGNORE` 可以再跑一遍。`ALTER TABLE ... ADD COLUMN` 只能跑一次。

## archive/

旧稿，列名对不上现在的库（例如 `warehouses`、`total_cents`、`roles.code`）。留下只是方便对照，不要在 dest 上执行。
