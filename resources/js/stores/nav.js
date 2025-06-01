import { defineStore } from 'pinia'

export const useNavStore = defineStore('nav', {
  state: () => ({
    navItems: [
      {
        title: 'Dashboard',
        icon: 'mdi-view-dashboard',
        route: 'dashboard',
        roles: ['admin', 'staff', 'user'],
        plans: ['Free', 'Pro', 'Enterprise'],
        features: [],
        children: [
          { title: 'Metrics & KPIs', route: '/dashboard/metrics' },
          { title: 'Activity Logs', route: '/dashboard/activity' },
          { title: 'System Status', route: '/dashboard/status' },
        ],
      },

      {
        title: 'Settings',
        icon: 'mdi-cog',
        route: 'settings',
        roles: ['admin'],
        plans: ['Free', 'Pro', 'Enterprise'],
        features: [],
        children: [
          { title: 'Profile & Account', route: '/settings/profile' },
          { title: 'Organization', route: '/settings/org' },
          { title: 'Feature Toggles', route: '/settings/features' },
          { title: 'Notifications', route: '/settings/notifications' },
          { title: 'Branding', route: '/settings/branding' },
        ],
      },

      {
        title: 'Reports',
        icon: 'mdi-chart-box',
        route: 'reports',
        roles: ['admin', 'staff'],
        plans: ['Pro', 'Enterprise'],
        features: ['reports'],
        children: [
          { title: 'Generate Report', route: '/reports/generate' },
          { title: 'Export', route: '/reports/export' },
          { title: 'Schedule Reports', route: '/reports/schedule' },
        ],
      },

      {
        title: 'Integrations',
        icon: 'mdi-api',
        route: 'integrations',
        roles: ['admin'],
        plans: ['Pro', 'Enterprise'],
        features: ['integrations'],
        children: [
          { title: 'Webhooks', route: '/integrations/webhooks' },
          { title: 'API Keys', route: '/integrations/api' },
          { title: 'Third-Party Apps', route: '/integrations/apps' },
        ],
      },

      {
        title: 'Users & Teams',
        icon: 'mdi-account-group',
        route: 'admin.users',
        roles: ['admin'],
        plans: ['Pro', 'Enterprise'],
        features: ['team-management'],
        children: [
          { title: 'User Management', route: '/users/manage' },
          { title: 'Teams', route: '/users/teams' },
          { title: 'Roles & Permissions', route: '/users/roles' },
          { title: 'Invitations', route: '/users/invitations' },
        ],
      },

      {
        title: 'Billing',
        icon: 'mdi-credit-card',
        route: 'billing',
        roles: ['admin'],
        plans: ['Pro', 'Enterprise'],
        features: ['billing'],
        children: [
          { title: 'Plans & Pricing', route: '/billing/plans' },
          { title: 'Payment Methods', route: '/billing/payments' },
          { title: 'Invoices', route: '/billing/invoices' },
          { title: 'Usage Metering', route: '/billing/usage' },
          { title: 'License Management', route: '/billing/licenses' },
        ],
      },

      {
        title: 'Notifications ',
        icon: 'mdi-bell',
        route: 'notifications',
        roles: ['admin', 'staff'],
        plans: ['Pro', 'Enterprise'],
        features: ['notifications'],
        children: [
          { title: 'Notification Center', route: '/notifications/center' },
          { title: 'Activity Trail', route: '/notifications/activity' },
          { title: 'Read/Unread', route: '/notifications/status' },
        ],
      },

      {
        title: 'Help & Support',
        icon: 'mdi-lifebuoy',
        route: 'support',
        roles: ['admin', 'staff', 'user'],
        plans: ['Free', 'Pro', 'Enterprise'],
        features: [],
        children: [
          { title: 'FAQs / Knowledge Base', route: '/support/faqs' },
          { title: 'Contact Support', route: '/support/contact' },
          { title: 'Live Chat', route: '/support/chat' },
          { title: 'Submit a Ticket', route: '/support/ticket' },
        ],
      },

      {
        title: 'Audit Logs',
        icon: 'mdi-clipboard-text-clock',
        route: 'audit',
        roles: ['admin'],
        plans: ['Enterprise'],
        features: ['audit-trail'],
        children: [
          { title: 'Login History', route: '/audit/logins' },
          { title: 'User Actions', route: '/audit/actions' },
          { title: 'System Changes', route: '/audit/system' },
        ],
      },

      {
        title: 'Developer Tools',
        icon: 'mdi-code-tags',
        route: 'developer',
        roles: ['admin'],
        plans: ['Enterprise'],
        features: ['developer-tools'],
        children: [
          { title: 'API Docs', route: '/developer/docs' },
          { title: 'SDKs', route: '/developer/sdks' },
          { title: 'Sandbox Mode', route: '/developer/sandbox' },
          { title: 'Usage Stats', route: '/developer/stats' },
        ],
      },
    ],
  }),

  getters: {
    filteredNavItems: (state) => {
      return (userRole, userPlan, enabledFeatures = []) => {
        return state.navItems.filter(item => {
          const hasRole = item.roles.includes(userRole)
          const hasPlan = item.plans.includes(userPlan)
          const hasFeatures = item.features.every(f => enabledFeatures.includes(f))
          return hasRole && hasPlan && hasFeatures
        })
      }
    },
  },
})
