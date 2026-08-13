# MySQL 与现有 CakePHP 兼容说明

## 保留不变的现有字段

`users` 原字段全部保留：`id`、`email`、`password`、`password_reset_token`、`password_reset_expires`、`created`、`modified`。没有把 `password` 改成 `password_hash`，也没有改变密码重置 token 只存 SHA-256 的现有应用约定。

`contact_messages` 原字段全部保留：`id`、`name`、`email`、`phone`、`subject`、`message`、`is_read`、`created`。新字段只做 additive migration；旧表单不提交新字段仍可插入。

## 旧数据迁移

- 已有 `contact_messages.is_read = 1` 回填为 `status = 'resolved'`；
- 留言编号按 `MSG-00000001` 形式回填；
- `captcha_verified_at` 暂时允许 NULL，避免当前 controller 尚未写时间戳时立即失败；
- 新代码应在 reCAPTCHA 服务端验证成功后写入 `captcha_verified_at`；
- `reference_number` 在兼容迁移中保持可空，等所有写入路径都生成编号后再做 hardening migration 改成 NOT NULL。

## CakePHP 实体与认证保护必须继续保留

- `users.password_reset_token` 与 `password_reset_expires` 不进入 `$_accessible`；
- `users.password` 与 `password_reset_token` 继续放在 `$_hidden`；
- `contact_messages.is_read`、`status`、`assigned_to_user_id`、`created`、`modified` 不允许来自公共表单 mass assignment；
- 密码重置查询继续把 `password_reset_expires > NOW()` 放进 SQL 条件，并在改密码的同一 UPDATE 清空 token 与过期时间；
- 现有 `login_throttle` CakePHP Cache（15 分钟窗口、5 次上限）默认继续使用。虽然扩展 schema 预留登录审计/尝试表，但在 Service、测试和部署配置全部改好之前，不得静默切换；
- 原 `UsersSeed.php` 继续负责以 `ADMIN_SEED_PASSWORD` 幂等创建管理员。`EcoGlowAuthorizationSeed` 不创建账号、不改密码，只把 `MASTER_USER_EMAIL` 指向的唯一现有账号绑定为 master。

## MySQL 目标

- MySQL 8.0.21+；
- InnoDB；
- `utf8mb4` / `utf8mb4_unicode_ci`；
- 新业务表使用 BIGINT UNSIGNED 主键，但所有指向现有 `users.id` 与 `contact_messages.id` 的外键使用 INT，类型完全匹配；
- PostgreSQL UUID、enum、array、partial index、GIN、exclusion constraint 和 `TIMESTAMPTZ` 均已替换为 MySQL 方案。

## 额外兼容与并发约束

- 升级 migration 先把新增 `users.role` 以可空字段加入，只对迁移前已有且角色为空的账号回填 `admin`，之后再改为 `NOT NULL DEFAULT 'customer'`，避免未来顾客注册被错误赋成管理员；正式授权仍以 RBAC 表为准。
- `product_images` 区分 `listing_primary` 与 `detail_hero`，保留方形列表图和 3:2 详情横图，不再把同一商品限制为一张图片。
- `sp_apply_inventory_change_in_transaction` 由 CakePHP `Connection::transactional()` 持有事务；独立维护命令才调用自带事务的 `sp_apply_inventory_change` 包装过程。包装过程绝不能嵌套在应用事务中。任何库存过程报错时，应用事务必须整体回滚。
- `tests/mysql_smoke_test.sql` 会创建并清理专用临时业务行，不是只读脚本，只在数据库副本或 staging 运行。

## 不能靠数据库单独完成的约束

MySQL 没有 PostgreSQL exclusion constraint。预约防撞必须通过事务完成：锁定对应员工/资源的排班范围，查询重叠的 tentative/confirmed/in_progress 预约，无冲突后再插入。包内保留了必要索引，但 Cursor 必须在 Service 层实现该事务和并发测试。
