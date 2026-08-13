-- READ ONLY: run against the current database before applying any v3 migration.
-- Example: mysql --table -u USER -p DATABASE < database/mysql/PREFLIGHT_EXISTING_DATABASE.sql

SELECT VERSION() AS mysql_version,
       @@version_comment AS version_comment,
       DATABASE() AS database_name,
       @@character_set_database AS database_charset,
       @@collation_database AS database_collation,
       @@sql_mode AS sql_mode,
       @@time_zone AS session_time_zone,
       @@system_time_zone AS system_time_zone,
       @@lower_case_table_names AS lower_case_table_names;

SELECT CASE
         WHEN LOCATE('MariaDB', VERSION()) > 0 THEN 'BLOCK: package targets Oracle MySQL 8.0.21+'
         WHEN CAST(SUBSTRING_INDEX(VERSION(), '.', 1) AS UNSIGNED) > 8 THEN 'PASS'
         WHEN CAST(SUBSTRING_INDEX(VERSION(), '.', 1) AS UNSIGNED) = 8
          AND (
            CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(VERSION(), '.', 2), '.', -1) AS UNSIGNED) > 0
            OR CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(VERSION(), '.', 3), '.', -1) AS UNSIGNED) >= 21
          ) THEN 'PASS'
         ELSE 'BLOCK: upgrade to MySQL 8.0.21+'
       END AS mysql_version_gate;

SELECT CASE WHEN @@sql_mode LIKE '%STRICT_TRANS_TABLES%' OR @@sql_mode LIKE '%STRICT_ALL_TABLES%'
            THEN 'PASS' ELSE 'WARN: enable strict SQL mode' END AS strict_mode_gate;

SELECT CASE WHEN CONVERT_TZ('2026-01-01 00:00:00', 'UTC', 'Australia/Melbourne') IS NULL
            THEN 'WARN: MySQL timezone tables are not loaded; application fallback will use UTC'
            ELSE 'PASS' END AS timezone_table_gate;

SELECT table_name, engine, table_collation, table_rows
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name IN ('users', 'contact_messages', 'phinxlog')
ORDER BY table_name;

SELECT table_name, ordinal_position, column_name, column_type, is_nullable, column_default, extra
FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name IN ('users', 'contact_messages')
ORDER BY table_name, ordinal_position;

SELECT table_name, index_name, non_unique, seq_in_index, column_name
FROM information_schema.statistics
WHERE table_schema = DATABASE() AND table_name IN ('users', 'contact_messages')
ORDER BY table_name, index_name, seq_in_index;

SELECT 'users' AS table_name, COUNT(*) AS row_count FROM users
UNION ALL
SELECT 'contact_messages', COUNT(*) FROM contact_messages;

SELECT LOWER(email) AS duplicate_email, COUNT(*) AS duplicate_count
FROM users
GROUP BY LOWER(email)
HAVING COUNT(*) > 1;

SELECT table_name AS colliding_new_table
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN (
    'customers',
    'customer_notes',
    'auth_sessions',
    'auth_tokens',
    'addresses',
    'site_settings',
    'content_pages',
    'contact_message_events',
    'categories',
    'brands',
    'products',
    'product_variants',
    'product_images',
    'service_types',
    'inventory_locations',
    'inventory_balances',
    'inventory_movements',
    'suppliers',
    'supplier_products',
    'purchase_orders',
    'purchase_order_items',
    'goods_receipts',
    'goods_receipt_items',
    'carts',
    'cart_items',
    'sales_orders',
    'order_addresses',
    'sales_order_items',
    'order_status_history',
    'payments',
    'payment_refunds',
    'shipments',
    'shipment_items',
    'service_requests',
    'service_appointments',
    'service_notes',
    'product_reviews',
    'ai_conversations',
    'ai_messages',
    'ai_product_recommendations',
    'ai_action_requests',
    'audit_logs',
    'customer_sensitive_profiles',
    'content_sections',
    'content_items',
    'materials',
    'product_materials',
    'saved_cart_items',
    'businesses',
    'business_locations',
    'document_sequences',
    'feature_flags',
    'file_assets',
    'entity_attachments',
    'roles',
    'permissions',
    'role_permissions',
    'user_roles',
    'user_permission_overrides',
    'staff_profiles',
    'login_attempts',
    'approval_requests',
    'approval_decisions',
    'idempotency_records',
    'outbox_events',
    'customer_contacts',
    'customer_consents',
    'tags',
    'customer_tag_assignments',
    'customer_interactions',
    'customer_segments',
    'customer_segment_memberships',
    'notification_preferences',
    'outbound_messages',
    'outbound_message_events',
    'wishlists',
    'wishlist_items',
    'loyalty_accounts',
    'loyalty_transactions',
    'contact_message_attachments',
    'product_category_assignments',
    'attribute_definitions',
    'attribute_options',
    'catalog_attribute_values',
    'tax_categories',
    'tax_rates',
    'price_lists',
    'price_list_entries',
    'customer_price_list_assignments',
    'trade_accounts',
    'product_cost_history',
    'promotions',
    'promotion_codes',
    'promotion_redemptions',
    'quotations',
    'quotation_versions',
    'quotation_items',
    'quotation_status_history',
    'invoices',
    'invoice_items',
    'invoice_status_history',
    'credit_notes',
    'credit_note_items',
    'payment_allocations',
    'order_notes',
    'order_adjustments',
    'sales_returns',
    'sales_return_items',
    'shipping_zones',
    'shipping_zone_rules',
    'shipping_methods',
    'shipping_rates',
    'shipment_events',
    'fulfilment_tasks',
    'stock_reservations',
    'stock_counts',
    'stock_count_items',
    'stock_transfers',
    'stock_transfer_items',
    'reorder_rules',
    'purchase_order_status_history',
    'supplier_invoices',
    'supplier_invoice_items',
    'service_request_items',
    'site_surveys',
    'staff_availability',
    'service_work_logs',
    'service_parts_used',
    'external_integrations',
    'integration_events',
    'accounting_exports',
    'import_jobs',
    'import_job_rows',
    'data_retention_policies',
    'privacy_requests',
    'ai_configurations',
    'ai_feedback',
    'ai_usage_logs',
    'service_appointment_staff',
    'service_status_history',
    'payment_events',
    'delivery_bookings'
  )
ORDER BY table_name;

SELECT table_name, column_name AS colliding_upgrade_column
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND (
    (table_name = 'users' AND column_name IN (
      'first_name','last_name','phone','role','status','email_verified_at','last_login_at',
      'failed_login_count','locked_until','mfa_enabled','mfa_secret_reference','mfa_enrolled_at',
      'last_password_change_at','metadata','preferred_locale','timezone','deleted'
    ))
    OR
    (table_name = 'contact_messages' AND column_name IN (
      'reference_number','customer_id','user_id','source','status','inquiry_type','product_id',
      'service_type_id','preferred_reply_channel','consent_to_contact','captcha_provider',
      'captcha_verified_at','ip_hash','user_agent','assigned_to_user_id','last_response_at',
      'resolved_at','modified'
    ))
  )
ORDER BY table_name, column_name;

SELECT 'Review every non-empty result above before migration. This script made no changes.' AS final_instruction;
