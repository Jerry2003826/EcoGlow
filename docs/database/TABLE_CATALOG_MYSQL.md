# Eco Glow Lighting MySQL 表目录

完整 schema 共 **144 张表**（包含现有 `users` 与 `contact_messages`）。

未启用模块只是不写入数据，不需要删表。

## 002_foundation_mysql.sql

48 张：

```text
customers | customer_notes | auth_sessions | auth_tokens | addresses | site_settings | content_pages | contact_message_events | categories | brands | products | product_variants | product_images | service_types | inventory_locations | inventory_balances | inventory_movements | suppliers | supplier_products | purchase_orders | purchase_order_items | goods_receipts | goods_receipt_items | carts | cart_items | sales_orders | order_addresses | sales_order_items | order_status_history | payments | payment_refunds | shipments | shipment_items | service_requests | service_appointments | service_notes | product_reviews | ai_conversations | ai_messages | ai_product_recommendations | ai_action_requests | audit_logs | customer_sensitive_profiles | content_sections | content_items | materials | product_materials | saved_cart_items
```

## 003_core_rbac_crm_mysql.sql

32 张：

```text
businesses | business_locations | document_sequences | feature_flags | file_assets | entity_attachments | roles | permissions | role_permissions | user_roles | user_permission_overrides | staff_profiles | login_attempts | approval_requests | approval_decisions | idempotency_records | outbox_events | customer_contacts | customer_consents | tags | customer_tag_assignments | customer_interactions | customer_segments | customer_segment_memberships | notification_preferences | outbound_messages | outbound_message_events | wishlists | wishlist_items | loyalty_accounts | loyalty_transactions | contact_message_attachments
```

## 004_catalogue_pricing_mysql.sql

14 张：

```text
product_category_assignments | attribute_definitions | attribute_options | catalog_attribute_values | tax_categories | tax_rates | price_lists | price_list_entries | customer_price_list_assignments | trade_accounts | product_cost_history | promotions | promotion_codes | promotion_redemptions
```

## 005_commerce_documents_mysql.sql

20 张：

```text
quotations | quotation_versions | quotation_items | quotation_status_history | invoices | invoice_items | invoice_status_history | credit_notes | credit_note_items | payment_allocations | order_notes | order_adjustments | sales_returns | sales_return_items | shipping_zones | shipping_zone_rules | shipping_methods | shipping_rates | shipment_events | fulfilment_tasks
```

## 006_operations_integrations_mysql.sql

28 张：

```text
stock_reservations | stock_counts | stock_count_items | stock_transfers | stock_transfer_items | reorder_rules | purchase_order_status_history | supplier_invoices | supplier_invoice_items | service_request_items | site_surveys | staff_availability | service_work_logs | service_parts_used | external_integrations | integration_events | accounting_exports | import_jobs | import_job_rows | data_retention_policies | privacy_requests | ai_configurations | ai_feedback | ai_usage_logs | service_appointment_staff | service_status_history | payment_events | delivery_bookings
```
