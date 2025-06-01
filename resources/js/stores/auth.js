// stores/auth.js
import { defineStore } from 'pinia'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export const useAuthStore = defineStore('auth', () => {
  const page = usePage()

  const user = computed(() => page.props.auth?.user || null)
  const roles = computed(() => page.props.auth?.roles || [])
  const permissions = computed(() => page.props.auth?.permissions || [])

  const hasRole = (role) => roles.value.includes(role)
  const hasPermission = (permission) => permissions.value.includes(permission)

  return {
    user,
    roles,
    permissions,
    hasRole,
    hasPermission,
  }
})
