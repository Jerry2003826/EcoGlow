# 现有字段清单

这份文件记录接入扩展数据库包**之前**应用里已经存在的东西：两张真实的 MySQL 表，以及前端目前硬编码在模板里的全部数据。

实际数据值在 `frontend-seed-data.json`，本文件只说明结构和语义。

---

## 一、现有数据库表

引擎 MySQL，字符集 `utf8mb4`。由 CakePHP Migrations 建立，迁移文件在 `config/Migrations/`。

### users

管理员登录账号。**目前没有顾客账号**，`/register` 还是静态原型。

| 字段 | 类型 | 可空 | 默认 | 说明 |
|---|---|---|---|---|
| `id` | INT AUTO_INCREMENT | 否 | | 主键 |
| `email` | VARCHAR(255) | 否 | | UNIQUE 索引 |
| `password` | VARCHAR(255) | 否 | | bcrypt，由实体的 `_setPassword()` 经 CakePHP `DefaultPasswordHasher` 自动哈希 |
| `password_reset_token` | VARCHAR(255) | 是 | NULL | 存的是明文令牌的 SHA-256，不是令牌本身；有普通索引 |
| `password_reset_expires` | DATETIME | 是 | NULL | 签发后 1 小时 |
| `created` | DATETIME | 否 | | CakePHP Timestamp behavior 维护 |
| `modified` | DATETIME | 否 | | 同上 |

约束与行为，接入时需要保留等价能力：

- 密码重置令牌只存哈希，链接单次可用——写入新密码的同一次 UPDATE 里把 token 和 expires 一起清空。
- 重置令牌的查询把过期时间作为条件之一，不是查出来再判断。
- `password_reset_token` 和 `password_reset_expires` **不在**实体的 `$_accessible` 里，只能用 `set()` 写，请求数据改不到。
- `password` 和 `password_reset_token` 在 `$_hidden` 里，不会被序列化。
- 登录失败节流用的是 CakePHP Cache（`login_throttle`，文件引擎，`+15 minutes`，5 次上限），**没有用数据库表**。扩展包里的 `login_attempts` 表如果启用，这块要重写。
- 种子管理员由 `config/Seeds/UsersSeed.php` 建立，幂等（已存在则跳过），密码取环境变量 `ADMIN_SEED_PASSWORD`。

### contact_messages

联系表单提交。这是课程验收要求「表单数据存入数据库」对应的表。

| 字段 | 类型 | 可空 | 默认 | 应用层验证 |
|---|---|---|---|---|
| `id` | INT AUTO_INCREMENT | 否 | | |
| `name` | VARCHAR(128) | 否 | | 必填，最长 128 |
| `email` | VARCHAR(255) | 否 | | 必填，邮箱格式，最长 255 |
| `phone` | VARCHAR(32) | 是 | NULL | 最长 32 |
| `subject` | VARCHAR(255) | 否 | | 必填，最长 255 |
| `message` | TEXT | 否 | | 必填，最长 65535 |
| `is_read` | TINYINT(1) | 否 | 0 | 后台标记已读 |
| `created` | DATETIME | 否 | | |

要点：

- `is_read` 和 `created` **不在** `$_accessible` 里。曾经在里面，是个批量赋值漏洞：伪造的 POST 能把自己的留言标成已读或伪造时间。
- 长度上限在应用层和数据库层都有。只有数据库约束时，超长提交会变成 HTTP 500（SQLSTATE `Data too long`）。
- reCAPTCHA 在服务端校验（`src/Service/RecaptchaVerifier.php`），校验失败不写库。**校验时间戳目前没有存**。扩展包给 `contact_messages` 加的 `captcha_verified_at` 只是一个可空列，没有任何 CHECK 约束，web 来源也不强制非空。不过 reCAPTCHA 服务端验证成功后仍然应该写入这个时间戳，因为它是审计价值所在。
- 没有 `status` 状态字段，只有 `is_read` 布尔。扩展包用的是状态枚举，语义更丰富（新建/处理中/已解决），迁移时 `is_read = 1` 对应「已解决」。

### 其他

`phinxlog` 是迁移记录表。`articles` 和 `categories` 是 CakePHP 脚手架遗留，已由 `20260810130000_DropArticlesAndCategories` 删除。

---

## 二、前端硬编码数据

以下数据现在写在模板顶部的 PHP 数组里，接数据库后需要由查询提供。**种子数据必须产出同样的内容和同样的图片文件名**，否则页面展示会变。

实际值见 `frontend-seed-data.json`。

### 商品（`shop.php` 的 `$products`，12 条）

每条的字段：

| 键 | 语义 | 建议落点 |
|---|---|---|
| `image` | 图片文件名，相对 `webroot/img/products/` | `product_images` |
| `alt` | 图片替代文字，逐张不同，不可省 | `product_images` 的替代文字字段 |
| `name` | 商品名 | `products.name` |
| `meta` | 材质与尺寸摘要，如「Turned oak, linen shade, 1.45 m」 | `products.short_description` |
| `price` | 澳元含 GST 价 | `product_variants.price_cents`（整数分） |
| `flag` | 角标，`New` / `Sale` / `null` | 标签或促销 |
| `category` | 分类名 | `categories` |
| `style` | 风格，用于 `/shop` 的筛选 | 属性或标签 |
| `swatches` | 颜色选项数组，每项是 `[名称, hex]` | `product_variants.attributes` |

`/shop` 的筛选项不是独立数据，是从 `$products` 里取 `category` 和 `style` 去重得来的（`shop.php:175-176`）。

### 分类（`home.php` 的 `$collections`，6 条）

字段：`image`（代表图）、`name`（分类名）、`text`（一句说明，含色温、调光方式等具体参数）。

六个分类名：LED Ceiling Lights、Ambient Floor Lamps、Smart Bulbs、Outdoor Solar Lights、Decorative Accessories、Wall Sconces。

分类的 `text` 是有信息量的文案（不是占位符），需要保留，建议放 `categories` 的描述字段。

### 首页热销（`home.php` 的 `$bestSellers`，4 条）

字段与商品行相同的子集：`image`、`alt`、`name`、`meta`、`price`。这四条是 `$products` 里的商品，接数据库后应该由「热销」标记或排序产生，不要再单独维护一份。

### 材质（`home.php` 的 `$materials`，5 条）

字段：`image`（材质微距图，在 `webroot/img/materials/`）、`name`、`text`（讲工艺与选材原因的段落）。

五种：Turned oak、Undyed linen、Opal glass、Brushed brass、Powder-coated aluminium。

这是内容而非商品数据，可以放 `content_pages`，也可以作为属性选项的描述。

### 服务步骤（`home.php` 的 `$steps`，4 条）

字段：`title`、`text`。属于页面内容，落 `content_pages` 即可。

### 商品详情（`product.php`）

- `$product`：9 个键，比列表页多 `flag`、`swatches`，并且 `image` 指向**详情页专用的横向图** `marlow-detail-wide.webp`（列表页用方图，详情页用横图，这是布局需要，见 `webroot/css/site.css` 里 `.product-media-col .product-media` 的注释）。所以一个商品可能需要两张不同比例的图。
- `$globes`：3 个灯泡选项，纯字符串数组。落 `product_variants`。
- `$specs`：7 组键值——Height、Shade diameter、Materials、Fitting、Globe included、Energy rating、Cable。落 `products.specifications` 的 JSON。
- `$related`：4 条相关商品，字段是商品行的子集。

### 购物车（`cart.php`）

- `$cartLines`：3 条，字段 `image`、`name`、`meta`、`variant`（已选规格的文字描述）、`price`、`qty`。
- 定价规则写死在模板里：满 150 澳元免运费，否则平邮 14.95；GST 按含税总额的 1/11 计算（澳洲 10% GST）。这三个数字接入后应该来自配置或 `site_settings`，不要留在模板里。

---

## 三、图片文件

商品图在 `webroot/img/products/`，13 个（12 个商品方图 + 1 张详情页横图）：

```
marlow-floor-lamp      odette-arc-lamp        corva-ceiling-disc
halden-pendant         ashby-twin-sconce      brindle-wall-sconce
aura-smart-bulbs       nimbus-smart-downlight fernway-solar-path
kelso-solar-bollard    linen-drum-shade       rowan-rotary-dimmer
marlow-detail-wide  ← 详情页专用，3:2 横构图
```

材质微距图在 `webroot/img/materials/`，5 个：`oak`、`linen`、`opal`、`brass`、`powder`。

其他：`hero-interior.webp`（首页主图）、`before-lighting.webp` / `after-lighting.webp`（服务区对比滑块）。

全部是 WebP。种子数据存相对路径即可，不要改文件名——模板和 CSS 都按这些名字引用。
