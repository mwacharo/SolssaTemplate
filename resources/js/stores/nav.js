import { defineStore } from 'pinia'

// Route Registry for consistency
export const ROUTES = {
  DASHBOARD: {
    INDEX: 'dashboard',
    // METRICS: 'dashboard.metrics',
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
    SHIP: 'orders.ship',
  },
  // shipping orders 
  // scan and dispatch 
  // dispatchlist &ship orders 

  USERS: {
    // INDEX: 'users.index',
      INDEX: 'admin.users', // 👈 This should match Laravel's ->name('admin.users')

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
    CaLL_SETTINGS: 'settings.call-settings',
    // FEATURES: 'settings.features',
    // NOTIFICATIONS: 'settings.notifications',
    // BRANDING: 'settings.branding',
  },
  INTEGRATIONS: {
    // API: 'integrations.api',
    // WEBHOOKS: 'integrations.webhooks',
    // APPS: 'integrations.apps',
    MARKETPLACE: 'integrations.marketplace',
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
        roles: ['admin', 'staff', 'user'],
        plans: ['Free', 'Pro', 'Enterprise'],
        features: [],
        children: [
          { title: 'Overview', route: ROUTES.DASHBOARD.INDEX, icon: 'mdi-view-dashboard-outline' },
          // { title: 'Metrics & KPIs', route: ROUTES.DASHBOARD.METRICS, icon: 'mdi-chart-line' },
          // { title: 'Activity Logs', route: ROUTES.DASHBOARD.ACTIVITY, icon: 'mdi-history' },
          // { title: 'System Status', route: ROUTES.DASHBOARD.STATUS, icon: 'mdi-server' },
        ],
      },

      {
        title: 'Call Center',
        icon: 'mdi-headset',
        route: ROUTES.CALL_CENTRE.INDEX,
        roles: ['admin', 'staff', 'user'],
        plans: ['Free', 'Pro', 'Enterprise'],
        features: ['call-center'],
        children: [
          { title: 'Call Centre', route: ROUTES.CALL_CENTRE.INDEX, icon: 'mdi-headset' },
          { title: 'Tickets', route: ROUTES.CALL_CENTRE.TICKETS, icon: 'mdi-ticket-outline' },
          { title: 'Messages', route: ROUTES.CALL_CENTRE.MESSAGES, icon: 'mdi-message-text-outline' },
          { title: 'WhatsApp', route: ROUTES.CALL_CENTRE.WHATSAPP, icon: 'mdi-whatsapp' },
          { title: 'Emails', route: ROUTES.CALL_CENTRE.EMAILS, icon: 'mdi-email-outline' },
          { title: 'Clients', route: ROUTES.CALL_CENTRE.CLIENTS, icon: 'mdi-account-multiple-outline' },
          { title: 'Contacts', route: ROUTES.CALL_CENTRE.CONTACTS, icon: 'mdi-phone-outline' },
          { title: 'Telegram', route: ROUTES.CALL_CENTRE.TELEGRAM, icon: 'mdi-telegram' },
          { title: 'Notes', route: ROUTES.CALL_CENTRE.NOTES, icon: 'mdi-note-text-outline' },
        ],
      },

      {
        title: 'Users & Teams',
        icon: 'mdi-account-group',
        route: ROUTES.USERS.INDEX,
        roles: ['admin'],
        plans: ['Pro', 'Enterprise'],
        features: ['team-management'],
        children: [
          { title: 'User Management', route: ROUTES.USERS.INDEX, icon: 'mdi-account-group-outline' },
          { title: 'User Roles', route: ROUTES.USERS.ROLES, icon: 'mdi-account-key-outline' },
          // { title: 'Permissions', route: ROUTES.USERS.PERMISSIONS, icon: 'mdi-shield-key-outline' },
          // { title: 'Teams', route: ROUTES.USERS.TEAMS, icon: 'mdi-account-multiple' },
          // { title: 'Invitations', route: ROUTES.USERS.INVITATIONS, icon: 'mdi-email-send' },
        ],
      },

      {
        title: 'Reports',
        icon: 'mdi-chart-box',
        route: ROUTES.REPORTS.INDEX,
        roles: ['admin', 'staff'],
        plans: ['Pro', 'Enterprise'],
        features: ['reports'],
        children: [
          { title: 'View Reports', route: ROUTES.REPORTS.INDEX, icon: 'mdi-chart-box-outline' },
          // { title: 'Generate Report', route: ROUTES.REPORTS.GENERATE, icon: 'mdi-file-chart' },
          // { title: 'Export', route: ROUTES.REPORTS.EXPORT, icon: 'mdi-download' },
          // { title: 'Schedule Reports', route: ROUTES.REPORTS.SCHEDULE, icon: 'mdi-calendar-clock' },
        ],
      },

      {
        title: 'Branches',
        icon: 'mdi-map-marker-outline',
        route: ROUTES.BRANCHES.INDEX,
        roles: ['admin', 'staff'],
        plans: ['Pro', 'Enterprise'],
        features: ['branch-management'],
        permissions: ['view branches'],
        children: [
          { title: 'View Branches', route: ROUTES.BRANCHES.INDEX, icon: 'mdi-map-marker-radius-outline' },
          // { title: 'Add Branch', route: ROUTES.BRANCHES.CREATE, icon: 'mdi-map-marker-plus' },
          // { title: 'Manage Locations', route: ROUTES.BRANCHES.LOCATIONS, icon: 'mdi-map' },
        ],
      },

      // warehousing
      {
        title: 'Warehousing',
        icon: 'mdi-warehouse',
        route: 'warehouse.index', // Laravel route name matches: warehouse.index
        roles: ['admin', 'staff'],
        plans: ['Pro', 'Enterprise'],
        features: ['warehousing'],  
        children: [
          { title: 'Warehouse', route: ROUTES.WAREHOUSE.INDEX, icon: 'mdi-warehouse' },
          // { title: 'Products', route: ROUTES.WAREHOUSING.PRODUCTS, icon: 'mdi-package-variant-closed' },
          // { title: 'Stock Management', route: ROUTES.WAREHOUSING.STOCK, icon: 'mdi-warehouse' },
          // { title: 'Orders', route: ROUTES.WAREHOUSING.ORDERS, icon: 'mdi-cart-outline' },
          //{ title: 'Suppliers', route: ROUTES.WAREHOUSING.SUPPLIERS, icon: 'mdi-account-multiple-outline' },
          { title: 'Products', route: ROUTES.WAREHOUSE.PRODUCTS, icon: 'mdi-package-variant-closed' },
          { title: 'Inventory', route: ROUTES.WAREHOUSE.INVENTORY, icon: 'mdi-warehouse' },
          { title: 'Statistics', route: ROUTES.WAREHOUSE.STATISTICS, icon: 'mdi-chart-bar' },
          { title: 'Category', route: ROUTES.WAREHOUSE.CATEGORY, icon: 'mdi-tag-multiple' },
        ],
      },

      {
        title: 'Settings',
        icon: 'mdi-cog',
        route: ROUTES.SETTINGS.PROFILE,
        roles: ['admin'],
        plans: ['Free', 'Pro', 'Enterprise'],
        features: [],
        children: [
          // { title: 'Profile & Account', route: ROUTES.SETTINGS.PROFILE, icon: 'mdi-account-cog' },
          // { title: 'Organization', route: ROUTES.SETTINGS.ORG, icon: 'mdi-office-building' },
          { title: 'IVR Options', route: ROUTES.SETTINGS.IVR, icon: 'mdi-phone-settings' },
          { title: 'Integrations', route: ROUTES.SETTINGS.INTEGRATIONS, icon: 'mdi-puzzle' },
          { title: 'Templates', route: ROUTES.SETTINGS.TEMPLATES, icon: 'mdi-file-outline' },
          { title: 'Call Settings', route: ROUTES.SETTINGS.CaLL_SETTINGS, icon: 'mdi-phone-outline' },
          // { title: 'Feature Toggles', route: ROUTES.SETTINGS.FEATURES, icon: 'mdi-toggle-switch' },
          // { title: 'Notifications', route: ROUTES.SETTINGS.NOTIFICATIONS, icon: 'mdi-bell-cog' },
          // { title: 'Branding', route: ROUTES.SETTINGS.BRANDING, icon: 'mdi-palette' },
        ],
      },

      {
        title: 'Integrations',
        icon: 'mdi-api',
        route: ROUTES.INTEGRATIONS.API,
        roles: ['admin'],
        plans: ['Pro', 'Enterprise'],
        features: ['integrations'],
        children: [
          // { title: 'API Management', route: ROUTES.INTEGRATIONS.API, icon: 'mdi-api' },
          // { title: 'Webhooks', route: ROUTES.INTEGRATIONS.WEBHOOKS, icon: 'mdi-webhook' },
          // { title: 'Third-Party Apps', route: ROUTES.INTEGRATIONS.APPS, icon: 'mdi-application' },
          { title: 'Marketplace', route: ROUTES.INTEGRATIONS.MARKETPLACE, icon: 'mdi-storefront-outline' },
        ],
      },

      {
        title: 'Billing',
        icon: 'mdi-credit-card',
        route: ROUTES.BILLING.PLANS,
        roles: ['admin'],
        plans: ['Pro', 'Enterprise'],
        features: ['billing'],
        children: [
          { title: 'Plans & Pricing', route: ROUTES.BILLING.PLANS, icon: 'mdi-card-account-details' },
          { title: 'Payment Methods', route: ROUTES.BILLING.PAYMENTS, icon: 'mdi-credit-card-outline' },
          { title: 'Invoices', route: ROUTES.BILLING.INVOICES, icon: 'mdi-receipt' },
          { title: 'Usage Metering', route: ROUTES.BILLING.USAGE, icon: 'mdi-gauge' },
          { title: 'License Management', route: ROUTES.BILLING.LICENSES, icon: 'mdi-license' },
        ],
      },

      {
        title: 'Notifications',
        icon: 'mdi-bell',
        route: ROUTES.NOTIFICATIONS.CENTER,
        roles: ['admin', 'staff'],
        plans: ['Pro', 'Enterprise'],
        features: ['notifications'],
        children: [
          { title: 'Notification Center', route: ROUTES.NOTIFICATIONS.CENTER, icon: 'mdi-bell-outline' },
          { title: 'Activity Trail', route: ROUTES.NOTIFICATIONS.ACTIVITY, icon: 'mdi-timeline' },
          { title: 'Read/Unread', route: ROUTES.NOTIFICATIONS.STATUS, icon: 'mdi-bell-check' },
        ],
      },

      {
        title: 'Audit Logs',
        icon: 'mdi-clipboard-text-clock',
        route: ROUTES.AUDIT.LOGINS,
        roles: ['admin'],
        plans: ['Enterprise'],
        features: ['audit-trail'],
        children: [
          { title: 'Login History', route: ROUTES.AUDIT.LOGINS, icon: 'mdi-account-clock' },
          { title: 'User Actions', route: ROUTES.AUDIT.ACTIONS, icon: 'mdi-account-edit' },
          { title: 'System Changes', route: ROUTES.AUDIT.SYSTEM, icon: 'mdi-cog-clockwise' },
        ],
      },

      {
        title: 'Developer Tools',
        icon: 'mdi-code-tags',
        route: ROUTES.DEVELOPER.DOCS,
        roles: ['admin'],
        plans: ['Enterprise'],
        features: ['developer-tools'],
        children: [
          { title: 'API Docs', route: ROUTES.DEVELOPER.DOCS, icon: 'mdi-book-open-variant' },
          { title: 'SDKs', route: ROUTES.DEVELOPER.SDKS, icon: 'mdi-package-variant' },
          { title: 'Sandbox Mode', route: ROUTES.DEVELOPER.SANDBOX, icon: 'mdi-play-box-outline' },
          { title: 'Usage Stats', route: ROUTES.DEVELOPER.STATS, icon: 'mdi-chart-line-variant' },
        ],
      },


       {
        title: 'Vendor',
        icon: 'mdi-store',
        route: ROUTES.VENDOR.INDEX,
        roles: ['admin', 'staff'],
        plans: ['Pro', 'Enterprise'],
        features: ['vendor-management'],
        children: [
          { title: 'Vendor Dashboard', route: ROUTES.VENDOR.INDEX, icon: 'mdi-store-outline' },
          { title: 'Products', route: ROUTES.VENDOR.PRODUCTS, icon: 'mdi-package-variant-closed' },
          { title: 'Orders', route: ROUTES.VENDOR.ORDERS, icon: 'mdi-cart-outline' },
          // { title: 'Payments', route: ROUTES.VENDOR.PAYMENTS, icon: 'mdi-cash-multiple' },
          // { title: 'Reviews', route: ROUTES.VENDOR.REVIEWS, icon: 'mdi-star-outline' },
        ],
      },

      {
        title: 'Help & Support',
        icon: 'mdi-lifebuoy',
        route: ROUTES.SUPPORT.FAQS,
        roles: ['admin', 'staff', 'user'],
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
      return (userRole, userPlan, enabledFeatures = [], userPermissions = []) => {
        return state.navItems.filter(item => {
          const hasRole = item.roles.includes(userRole)
          const hasPlan = item.plans.includes(userPlan)
          const hasFeatures = item.features.length === 0 || item.features.every(f => enabledFeatures.includes(f))
          const hasPermissions = !item.permissions || item.permissions.every(p => userPermissions.includes(p))
          
          return hasRole && hasPlan && hasFeatures && hasPermissions
        })
      }
    },

    // Get filtered children for a specific nav item
    getFilteredChildren: (state) => {
      return (parentItem, userRole, userPlan, enabledFeatures = [], userPermissions = []) => {
        if (!parentItem.children) return []
        
        return parentItem.children.filter(child => {
          const hasPermission = !child.permission || userPermissions.includes(child.permission)
          const hasRole = !child.role || child.role === userRole
          return hasPermission && hasRole
        })
      }
    },

    // Get navigation items grouped by category
    getGroupedNavItems: (state) => {
      return (userRole, userPlan, enabledFeatures = [], userPermissions = []) => {
        const filtered = state.filteredNavItems(userRole, userPlan, enabledFeatures, userPermissions)
        
        return {
          core: filtered.filter(item => ['Dashboard', 'Call Center'].includes(item.title)),
          management: filtered.filter(item => ['Users & Teams', 'Branches', 'Reports'].includes(item.title)),
          configuration: filtered.filter(item => ['Settings', 'Integrations'].includes(item.title)),
          business: filtered.filter(item => ['Billing', 'Notifications'].includes(item.title)),
          security: filtered.filter(item => ['Audit Logs'].includes(item.title)),
          development: filtered.filter(item => ['Developer Tools'].includes(item.title)),
          support: filtered.filter(item => ['Help & Support'].includes(item.title)),
          vendor: filtered.filter(item => ['Vendor'].includes(item.title)),
        }
      }
    }
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
