<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Define all permissions
        $permissions = [
            // Dashboard Permissions
            ['name' => 'dashboard_view', 'description' => 'View dashboard and analytics', 'guard_name' => 'sanctum', 'icon' => 'mdi-view-dashboard', 'color' => 'blue'],
            ['name' => 'dashboard_export', 'description' => 'Export dashboard data and reports', 'guard_name' => 'sanctum', 'icon' => 'mdi-download', 'color' => 'blue'],

            // Call Center Permissions
            ['name' => 'call_center_view', 'description' => 'Access call center interface', 'guard_name' => 'sanctum', 'icon' => 'mdi-headset', 'color' => 'green'],
            ['name' => 'call_center_make_calls', 'description' => 'Make outbound calls', 'guard_name' => 'sanctum', 'icon' => 'mdi-phone-outgoing', 'color' => 'green'],
            ['name' => 'call_center_receive_calls', 'description' => 'Receive and answer incoming calls', 'guard_name' => 'sanctum', 'icon' => 'mdi-phone-incoming', 'color' => 'green'],
            ['name' => 'call_center_transfer_calls', 'description' => 'Transfer calls to other agents or departments', 'guard_name' => 'sanctum', 'icon' => 'mdi-phone-forward', 'color' => 'green'],
            ['name' => 'call_center_hold_calls', 'description' => 'Put calls on hold', 'guard_name' => 'sanctum', 'icon' => 'mdi-phone-pause', 'color' => 'green'],
            ['name' => 'call_center_conference_calls', 'description' => 'Create and manage conference calls', 'guard_name' => 'sanctum', 'icon' => 'mdi-account-group', 'color' => 'green'],
            ['name' => 'call_center_view_call_history', 'description' => 'View call logs and history', 'guard_name' => 'sanctum', 'icon' => 'mdi-history', 'color' => 'green'],
            ['name' => 'call_center_record_calls', 'description' => 'Record phone conversations', 'guard_name' => 'sanctum', 'icon' => 'mdi-record-rec', 'color' => 'red'],

            // Ticket Management Permissions
            ['name' => 'tickets_view', 'description' => 'View support tickets', 'guard_name' => 'sanctum', 'icon' => 'mdi-ticket-outline', 'color' => 'orange'],
            ['name' => 'tickets_create', 'description' => 'Create new support tickets', 'guard_name' => 'sanctum', 'icon' => 'mdi-ticket-plus', 'color' => 'orange'],
            ['name' => 'tickets_edit', 'description' => 'Edit existing tickets', 'guard_name' => 'sanctum', 'icon' => 'mdi-ticket-outline', 'color' => 'orange'],
            ['name' => 'tickets_delete', 'description' => 'Delete support tickets', 'guard_name' => 'sanctum', 'icon' => 'mdi-delete', 'color' => 'red'],
            ['name' => 'tickets_assign', 'description' => 'Assign tickets to agents', 'guard_name' => 'sanctum', 'icon' => 'mdi-account-arrow-right', 'color' => 'orange'],
            ['name' => 'tickets_close', 'description' => 'Close or resolve tickets', 'guard_name' => 'sanctum', 'icon' => 'mdi-check-circle', 'color' => 'green'],
            ['name' => 'tickets_escalate', 'description' => 'Escalate tickets to higher priority', 'guard_name' => 'sanctum', 'icon' => 'mdi-arrow-up-bold', 'color' => 'red'],

            // Communication Permissions
            ['name' => 'messages_view', 'description' => 'View SMS and text messages', 'guard_name' => 'sanctum', 'icon' => 'mdi-message-text', 'color' => 'blue'],
            ['name' => 'messages_send', 'description' => 'Send SMS and text messages', 'guard_name' => 'sanctum', 'icon' => 'mdi-send', 'color' => 'blue'],
            ['name' => 'whatsapp_view', 'description' => 'View WhatsApp conversations', 'guard_name' => 'sanctum', 'icon' => 'mdi-whatsapp', 'color' => 'green'],
            ['name' => 'whatsapp_send', 'description' => 'Send WhatsApp messages', 'guard_name' => 'sanctum', 'icon' => 'mdi-whatsapp', 'color' => 'green'],
            ['name' => 'emails_view', 'description' => 'View email communications', 'guard_name' => 'sanctum', 'icon' => 'mdi-email', 'color' => 'red'],
            ['name' => 'emails_send', 'description' => 'Send emails to customers', 'guard_name' => 'sanctum', 'icon' => 'mdi-email-send', 'color' => 'red'],
            ['name' => 'emails_templates', 'description' => 'Manage email templates', 'guard_name' => 'sanctum', 'icon' => 'mdi-email-outline', 'color' => 'red'],
            ['name' => 'telegram_view', 'description' => 'View Telegram conversations', 'guard_name' => 'sanctum', 'icon' => 'mdi-telegram', 'color' => 'blue'],
            ['name' => 'telegram_send', 'description' => 'Send Telegram messages', 'guard_name' => 'sanctum', 'icon' => 'mdi-telegram', 'color' => 'blue'],

            // Client and Contact Management
            ['name' => 'clients_view', 'description' => 'View client information and profiles', 'guard_name' => 'sanctum', 'icon' => 'mdi-account-multiple', 'color' => 'purple'],
            ['name' => 'clients_create', 'description' => 'Create new client records', 'guard_name' => 'sanctum', 'icon' => 'mdi-account-plus', 'color' => 'purple'],
            ['name' => 'clients_edit', 'description' => 'Edit client information', 'guard_name' => 'sanctum', 'icon' => 'mdi-account-edit', 'color' => 'purple'],
            ['name' => 'clients_delete', 'description' => 'Delete client records', 'guard_name' => 'sanctum', 'icon' => 'mdi-account-remove', 'color' => 'red'],
            ['name' => 'clients_export', 'description' => 'Export client data', 'guard_name' => 'sanctum', 'icon' => 'mdi-download', 'color' => 'blue'],
            ['name' => 'contacts_view', 'description' => 'View contact details', 'guard_name' => 'sanctum', 'icon' => 'mdi-contacts', 'color' => 'teal'],
            ['name' => 'contacts_create', 'description' => 'Add new contacts', 'guard_name' => 'sanctum', 'icon' => 'mdi-contact-plus', 'color' => 'teal'],
            ['name' => 'contacts_edit', 'description' => 'Edit contact information', 'guard_name' => 'sanctum', 'icon' => 'mdi-contact-edit', 'color' => 'teal'],
            ['name' => 'contacts_delete', 'description' => 'Delete contacts', 'guard_name' => 'sanctum', 'icon' => 'mdi-contact-remove', 'color' => 'red'],

            // Notes Management
            ['name' => 'notes_view', 'description' => 'View customer notes and history', 'guard_name' => 'sanctum', 'icon' => 'mdi-note-text', 'color' => 'amber'],
            ['name' => 'notes_create', 'description' => 'Create new notes', 'guard_name' => 'sanctum', 'icon' => 'mdi-note-plus', 'color' => 'amber'],
            ['name' => 'notes_edit', 'description' => 'Edit existing notes', 'guard_name' => 'sanctum', 'icon' => 'mdi-note-edit', 'color' => 'amber'],
            ['name' => 'notes_delete', 'description' => 'Delete notes', 'guard_name' => 'sanctum', 'icon' => 'mdi-delete', 'color' => 'red'],

            // Order Management Permissions
            ['name' => 'orders_view', 'description' => 'View all orders and order details', 'guard_name' => 'sanctum', 'icon' => 'mdi-cart-outline', 'color' => 'indigo'],
            ['name' => 'orders_view_own', 'description' => 'View only own orders (vendor-specific)', 'guard_name' => 'sanctum', 'icon' => 'mdi-cart-outline', 'color' => 'indigo'],
            ['name' => 'orders_view_assigned', 'description' => 'View only assigned orders (delivery agent)', 'guard_name' => 'sanctum', 'icon' => 'mdi-cart-outline', 'color' => 'indigo'],
            ['name' => 'orders_create', 'description' => 'Create new orders', 'guard_name' => 'sanctum', 'icon' => 'mdi-cart-plus', 'color' => 'indigo'],
            ['name' => 'orders_edit', 'description' => 'Edit order information', 'guard_name' => 'sanctum', 'icon' => 'mdi-cart-outline', 'color' => 'indigo'],
            ['name' => 'orders_edit_own', 'description' => 'Edit only own orders', 'guard_name' => 'sanctum', 'icon' => 'mdi-cart-outline', 'color' => 'indigo'],
            ['name' => 'orders_delete', 'description' => 'Cancel or delete orders', 'guard_name' => 'sanctum', 'icon' => 'mdi-cart-remove', 'color' => 'red'],
            ['name' => 'orders_ship', 'description' => 'Process shipping for orders', 'guard_name' => 'sanctum', 'icon' => 'mdi-truck', 'color' => 'green'],
            ['name' => 'orders_ship_own', 'description' => 'Process shipping for own orders only', 'guard_name' => 'sanctum', 'icon' => 'mdi-truck', 'color' => 'green'],
            ['name' => 'orders_dispatch', 'description' => 'Dispatch orders for delivery', 'guard_name' => 'sanctum', 'icon' => 'mdi-send', 'color' => 'orange'],
            ['name' => 'orders_scan', 'description' => 'Scan order barcodes and QR codes', 'guard_name' => 'sanctum', 'icon' => 'mdi-barcode-scan', 'color' => 'blue'],
            ['name' => 'orders_track', 'description' => 'Track order status and delivery', 'guard_name' => 'sanctum', 'icon' => 'mdi-package-variant-closed', 'color' => 'teal'],
            ['name' => 'orders_track_own', 'description' => 'Track own orders only', 'guard_name' => 'sanctum', 'icon' => 'mdi-package-variant-closed', 'color' => 'teal'],
            ['name' => 'orders_track_assigned', 'description' => 'Track assigned orders only', 'guard_name' => 'sanctum', 'icon' => 'mdi-package-variant-closed', 'color' => 'teal'],
            ['name' => 'orders_update_status', 'description' => 'Update order delivery status', 'guard_name' => 'sanctum', 'icon' => 'mdi-update', 'color' => 'orange'],
            ['name' => 'orders_refund', 'description' => 'Process refunds and returns', 'guard_name' => 'sanctum', 'icon' => 'mdi-cash-refund', 'color' => 'red'],

            // User Management Permissions
            ['name' => 'users_view', 'description' => 'View user accounts and profiles', 'guard_name' => 'sanctum', 'icon' => 'mdi-account-group', 'color' => 'blue'],
            ['name' => 'users_create', 'description' => 'Create new user accounts', 'guard_name' => 'sanctum', 'icon' => 'mdi-account-plus', 'color' => 'green'],
            ['name' => 'users_edit', 'description' => 'Edit user information', 'guard_name' => 'sanctum', 'icon' => 'mdi-account-edit', 'color' => 'orange'],
            ['name' => 'users_delete', 'description' => 'Delete or deactivate user accounts', 'guard_name' => 'sanctum', 'icon' => 'mdi-account-remove', 'color' => 'red'],
            ['name' => 'users_impersonate', 'description' => 'Login as another user (admin feature)', 'guard_name' => 'sanctum', 'icon' => 'mdi-account-switch', 'color' => 'deep-purple'],

            // Role and Permission Management
            ['name' => 'roles_view', 'description' => 'View user roles and permissions', 'guard_name' => 'sanctum', 'icon' => 'mdi-account-key', 'color' => 'purple'],
            ['name' => 'roles_create', 'description' => 'Create new user roles', 'guard_name' => 'sanctum', 'icon' => 'mdi-key-plus', 'color' => 'purple'],
            ['name' => 'roles_edit', 'description' => 'Edit existing roles', 'guard_name' => 'sanctum', 'icon' => 'mdi-key-outline', 'color' => 'purple'],
            ['name' => 'roles_delete', 'description' => 'Delete user roles', 'guard_name' => 'sanctum', 'icon' => 'mdi-key-remove', 'color' => 'red'],
            ['name' => 'roles_assign', 'description' => 'Assign roles to users', 'guard_name' => 'sanctum', 'icon' => 'mdi-account-arrow-right', 'color' => 'blue'],
            ['name' => 'permissions_view', 'description' => 'View system permissions', 'guard_name' => 'sanctum', 'icon' => 'mdi-shield-key', 'color' => 'indigo'],
            ['name' => 'permissions_create', 'description' => 'Create new permissions', 'guard_name' => 'sanctum', 'icon' => 'mdi-shield-plus', 'color' => 'indigo'],
            ['name' => 'permissions_edit', 'description' => 'Edit existing permissions', 'guard_name' => 'sanctum', 'icon' => 'mdi-shield-edit', 'color' => 'indigo'],
            ['name' => 'permissions_delete', 'description' => 'Delete permissions', 'guard_name' => 'sanctum', 'icon' => 'mdi-shield-remove', 'color' => 'red'],

            // Reports and Analytics
            ['name' => 'reports_view', 'description' => 'View reports and analytics', 'guard_name' => 'sanctum', 'icon' => 'mdi-chart-box', 'color' => 'green'],
            ['name' => 'reports_view_own', 'description' => 'View own reports only', 'guard_name' => 'sanctum', 'icon' => 'mdi-chart-box', 'color' => 'green'],
            ['name' => 'reports_create', 'description' => 'Generate custom reports', 'guard_name' => 'sanctum', 'icon' => 'mdi-file-chart', 'color' => 'blue'],
            ['name' => 'reports_export', 'description' => 'Export reports in various formats', 'guard_name' => 'sanctum', 'icon' => 'mdi-download', 'color' => 'teal'],
            ['name' => 'reports_export_own', 'description' => 'Export own reports only', 'guard_name' => 'sanctum', 'icon' => 'mdi-download', 'color' => 'teal'],
            ['name' => 'reports_schedule', 'description' => 'Schedule automated reports', 'guard_name' => 'sanctum', 'icon' => 'mdi-calendar-clock', 'color' => 'orange'],
            ['name' => 'reports_share', 'description' => 'Share reports with other users', 'guard_name' => 'sanctum', 'icon' => 'mdi-share-variant', 'color' => 'purple'],

            // Branch Management
            ['name' => 'branches_view', 'description' => 'View branch locations and information', 'guard_name' => 'sanctum', 'icon' => 'mdi-map-marker', 'color' => 'red'],
            ['name' => 'branches_create', 'description' => 'Add new branch locations', 'guard_name' => 'sanctum', 'icon' => 'mdi-map-marker-plus', 'color' => 'green'],
            ['name' => 'branches_edit', 'description' => 'Edit branch information', 'guard_name' => 'sanctum', 'icon' => 'mdi-map-marker-outline', 'color' => 'orange'],
            ['name' => 'branches_delete', 'description' => 'Delete branch locations', 'guard_name' => 'sanctum', 'icon' => 'mdi-map-marker-remove', 'color' => 'red'],
            ['name' => 'cities_manage', 'description' => 'Manage cities and regions', 'guard_name' => 'sanctum', 'icon' => 'mdi-city', 'color' => 'blue'],
            ['name' => 'zones_manage', 'description' => 'Manage delivery zones', 'guard_name' => 'sanctum', 'icon' => 'mdi-map', 'color' => 'teal'],

            // Warehouse Management
            ['name' => 'warehouse_view', 'description' => 'View warehouse information', 'guard_name' => 'sanctum', 'icon' => 'mdi-warehouse', 'color' => 'brown'],
            ['name' => 'warehouse_manage', 'description' => 'Manage warehouse operations', 'guard_name' => 'sanctum', 'icon' => 'mdi-warehouse', 'color' => 'brown'],
            ['name' => 'products_view', 'description' => 'View product catalog', 'guard_name' => 'sanctum', 'icon' => 'mdi-package-variant', 'color' => 'green'],
            ['name' => 'products_view_own', 'description' => 'View own products only', 'guard_name' => 'sanctum', 'icon' => 'mdi-package-variant', 'color' => 'green'],
            ['name' => 'products_create', 'description' => 'Add new products', 'guard_name' => 'sanctum', 'icon' => 'mdi-package-variant-plus', 'color' => 'green'],
            ['name' => 'products_create_own', 'description' => 'Add own products only', 'guard_name' => 'sanctum', 'icon' => 'mdi-package-variant-plus', 'color' => 'green'],
            ['name' => 'products_edit', 'description' => 'Edit product information', 'guard_name' => 'sanctum', 'icon' => 'mdi-package-variant', 'color' => 'orange'],
            ['name' => 'products_edit_own', 'description' => 'Edit own products only', 'guard_name' => 'sanctum', 'icon' => 'mdi-package-variant', 'color' => 'orange'],
            ['name' => 'products_delete', 'description' => 'Delete products from catalog', 'guard_name' => 'sanctum', 'icon' => 'mdi-package-variant-remove', 'color' => 'red'],
            ['name' => 'products_delete_own', 'description' => 'Delete own products only', 'guard_name' => 'sanctum', 'icon' => 'mdi-package-variant-remove', 'color' => 'red'],
            ['name' => 'inventory_view', 'description' => 'View inventory levels and stock', 'guard_name' => 'sanctum', 'icon' => 'mdi-clipboard-list', 'color' => 'blue'],
            ['name' => 'inventory_view_own', 'description' => 'View own inventory only', 'guard_name' => 'sanctum', 'icon' => 'mdi-clipboard-list', 'color' => 'blue'],
            ['name' => 'inventory_adjust', 'description' => 'Adjust inventory quantities', 'guard_name' => 'sanctum', 'icon' => 'mdi-clipboard-edit', 'color' => 'orange'],
            ['name' => 'inventory_adjust_own', 'description' => 'Adjust own inventory only', 'guard_name' => 'sanctum', 'icon' => 'mdi-clipboard-edit', 'color' => 'orange'],
            ['name' => 'inventory_transfer', 'description' => 'Transfer inventory between locations', 'guard_name' => 'sanctum', 'icon' => 'mdi-truck-delivery', 'color' => 'purple'],
            ['name' => 'categories_manage', 'description' => 'Manage product categories', 'guard_name' => 'sanctum', 'icon' => 'mdi-tag-multiple', 'color' => 'pink'],

            // Settings and Configuration
            ['name' => 'settings_view', 'description' => 'View system settings', 'guard_name' => 'sanctum', 'icon' => 'mdi-cog', 'color' => 'grey'],
            ['name' => 'settings_edit', 'description' => 'Modify system settings', 'guard_name' => 'sanctum', 'icon' => 'mdi-cog', 'color' => 'grey'],
            ['name' => 'ivr_manage', 'description' => 'Manage IVR (Interactive Voice Response) settings', 'guard_name' => 'sanctum', 'icon' => 'mdi-phone-settings', 'color' => 'blue'],
            ['name' => 'integrations_view', 'description' => 'View third-party integrations', 'guard_name' => 'sanctum', 'icon' => 'mdi-puzzle', 'color' => 'green'],
            ['name' => 'integrations_manage', 'description' => 'Configure and manage integrations', 'guard_name' => 'sanctum', 'icon' => 'mdi-puzzle', 'color' => 'green'],
            ['name' => 'templates_view', 'description' => 'View message and email templates', 'guard_name' => 'sanctum', 'icon' => 'mdi-file-outline', 'color' => 'purple'],
            ['name' => 'templates_create', 'description' => 'Create new templates', 'guard_name' => 'sanctum', 'icon' => 'mdi-file-plus', 'color' => 'green'],
            ['name' => 'templates_edit', 'description' => 'Edit existing templates', 'guard_name' => 'sanctum', 'icon' => 'mdi-file-edit', 'color' => 'orange'],
            ['name' => 'templates_delete', 'description' => 'Delete templates', 'guard_name' => 'sanctum', 'icon' => 'mdi-file-remove', 'color' => 'red'],
            ['name' => 'call_settings_manage', 'description' => 'Configure call center settings', 'guard_name' => 'sanctum', 'icon' => 'mdi-phone-settings', 'color' => 'blue'],
            ['name' => 'system_status_view', 'description' => 'View system status and health', 'guard_name' => 'sanctum', 'icon' => 'mdi-server', 'color' => 'green'],
            ['name' => 'system_status_manage', 'description' => 'Manage system status configurations', 'guard_name' => 'sanctum', 'icon' => 'mdi-server-security', 'color' => 'red'],

            // Billing and Financial
            ['name' => 'billing_view', 'description' => 'View billing information', 'guard_name' => 'sanctum', 'icon' => 'mdi-credit-card', 'color' => 'green'],
            ['name' => 'billing_manage', 'description' => 'Manage billing and payments', 'guard_name' => 'sanctum', 'icon' => 'mdi-credit-card-settings', 'color' => 'orange'],
            ['name' => 'invoices_view', 'description' => 'View invoices and receipts', 'guard_name' => 'sanctum', 'icon' => 'mdi-receipt', 'color' => 'blue'],
            ['name' => 'invoices_create', 'description' => 'Generate invoices', 'guard_name' => 'sanctum', 'icon' => 'mdi-receipt-text-plus', 'color' => 'green'],
            ['name' => 'payments_view', 'description' => 'View payment history', 'guard_name' => 'sanctum', 'icon' => 'mdi-cash', 'color' => 'green'],
            ['name' => 'payments_process', 'description' => 'Process payments and refunds', 'guard_name' => 'sanctum', 'icon' => 'mdi-cash-register', 'color' => 'blue'],

            // Notifications
            ['name' => 'notifications_view', 'description' => 'View system notifications', 'guard_name' => 'sanctum', 'icon' => 'mdi-bell', 'color' => 'orange'],
            ['name' => 'notifications_view_own', 'description' => 'View own notifications only', 'guard_name' => 'sanctum', 'icon' => 'mdi-bell', 'color' => 'orange'],
            ['name' => 'notifications_create', 'description' => 'Create and send notifications', 'guard_name' => 'sanctum', 'icon' => 'mdi-bell-plus', 'color' => 'green'],
            ['name' => 'notifications_manage', 'description' => 'Manage notification settings', 'guard_name' => 'sanctum', 'icon' => 'mdi-bell-cog', 'color' => 'blue'],

            // Audit and Security
            ['name' => 'audit_logs_view', 'description' => 'View audit trails and activity logs', 'guard_name' => 'sanctum', 'icon' => 'mdi-clipboard-text-clock', 'color' => 'red'],
            ['name' => 'login_history_view', 'description' => 'View user login history', 'guard_name' => 'sanctum', 'icon' => 'mdi-account-clock', 'color' => 'blue'],
            ['name' => 'system_changes_view', 'description' => 'View system configuration changes', 'guard_name' => 'sanctum', 'icon' => 'mdi-cog-clockwise', 'color' => 'orange'],
            ['name' => 'security_settings_manage', 'description' => 'Manage security settings and policies', 'guard_name' => 'sanctum', 'icon' => 'mdi-security', 'color' => 'red'],

            // Developer Tools
            ['name' => 'developer_tools_access', 'description' => 'Access developer tools and APIs', 'guard_name' => 'sanctum', 'icon' => 'mdi-code-tags', 'color' => 'purple'],
            ['name' => 'api_manage', 'description' => 'Manage API keys and access', 'guard_name' => 'sanctum', 'icon' => 'mdi-api', 'color' => 'green'],
            ['name' => 'webhooks_manage', 'description' => 'Configure webhooks and integrations', 'guard_name' => 'sanctum', 'icon' => 'mdi-webhook', 'color' => 'blue'],
            ['name' => 'sandbox_access', 'description' => 'Access sandbox/testing environment', 'guard_name' => 'sanctum', 'icon' => 'mdi-play-box-outline', 'color' => 'orange'],
            ['name' => 'feature_flags_manage', 'description' => 'Manage feature flags and beta features', 'guard_name' => 'sanctum', 'icon' => 'mdi-flag', 'color' => 'red'],

            // Vendor specific permissions
            ['name' => 'vendors_view', 'description' => 'View vendor information and profiles', 'guard_name' => 'sanctum', 'icon' => 'mdi-storefront', 'color' => 'cyan'],
            ['name' => 'vendors_create', 'description' => 'Create new vendor records', 'guard_name' => 'sanctum', 'icon' => 'mdi-storefront-plus', 'color' => 'cyan'],
            ['name' => 'vendors_edit', 'description' => 'Edit vendor information', 'guard_name' => 'sanctum', 'icon' => 'mdi-storefront-edit', 'color' => 'cyan'],
            ['name' => 'vendors_delete', 'description' => 'Delete vendor records', 'guard_name' => 'sanctum', 'icon' => 'mdi-storefront-remove', 'color' => 'red'],
            ['name' => 'vendors_approve', 'description' => 'Approve or reject vendor applications', 'guard_name' => 'sanctum', 'icon' => 'mdi-check-decagram', 'color' => 'green'],
            ['name' => 'vendors_manage', 'description' => 'Full vendor management capabilities', 'guard_name' => 'sanctum', 'icon' => 'mdi-storefront-edit', 'color' => 'blue'],

            // Vendor profile permissions (for vendors themselves)
            ['name' => 'vendor_profile_view', 'description' => 'View own vendor profile', 'guard_name' => 'sanctum', 'icon' => 'mdi-account-circle', 'color' => 'cyan'],
            ['name' => 'vendor_profile_edit', 'description' => 'Edit own vendor profile', 'guard_name' => 'sanctum', 'icon' => 'mdi-account-edit', 'color' => 'cyan'],

            // Scoped message permissions
            ['name' => 'messages_view_own', 'description' => 'View own messages only', 'guard_name' => 'sanctum', 'icon' => 'mdi-message-text', 'color' => 'blue'],
            ['name' => 'emails_view_own', 'description' => 'View own emails only', 'guard_name' => 'sanctum', 'icon' => 'mdi-email', 'color' => 'red'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], $permission);
        }

        // Define roles and assign relevant permissions
        $roles = [
            'Admin' => Permission::all()->pluck('name')->toArray(),
            
            'Manager' => [
                'dashboard_view',
                'dashboard_export',
                'call_center_view',
                'call_center_view_call_history',
                'tickets_view',
                'tickets_assign',
                'tickets_escalate',
                'messages_view',
                'emails_view',
                'clients_view',
                'contacts_view',
                'notes_view',
                'orders_view',
                'orders_edit',
                'orders_ship',
                'orders_dispatch',
                'orders_track',
                'users_view',
                'roles_view',
                'reports_view',
                'reports_create',
                'reports_export',
                'branches_view',
                'warehouse_view',
                'products_view',
                'inventory_view',
                'settings_view',
                'billing_view',
                'invoices_view',
                'notifications_view',
                'audit_logs_view',
                'vendors_view'
            ],
            
            'CallAgent' => [
                'call_center_view',
                'call_center_make_calls',
                'call_center_receive_calls',
                'call_center_transfer_calls',
                'call_center_hold_calls',
                'tickets_view',
                'tickets_create',
                'tickets_edit',
                'tickets_assign',
                'messages_view',
                'messages_send',
                'whatsapp_view',
                'whatsapp_send',
                'emails_view',
                'emails_send',
                'telegram_view',
                'telegram_send',
                'clients_view',
                'clients_create',
                'clients_edit',
                'contacts_view',
                'contacts_create',
                'contacts_edit',
                'notes_view',
                'notes_create',
                'notes_edit',
                'orders_view',
                'orders_create',
                'orders_edit'
            ],
            
            'Vendor' => [
                'dashboard_view',
                'orders_view_own',
                'orders_edit_own',
                'orders_ship_own',
                'orders_track_own',
                'products_view_own',
                'products_create_own',
                'products_edit_own',
                'products_delete_own',
                'inventory_view_own',
                'inventory_adjust_own',
                'reports_view_own',
                'reports_export_own',
                'vendor_profile_view',
                'vendor_profile_edit',
                'messages_view_own',
                'emails_view_own',
                'notifications_view_own'
            ],
            
            'Delivery Agent' => [
                'dashboard_view',
                'orders_view_assigned',
                'orders_scan',
                'orders_track_assigned',
                'orders_update_status',
                'clients_view',
                'contacts_view',
                'messages_view',
                'messages_send',
                'notes_view',
                'notes_create',
                'notifications_view_own'
            ]
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'sanctum']);
            $role->syncPermissions($rolePermissions);
        }
    }
}