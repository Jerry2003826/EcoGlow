# Eco Glow Lighting — MySQL 8 / CakePHP Compatible Database Pack v3

这版不是 PostgreSQL schema 的机械换后缀，而是针对现有 **MySQL + CakePHP Migrations** 项目做的兼容升级：保留两张现有表及其原字段，用 additive migration 扩展，再建立模块化业务底座。

## 规模

- **144 张表**，包含现有 `users`、`contact_messages`；
- 8 个 CakePHP migration loader；
- RBAC、CRM、商品/SKU、购物车、保存稍后、订单、交付日期、库存、采购、报价、发票、付款、退款、配送、安装维修、忠诚度、AI、审计与报表；
- 7 个只读视图、3 个存储过程、3 个触发器；
- fresh install 与 existing upgrade 两条路径；
- MySQL smoke test 与静态验证脚本。

## 最安全的现有项目接入方式

1. 先阅读 `SOURCE_EXISTING_FIELDS.md`，数据库做完整备份，并运行 `database/mysql/PREFLIGHT_EXISTING_DATABASE.sql`；
2. 阅读 `MIGRATION_RUNBOOK_ZH.md`，把本包 `config/Migrations/` 合并到项目同名目录；
3. 同时保留 `config/Migrations/sql/` 与 `database/mysql/`：migration loader 读取前者，授权 seed 和 CLI 安装脚本读取后者；
4. 先在数据库副本执行 `bin/cake migrations migrate`；
5. 保留并按原方式运行 `UsersSeed.php`（`ADMIN_SEED_PASSWORD`），再设置 `MASTER_USER_EMAIL` 运行 `bin/cake migrations seed --seed EcoGlowAuthorizationSeed`；
6. 将真实 `frontend-seed-data.json` 放到 `config/Seeds/data/` 并运行已提供的幂等 `FrontendCatalogSeed`；
7. 仅在数据库副本或 staging 执行会写入并清理专用测试行的 `mysql < tests/mysql_smoke_test.sql`；
8. 回归旧管理员登录、密码重置、联系表单和后台消息页。

## 不使用 CakePHP migration 时

现有库升级：

```bash
mysql --default-character-set=utf8mb4 -u USER -p DATABASE < install_existing_mysql.sql
```

空库安装：

```bash
mysql --default-character-set=utf8mb4 -u USER -p DATABASE < install_fresh_mysql.sql
```

`SOURCE` 使用相对路径，请从本包根目录运行。

## 真实前端 seed 的限制

现有字段说明明确指出真实值位于 `frontend-seed-data.json`，但本次上传没有包含该 JSON。因此本包不会编造 12 个商品的价格、逐图 alt text、meta、style、swatches 或具体文案。`FrontendCatalogSeed.php` 已实现幂等导入器；没有真实 JSON 时会明确停止，绝不会用示例占位值污染数据库。

## 第一迭代实际调用范围

不需要为全部表写页面。优先接入：

```text
users / roles / permissions / user_roles
customers / addresses
content_pages / content_sections / content_items
contact_messages / contact_message_events
categories / products / product_variants / product_images
carts / cart_items / saved_cart_items
sales_orders / sales_order_items / order_status_history
inventory_balances / inventory_movements / stock_reservations
invoices / invoice_items
outbound_messages / audit_logs
service_requests / service_appointments
```

其余模块由 feature flags 控制，建表只是在未来需求确认时避免重构核心表。

## 验证状态

本环境没有 MySQL Server 或 Docker，因此完成了静态解析、表/外键/文件/哈希检查和 PHP lint，但没有冒充 migration 已在真实 MySQL 执行。必须在项目数据库副本中运行 migration 和写入型 smoke test；生产环境只做只读结构检查与应用回归。
