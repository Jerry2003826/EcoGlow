# MySQL 现有项目迁移运行手册

## 0. 不要先在唯一数据库上试

先备份并复制一份数据库。`001_upgrade_existing_legacy.sql` 是由 Phinx 记录控制的一次性 additive migration；不要在迁移失败后不看状态就手工重复执行。

## 1. 只读预检

```bash
mysql --table -u USER -p DATABASE_CLONE < database/mysql/PREFLIGHT_EXISTING_DATABASE.sql
```

必须确认：Oracle MySQL 8.0.21+、两张旧表存在、旧字段类型与清单一致、没有与新模块同名的表，也没有已经人工添加过的升级字段。预检出现 collision 时，先对照当前仓库 migration 决定跳过、改名或建立 reconciliation migration。

## 2. 推荐的 CakePHP 路径

```bash
bin/cake migrations status
bin/cake migrations migrate
MASTER_USER_EMAIL=owner@example.com bin/cake migrations seed --seed EcoGlowAuthorizationSeed
bin/cake migrations seed --seed FrontendCatalogSeed
```

`FrontendCatalogSeed` 会寻找真实 `frontend-seed-data.json`，严格检查 12 商品、6 分类、4 热销、5 材质和 4 个服务步骤，并保留原价格、文案、alt、swatches 和图片文件名。

## 3. SQL CLI 备用路径

仅在不使用 CakePHP Migrations 时，从包根目录执行：

```bash
mysql --default-character-set=utf8mb4 -u USER -p DATABASE_CLONE < install_existing_mysql.sql
```

不要把 CakePHP 路径和 CLI 路径混用，否则会绕过 `phinxlog`。

## 4. 验证

```bash
mysql --table -u USER -p DATABASE_CLONE < tests/mysql_smoke_test.sql
bin/cake migrations status
```

然后回归：旧管理员登录、错误登录节流、密码重置、公共联系表单、CAPTCHA 失败不落库、后台消息读取、商品列表图片、购物车金额、GST 与运费。

## 5. 上线

在维护窗口内重新备份生产库，部署同一 commit，运行 migration 和必要 seed。`tests/mysql_smoke_test.sql` 会写入并清理专用测试行，只能在数据库副本、staging 或明确批准的维护环境运行；不要把它当作生产只读检查。生产环境只执行 migration status、只读结构查询和页面/API 回归。生产回退以数据库备份恢复和应用版本回滚为准。

`DEV_ONLY_ROLLBACK_NEW_MODULES.sql` 只供无真实数据的开发副本使用；它不会把 `users` 与 `contact_messages` 新增列恢复成旧结构，不能当生产回滚方案。
