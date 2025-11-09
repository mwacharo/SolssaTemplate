import { defineStore } from 'pinia'

// Route Registry for consistency
export const ROUTES = {
  DASHBOARD: {
    INDEX: 'dashboard',
    METRICS: 'dashboard.metrics',
    // ACTIVITY: 'dashboard.activity',
    // STATUS: 'dashboard.status',
  },
  CALL_CENTRE: {
    INDEX: 'call-centre.index',
    TICKETS: 'call-centre.tickets',
    MESSAGES: 'call-centre.messages',
    WHATSAPP: 'call-centre.whatsapp',
    EMAILS: 'call-centre.emails',
    CLIENTS: 'call-centre.clients',
    CONTACTS: 'call-centre.contacts',
    TELEGRAM: 'call-centre.telegram',
    NOTES: 'call-centre.notes',
  },
  // order 
  ORDERS: {
    INDEX: 'orders.index',
    SHIPPING: 'orders.shipping',
    SCAN_DISPATCH: 'orders.scan-dispatch',
    DISPATCH_LIST: 'orders.dispatch-list',
    // SHIP: 'orders.ship',
  },


  USERS: {
    // INDEX: 'users.index',
    INDEX: 'admin.users',

    ROLES: 'users.roles',
    PERMISSIONS: 'users.permissions',
    TEAMS: 'users.teams',
    INVITATIONS: 'users.invitations',
  },
  REPORTS: {
    INDEX: 'reports.index',
    // GENERATE: 'reports.generate',
    // EXPORT: 'reports.export',
    // SCHEDULE: 'reports.schedule',
  },
  BRANCHES: {
    INDEX: 'branches.index',
    // CREATE: 'branches.create',
    // LOCATIONS: 'branches.locations',
    // add cities and zones
    CITIES: 'branches.cities',
    ZONES: 'branches.zones',
  },
  WAREHOUSE: {
    INDEX: 'warehouse.index', // 👈 This should match Laravel's ->name('warehouse.index')
    PRODUCTS: 'warehouse.products', // Route::get('/products', ...)->name('products')
    INVENTORY: 'warehouse.inventory', // Route::get('/inventory', ...)->name('inventory')
    STATISTICS: 'warehouse.statistics', // Route::get('/statistics', ...)->name('statistics')
    CATEGORY: 'warehouse.category', // Route::get('/category', ...)->name('category')
    // ORDERS: 'warehouse.orders',
    // SUPPLIERS: 'warehouse.suppliers',
  },
  SETTINGS: {
    // PROFILE: 'settings.profile',
    // ORG: 'settings.org',
    IVR: 'settings.ivr',
    INTEGRATIONS: 'settings.integrations',
    TEMPLATES: 'settings.templates',
    // add call settings 
    CALL_SETTINGS: 'settings.call-settings',
    // add status
    STATUS: 'settings.status',
    // FEATURES: 'settings.features',
    // NOTIFICATIONS: 'settings.notifications',
    // BRANDING: 'settings.branding',
  },
  INTEGRATIONS: {
    // API: 'integrations.api',
    // WEBHOOKS: 'integrations.webhooks',
    // APPS: 'integrations.apps',
    MARKETPLACE: 'integrations.marketplace',
    SHOPIFY: 'integrations.shopify',
  },
  BILLING: {
    PLANS: 'billing.plans',
    PAYMENTS: 'billing.payments',
    INVOICES: 'billing.invoices',
    USAGE: 'billing.usage',
    LICENSES: 'billing.licenses',
  },
  NOTIFICATIONS: {
    CENTER: 'notifications.center',
    ACTIVITY: 'notifications.activity',
    STATUS: 'notifications.status',
  },
  AUDIT: {
    LOGINS: 'audit.logins',
    ACTIONS: 'audit.actions',
    SYSTEM: 'audit.system',
  },
  DEVELOPER: {
    DOCS: 'developer.docs',
    SDKS: 'developer.sdks',
    SANDBOX: 'developer.sandbox',
    STATS: 'developer.stats',
  },

  VENDOR: {
    INDEX: 'vendor.index',
    PRODUCTS: 'vendor.products',
    ORDERS: 'vendor.orders',
    PAYMENTS: 'vendor.payments',
    REVIEWS: 'vendor.reviews',
  },

  SUPPORT: {
    FAQS: 'support.faqs',
    CONTACT: 'support.contact',
    CHAT: 'support.chat',
    TICKET: 'support.ticket',
  },

  // add warehousing 
  WAREHOUSING: {
    INDEX: 'warehousing.index',
    PRODUCTS: 'warehousing.products',
    STOCK: 'warehousing.stock',
    // ORDERS: 'warehousing.orders',
    // SUPPLIERS: 'warehousing.suppliers',
  },

}

export const useNavStore = defineStore('nav', {
  state: () => ({
    expandedGroups: [],
    navItems: [
      {
        title: 'Dashboard',
        icon: 'mdi-view-dashboard',
        route: ROUTES.DASHBOARD.INDEX,
        roles: ['Admin', 'Manager', 'CallAgent', 'Vendor', 'Delivery Agent'],
        plans: ['Free', 'Pro', 'Enterprise'],
        features: ['dashboard'],
        permissions: ['dashboard_view', 'dashboard_export'],
        children: [
          { title: 'Overview', route: ROUTES.DASHBOARD.INDEX, icon: 'mdi-view-dashboard-outline', permission: 'dashboard_view' },
          { title: 'Metrics', route: ROUTES.DASHBOARD.METRICS, icon: 'mdi-chart-line', permission: 'dashboard_view' },
        ],
      },

      {
        title: 'Call Center',
        icon: 'mdi-headset',
        route: ROUTES.CALL_CENTRE.INDEX,
        roles: ['Admin', 'Manager', 'CallAgent', 'Vendor', 'Delivery Agent'],
        plans: ['Free', 'Pro', 'Enterprise'],
        features: ['call-center'],
        permissions: ['call_center_view'],
        children: [
          { title: 'Call Centre', route: ROUTES.CALL_CENTRE.INDEX, icon: 'mdi-headset', permission: 'call_center_view' },
          { title: 'Tickets', route: ROUTES.CALL_CENTRE.TICKETS, icon: 'mdi-ticket-outline', permission: 'tickets_view' },
          { title: 'Messages', route: ROUTES.CALL_CENTRE.MESSAGES, icon: 'mdi-message-text-outline', permission: 'messages_view' },
          { title: 'WhatsApp', route: ROUTES.CALL_CENTRE.WHATSAPP, icon: 'mdi-whatsapp', permission: 'whatsapp_view' },
          { title: 'Emails', route: ROUTES.CALL_CENTRE.EMAILS, icon: 'mdi-email-outline', permission: 'emails_view' },
          { title: 'Clients', route: ROUTES.CALL_CENTRE.CLIENTS, icon: 'mdi-account-multiple-outline', permission: 'clients_view' },
          { title: 'Contacts', route: ROUTES.CALL_CENTRE.CONTACTS, icon: 'mdi-phone-outline', permission: 'contacts_view' },
          { title: 'Telegram', route: ROUTES.CALL_CENTRE.TELEGRAM, icon: 'mdi-telegram', permission: 'telegram_view' },
          { title: 'Notes', route: ROUTES.CALL_CENTRE.NOTES, icon: 'mdi-note-text-outline', permission: 'notes_view' },
        ],
      },

      {
        title: 'Orders',
        icon: 'mdi-cart',
        route: ROUTES.ORDERS.INDEX,
        roles: ['Admin', 'Manager', 'CallAgent', 'Vendor', 'Delivery Agent'],
        plans: ['Free', 'Pro', 'Enterprise'],
        features: ['orders'],
        permissions: ['orders_view', 'orders_create', 'orders_edit', 'orders_delete', 'orders_ship', 'orders_dispatch', 'orders_scan', 'orders_track', 'orders_update_status', 'orders_refund'],
        children: [
          { title: 'All Orders', route: ROUTES.ORDERS.INDEX, icon: 'mdi-cart-outline', permission: 'orders_view' },
          { title: 'Shipping', route: ROUTES.ORDERS.SHIPPING, icon: 'mdi-truck-outline', permission: 'orders_ship' },
          { title: 'Scan & Dispatch', route: ROUTES.ORDERS.SCAN_DISPATCH, icon: 'mdi-barcode-scan', permission: 'orders_scan' },
          { title: 'Dispatch List', route: ROUTES.ORDERS.DISPATCH_LIST, icon: 'mdi-format-list-bulleted', permission: 'orders_dispatch' },
        ],
      },

      {
        title: 'Users & Teams',
        icon: 'mdi-account-group',
        route: ROUTES.USERS.INDEX,
        roles: ['Admin', 'Manager', 'CallAgent', 'Vendor', 'Delivery Agent'],
        plans: ['Pro', 'Enterprise'],
        features: ['team-management'],
        permissions: ['users_view', 'users_create', 'users_edit', 'users_delete', 'users_impersonate', 'roles_view', 'roles_create', 'roles_edit', 'roles_delete', 'roles_assign', 'permissions_view', 'permissions_create', 'permissions_edit', 'permissions_delete'],
        children: [
          { title: 'User Management', route: ROUTES.USERS.INDEX, icon: 'mdi-account-group-outline', permission: 'users_view' },
          { title: 'User Roles', route: ROUTES.USERS.ROLES, icon: 'mdi-account-key-outline', permission: 'roles_view' },
          { title: 'Permissions', route: ROUTES.USERS.PERMISSIONS, icon: 'mdi-shield-key-outline', permission: 'permissions_view' },
        ],
      },

      {
        title: 'Reports',
        icon: 'mdi-chart-box',
        route: ROUTES.REPORTS.INDEX,
        roles: ['Admin', 'Manager', 'CallAgent', 'Vendor', 'Delivery Agent'],
        plans: ['Pro', 'Enterprise'],
        features: ['reports'],
        permissions: ['reports_view', 'reports_create', 'reports_export', 'reports_schedule', 'reports_share'],
        children: [
          { title: 'View Reports', route: ROUTES.REPORTS.INDEX, icon: 'mdi-chart-box-outline', permission: 'reports_view' },
        ],
      },

      {
        title: 'Branches',
        icon: 'mdi-map-marker-outline',
        route: ROUTES.BRANCHES.INDEX,
        roles: ['Admin', 'Manager', 'CallAgent', 'Vendor', 'Delivery Agent'],
        plans: ['Pro', 'Enterprise'],
        features: ['branch-management'],
        permissions: ['branches_view', 'branches_create', 'branches_edit', 'branches_delete', 'cities_manage', 'zones_manage'],
        children: [
          { title: 'View Branches', route: ROUTES.BRANCHES.INDEX, icon: 'mdi-map-marker-radius-outline', permission: 'branches_view' },
          { title: 'Cities', route: ROUTES.BRANCHES.CITIES, icon: 'mdi-city', permission: 'cities_manage' },
          { title: 'Zones', route: ROUTES.BRANCHES.ZONES, icon: 'mdi-shape-outline', permission: 'zones_manage' },
        ],
      },

      {
        title: 'Warehousing',
        icon: 'mdi-warehouse',
        route: 'warehouse.index',
        roles: ['Admin', 'Manager', 'CallAgent', 'Vendor', 'Delivery Agent'],
        plans: ['Pro', 'Enterprise'],
        features: ['warehousing'],
        permissions: ['warehouse_view', 'warehouse_manage', 'products_view', 'products_create', 'products_edit', 'products_delete', 'inventory_view', 'inventory_adjust', 'inventory_transfer', 'categories_manage'],
        children: [
          { title: 'Warehouse', route: ROUTES.WAREHOUSE.INDEX, icon: 'mdi-warehouse', permission: 'warehouse_view' },
          { title: 'Products', route: ROUTES.WAREHOUSE.PRODUCTS, icon: 'mdi-package-variant-closed', permission: 'products_view' },
          { title: 'Inventory', route: ROUTES.WAREHOUSE.INVENTORY, icon: 'mdi-warehouse', permission: 'inventory_view' },
          { title: 'Statistics', route: ROUTES.WAREHOUSE.STATISTICS, icon: 'mdi-chart-bar', permission: 'warehouse_view' },
          { title: 'Category', route: ROUTES.WAREHOUSE.CATEGORY, icon: 'mdi-tag-multiple', permission: 'categories_manage' },
        ],
      },

      {
        title: 'Settings',
        icon: 'mdi-cog',
        route: ROUTES.SETTINGS.PROFILE,
        roles: ['Admin', 'Manager', 'CallAgent', 'Vendor', 'Delivery Agent'],
        plans: ['Free', 'Pro', 'Enterprise'],
        features: [],
        permissions: ['settings_view', 'settings_edit', 'ivr_manage', 'integrations_view', 'integrations_manage', 'templates_view', 'templates_create', 'templates_edit', 'templates_delete', 'call_settings_manage', 'system_status_view', 'system_status_manage'],
        children: [
          { title: 'IVR Options', route: ROUTES.SETTINGS.IVR, icon: 'mdi-phone-settings', permission: 'ivr_manage' },
          { title: 'Integrations', route: ROUTES.SETTINGS.INTEGRATIONS, icon: 'mdi-puzzle', permission: 'integrations_view' },
          { title: 'Templates', route: ROUTES.SETTINGS.TEMPLATES, icon: 'mdi-file-outline', permission: 'templates_view' },
          { title: 'Call Settings', route: ROUTES.SETTINGS.CALL_SETTINGS, icon: 'mdi-phone-outline', permission: 'call_settings_manage' },
          { title: 'System Statuses', route: ROUTES.SETTINGS.STATUS, icon: 'mdi-server', permission: 'system_status_view' },
        ],
      },

      {
        title: 'Integrations',
        icon: 'mdi-api',
        route: ROUTES.INTEGRATIONS.API,
        roles: ['Admin', 'Manager', 'CallAgent', 'Vendor', 'Delivery Agent'],
        plans: ['Pro', 'Enterprise'],
        features: ['integrations'],
        permissions: ['integrations_view', 'integrations_manage', 'api_manage', 'webhooks_manage'],
        children: [
          { title: 'Marketplace', route: ROUTES.INTEGRATIONS.MARKETPLACE, icon: 'mdi-storefront-outline', permission: 'integrations_view' },
          { title: 'Shopify', route: ROUTES.INTEGRATIONS.SHOPIFY, icon: 'mdi-api', permission: 'api_manage' },
        ],
      },

      {
        title: 'Billing',
        icon: 'mdi-credit-card',
        route: ROUTES.BILLING.PLANS,
        roles: ['Admin', 'Manager', 'CallAgent', 'Vendor', 'Delivery Agent'],
        plans: ['Pro', 'Enterprise'],
        features: ['billing'],
        permissions: ['billing_view', 'billing_manage', 'invoices_view', 'invoices_create', 'payments_view', 'payments_process'],
        children: [
          { title: 'Plans & Pricing', route: ROUTES.BILLING.PLANS, icon: 'mdi-card-account-details', permission: 'billing_view' },
          { title: 'Payment Methods', route: ROUTES.BILLING.PAYMENTS, icon: 'mdi-credit-card-outline', permission: 'payments_view' },
          { title: 'Invoices', route: ROUTES.BILLING.INVOICES, icon: 'mdi-receipt', permission: 'invoices_view' },
          { title: 'Usage Metering', route: ROUTES.BILLING.USAGE, icon: 'mdi-gauge', permission: 'billing_view' },
          { title: 'License Management', route: ROUTES.BILLING.LICENSES, icon: 'mdi-license', permission: 'billing_manage' },
        ],
      },

      {
        title: 'Notifications',
        icon: 'mdi-bell',
        route: ROUTES.NOTIFICATIONS.CENTER,
        roles: ['Admin', 'Manager', 'CallAgent', 'Vendor', 'Delivery Agent'],
        plans: ['Pro', 'Enterprise'],
        features: ['notifications'],
        permissions: ['notifications_view', 'notifications_create', 'notifications_manage'],
        children: [
          { title: 'Notification Center', route: ROUTES.NOTIFICATIONS.CENTER, icon: 'mdi-bell-outline', permission: 'notifications_view' },
          { title: 'Activity Trail', route: ROUTES.NOTIFICATIONS.ACTIVITY, icon: 'mdi-timeline', permission: 'notifications_view' },
          { title: 'Read/Unread', route: ROUTES.NOTIFICATIONS.STATUS, icon: 'mdi-bell-check', permission: 'notifications_view' },
        ],
      },

      {
        title: 'Audit Logs',
        icon: 'mdi-clipboard-text-clock',
        route: ROUTES.AUDIT.LOGINS,
        roles: ['Admin', 'Manager', 'CallAgent', 'Vendor', 'Delivery Agent'],
        plans: ['Enterprise'],
        features: ['audit-trail'],
        permissions: ['audit_logs_view', 'login_history_view', 'system_changes_view', 'security_settings_manage'],
        children: [
          { title: 'Login History', route: ROUTES.AUDIT.LOGINS, icon: 'mdi-account-clock', permission: 'login_history_view' },
          { title: 'User Actions', route: ROUTES.AUDIT.ACTIONS, icon: 'mdi-account-edit', permission: 'audit_logs_view' },
          { title: 'System Changes', route: ROUTES.AUDIT.SYSTEM, icon: 'mdi-cog-clockwise', permission: 'system_changes_view' },
        ],
      },

      {
        title: 'Developer Tools',
        icon: 'mdi-code-tags',
        route: ROUTES.DEVELOPER.DOCS,
        roles: ['Admin', 'Manager', 'CallAgent', 'Vendor', 'Delivery Agent'],
        plans: ['Enterprise'],
        features: ['developer-tools'],
        permissions: ['developer_tools_access', 'api_manage', 'webhooks_manage', 'sandbox_access', 'feature_flags_manage'],
        children: [
          { title: 'API Docs', route: ROUTES.DEVELOPER.DOCS, icon: 'mdi-book-open-variant', permission: 'developer_tools_access' },
          { title: 'SDKs', route: ROUTES.DEVELOPER.SDKS, icon: 'mdi-package-variant', permission: 'developer_tools_access' },
          { title: 'Sandbox Mode', route: ROUTES.DEVELOPER.SANDBOX, icon: 'mdi-play-box-outline', permission: 'sandbox_access' },
          { title: 'Usage Stats', route: ROUTES.DEVELOPER.STATS, icon: 'mdi-chart-line-variant', permission: 'developer_tools_access' },
        ],
      },

      // {
      //   title: 'Vendor',
      //   icon: 'mdi-store',
      //   route: ROUTES.VENDOR.INDEX,
      //   roles: ['Admin', 'Manager', 'CallAgent', 'Vendor', 'Delivery Agent'],
      //   plans: ['Pro', 'Enterprise'],
      //   features: ['vendor-management'],
      //   permissions: ['vendors_view', 'vendors_create', 'vendors_edit', 'vendors_delete', 'vendors_approve', 'vendors_manage', 'vendor_profile_view', 'vendor_profile_edit'],
      //   children: [
      //     { title: 'Vendor Dashboard', route: ROUTES.VENDOR.INDEX, icon: 'mdi-store-outline', permission: 'vendors_view' },
      //     { title: 'Products', route: ROUTES.VENDOR.PRODUCTS, icon: 'mdi-package-variant-closed', permission: 'products_view' },
      //     { title: 'Orders', route: ROUTES.VENDOR.ORDERS, icon: 'mdi-cart-outline', permission: 'orders_view' },
      //   ],
      // },

      {
        title: 'Help & Support',
        icon: 'mdi-lifebuoy',
        route: ROUTES.SUPPORT.FAQS,
        roles: ['Admin', 'Manager', 'CallAgent', 'Vendor', 'Delivery Agent'],
        plans: ['Free', 'Pro', 'Enterprise'],
        features: [],
        children: [
          { title: 'FAQs / Knowledge Base', route: ROUTES.SUPPORT.FAQS, icon: 'mdi-help-circle-outline' },
          { title: 'Contact Support', route: ROUTES.SUPPORT.CONTACT, icon: 'mdi-email-outline' },
          { title: 'Live Chat', route: ROUTES.SUPPORT.CHAT, icon: 'mdi-chat-outline' },
          { title: 'Submit a Ticket', route: ROUTES.SUPPORT.TICKET, icon: 'mdi-ticket-plus-outline' },
        ],
      },
    ],
  }),

  getters: {
    filteredNavItems: (state) => {
      return (userRoles, userPlan, enabledFeatures = [], userPermissions = []) => {
        // Normalize everything to lowercase strings
        const roles = Array.isArray(userRoles)
          ? userRoles.map(r => String(r || '').toLowerCase())
          : [String(userRoles || '').toLowerCase()];

        const plan = String(userPlan || '').toLowerCase();
        const permissions = [...(Array.isArray(userPermissions) ? userPermissions : [])].map(p => p.toLowerCase());
        const features = enabledFeatures.map(f => f.toLowerCase());

        return state.navItems
          .map(item => {
            const itemRoles = (item.roles || []).map(r => r.toLowerCase());
            const itemPlans = (item.plans || []).map(p => p.toLowerCase());
            const itemFeatures = (item.features || []).map(f => f.toLowerCase());

            // Support both `permission` and `permissions`
            const itemPermissions = [];
            if (item.permission) itemPermissions.push(item.permission.toLowerCase());
            if (Array.isArray(item.permissions)) {
              itemPermissions.push(...item.permissions.map(p => p.toLowerCase()));
            }

            // Check if user has at least one of the required roles
            const hasRole = itemRoles.length === 0 ||
              roles.some(userRole => itemRoles.includes(userRole));

            const hasPlan = itemPlans.length === 0 || itemPlans.includes(plan);
            const hasFeatures = itemFeatures.length === 0 ||
              itemFeatures.every(f => features.includes(f));
            // const hasPermissions = itemPermissions.length === 0 || 
            //   itemPermissions.every(p => permissions.includes(p));

            const hasPermissions = itemPermissions.length === 0 ||
              itemPermissions.some(p => permissions.includes(p));

            if (!(hasRole && hasPlan && hasFeatures && hasPermissions)) return null;

            // Filter children
            let children = [];
            if (item.children) {
              children = item.children.filter(child => {
                const childPermission = child.permission?.toLowerCase();
                const childRole = child.role?.toLowerCase();

                const okPermission = !childPermission || permissions.includes(childPermission);
                const okRole = !childRole || roles.includes(childRole);
                return okPermission && okRole;
              });
            }

            return { ...item, children };
          })
          .filter(Boolean);
      };
    },
  },

  actions: {
    // Navigation drawer functions
    toggleGroup(groupName) {
      const index = this.expandedGroups.indexOf(groupName)
      if (index === -1) {
        this.expandedGroups.push(groupName)
      } else {
        this.expandedGroups.splice(index, 1)
      }
    },

    isGroupExpanded(groupName) {
      return this.expandedGroups.includes(groupName)
    },

    // Expand all groups
    expandAllGroups() {
      this.expandedGroups = this.navItems.map(item => item.title)
    },

    // Collapse all groups
    collapseAllGroups() {
      this.expandedGroups = []
    },

    // Find nav item by route
    findNavItemByRoute(route) {
      for (const item of this.navItems) {
        if (item.route === route) return item
        if (item.children) {
          const child = item.children.find(child => child.route === route)
          if (child) return { parent: item, child }
        }
      }
      return null
    },

    // Get breadcrumb for current route
    getBreadcrumb(route) {
      const navItem = this.findNavItemByRoute(route)
      if (!navItem) return []

      if (navItem.parent) {
        return [
          { title: navItem.parent.title, route: navItem.parent.route },
          { title: navItem.child.title, route: navItem.child.route }
        ]
      }

      return [{ title: navItem.title, route: navItem.route }]
    }
  }
})
