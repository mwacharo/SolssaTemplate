// composables/usePermissions.js
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function usePermissions() {
    const page = usePage()
    
    const user = computed(() => page.props.auth?.user)
    const userPermissions = computed(() => user.value?.permissions || [])
    const userRoles = computed(() => user.value?.roles || [])
    
    /**
     * Check if user has specific permission
     */
    const hasPermission = (permission) => {
        return userPermissions.value.includes(permission)
    }
    
    /**
     * Check if user has any of the specified permissions
     */
    const hasAnyPermission = (permissions) => {
        return permissions.some(permission => hasPermission(permission))
    }
    
    /**
     * Check if user has all specified permissions
     */
    const hasAllPermissions = (permissions) => {
        return permissions.every(permission => hasPermission(permission))
    }
    
    /**
     * Check if user has specific role
     */
    const hasRole = (role) => {
        return userRoles.value.some(userRole => userRole.name === role)
    }
    
    /**
     * Check if user has any of the specified roles
     */
    const hasAnyRole = (roles) => {
        return roles.some(role => hasRole(role))
    }
    
    /**
     * Check if user can perform action on own data
     */
    const canViewOwn = (resource) => {
        return hasPermission(`${resource}_view_own`) || hasPermission(`${resource}_view`)
    }
    
    /**
     * Check if user can edit own data
     */
    const canEditOwn = (resource) => {
        return hasPermission(`${resource}_edit_own`) || hasPermission(`${resource}_edit`)
    }
    
    /**
     * Check if user can create own data
     */
    const canCreateOwn = (resource) => {
        return hasPermission(`${resource}_create_own`) || hasPermission(`${resource}_create`)
    }
    
    /**
     * Check if user can delete own data
     */
    const canDeleteOwn = (resource) => {
        return hasPermission(`${resource}_delete_own`) || hasPermission(`${resource}_delete`)
    }
    
    /**
     * Check if user can view assigned data (for delivery agents)
     */
    const canViewAssigned = (resource) => {
        return hasPermission(`${resource}_view_assigned`) || hasPermission(`${resource}_view`)
    }
    
    /**
     * Get permission object for a resource
     */
    const getResourcePermissions = (resource) => {
        return {
            canView: hasPermission(`${resource}_view`) || canViewOwn(resource),
            canViewOwn: canViewOwn(resource),
            canViewAssigned: canViewAssigned(resource),
            canCreate: hasPermission(`${resource}_create`) || canCreateOwn(resource),
            canCreateOwn: canCreateOwn(resource),
            canEdit: hasPermission(`${resource}_edit`) || canEditOwn(resource),
            canEditOwn: canEditOwn(resource),
            canDelete: hasPermission(`${resource}_delete`) || canDeleteOwn(resource),
            canDeleteOwn: canDeleteOwn(resource),
        }
    }
    
    /**
     * Check if user is admin
     */
    const isAdmin = computed(() => hasRole('Admin'))
    
    /**
     * Check if user is vendor
     */
    const isVendor = computed(() => hasRole('Vendor'))
    
    /**
     * Check if user is delivery agent
     */
    const isDeliveryAgent = computed(() => hasRole('DeliveryAgent'))
    
    /**
     * Check if user is call agent
     */
    const isCallAgent = computed(() => hasRole('CallAgent'))
    
    /**
     * Check if user is manager
     */
    const isManager = computed(() => hasRole('Manager'))
    
    return {
        // Core permission functions
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,
        hasRole,
        hasAnyRole,
        
        // Scoped permission functions
        canViewOwn,
        canEditOwn,
        canCreateOwn,
        canDeleteOwn,
        canViewAssigned,
        
        // Resource-specific permissions
        getResourcePermissions,
        
        // Role checks
        isAdmin,
        isVendor,
        isDeliveryAgent,
        isCallAgent,
        isManager,
        
        // Computed properties
        user,
        userPermissions,
        userRoles,
    }
}

