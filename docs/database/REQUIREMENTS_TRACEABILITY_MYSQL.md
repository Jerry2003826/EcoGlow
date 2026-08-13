# 需求—数据库追踪矩阵（MySQL 版）

| 合并需求 | 主要表/视图 | 建议迭代 |
|---|---|---|
| Landing page 与材料/步骤内容 | `content_pages`, `content_sections`, `content_items`, `materials` | Onboarding/MVP |
| CAPTCHA 联系表单及反馈 | `contact_messages`, `contact_message_events`, `outbound_messages` | Onboarding/MVP |
| 商品浏览、搜索、筛选 | `products`, `product_variants`, `categories`, `attribute_*`, `v_public_product_catalogue` | MVP |
| 客户注册和个人资料 | `users`, `customers`, `customer_sensitive_profiles`, `addresses` | MVP |
| 保存稍后购买 | `saved_cart_items`, `wishlists`, `wishlist_items` | MVP/Should |
| 多渠道统一订单 | `sales_orders.source_channel`, `external_source_reference`, `customer_interactions` | MVP |
| 承诺交付日期 | `sales_orders.promised_delivery_date`, `delivery_bookings` | MVP |
| 库存、防超卖、低库存 | `inventory_balances`, `inventory_movements`, `stock_reservations`, `v_low_stock_items` | MVP |
| 报价及版本 | `quotations`, `quotation_versions`, `quotation_items` | 预留，确认后启用 |
| 发票、GST、促销 | `invoices`, `invoice_items`, `tax_*`, `promotions` | MVP 基础 |
| 部分付款、押金、退款 | `payments`, `payment_allocations`, `payment_refunds`, `payment_events` | 预留，支付规则确认后启用 |
| 安装/维修预约 | `service_requests`, `service_appointments`, `service_appointment_staff`, `service_work_logs` | 独立工作流 |
| 可配置 RBAC | `roles`, `permissions`, `role_permissions`, `user_roles`, overrides | MVP |
| 看板 | `v_business_dashboard_daily`, `v_order_profitability`, `v_invoice_balances` | MVP |
| 忠诚度 | `loyalty_accounts`, `loyalty_transactions` | Phase 2 |
| AI | `ai_*`, `approval_requests` | Future，默认关闭 |
