
// composables/useRoleBasedNavigation.js
import { computed } from 'vue'
import { usePermissions } from './usePermissions'

export function useRoleBasedNavigation() {
    const { hasPermission, hasRole, isAdmin, isVendor, isDeliveryAgent } = usePermissions()
    
    const navigationItems = computed(() => {
        const items = []
        
        // Dashboard - available to all authenticated users
        items.push({
            name: 'Dashboard',
            href: '/dashboard',
            icon: 'mdi-view-dashboard',
            permission: 'dashboard_view'
        })
        
        // Call Center - not for vendors or delivery agents
        if (hasPermission('call_center_view')) {
            items.push({
                name: 'Call Center',
                href: '/call-center',
                icon: 'mdi-headset',
                permission: 'call_center_view'
            })
        }
        
        // Orders - different views based on role
        if (hasPermission('orders_view') || hasPermission('orders_view_own') || hasPermission('orders_view_assigned')) {
            items.push({
                name: 'Orders',
                href: '/orders',
                icon: 'mdi-cart-outline',
                permission: 'orders_view'
            })
        }
        
        // Products - different access for vendors
        if (hasPermission('products_view') || hasPermission('products_view_own')) {
            items.push({
                name: 'Products',
                href: '/products',
                icon: 'mdi-package-variant',
                permission: 'products_view'
            })
        }
        
        // Customers - not for vendors
        if (hasPermission('clients_view') && !isVendor.value) {
            items.push({
                name: 'Customers',
                href: '/customers',
                icon: 'mdi-account-multiple',
                permission: 'clients_view'
            })
        }
        
        // Reports - scoped for vendors
        if (hasPermission('reports_view') || hasPermission('reports_view_own')) {
            items.push({
                name: 'Reports',
                href: '/reports',
                icon: 'mdi-chart-box',
                permission: 'reports_view'
            })
        }
        
        // Admin only sections
        if (isAdmin.value) {
            items.push(
                {
                    name: 'User Management',
                    href: '/users',
                    icon: 'mdi-account-group',
                    permission: 'users_view'
                },
                {
                    name: 'Vendors',
                    href: '/vendors',
                    icon: 'mdi-storefront',
                    permission: 'vendors_view'
                },
                {
                    name: 'Settings',
                    href: '/settings',
                    icon: 'mdi-cog',
                    permission: 'settings_view'
                }
            )
        }
        
        return items.filter(item => 
            !item.permission || hasPermission(item.permission)
        )
    })
    
    return {
        navigationItems
    }
}