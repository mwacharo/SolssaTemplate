import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'
import { notify } from '@/utils/toast'

export const useFulfillmentHubStore = defineStore('fulfillmentHub', () => {

  const hubs    = ref({ data: [] })
  const loading = ref(false)
  const error   = ref(null)

  // ── Fetch all ──────────────────────────────────────────────────────────────
  async function fetchHubs() {
    loading.value = true
    error.value   = null
    try {
      const { data } = await axios.get('/api/v1/fulfillment-hubs')
      hubs.value = data
    } catch (e) {
      error.value = e
      notify.error('Failed to load fulfillment hubs.')
      throw e
    } finally {
      loading.value = false
    }
  }

  // ── Create ─────────────────────────────────────────────────────────────────
  async function addHub(payload) {
    loading.value = true
    try {
      const { data } = await axios.post('/api/v1/fulfillment-hubs', payload)
      hubs.value.data.unshift(data.data ?? data)
      notify.success('Fulfillment hub created.')
      return data
    } catch (e) {
      error.value = e
      notify.error('Failed to create hub.')
      throw e
    } finally {
      loading.value = false
    }
  }

  // ── Update ─────────────────────────────────────────────────────────────────
  async function updateHub(id, payload) {
    loading.value = true
    try {
      const { data } = await axios.put(`/api/v1/fulfillment-hubs/${id}`, payload)
      const updated = data.data ?? data
      const idx = hubs.value.data.findIndex(h => h.id === id)
      if (idx !== -1) hubs.value.data.splice(idx, 1, updated)
      notify.success('Hub updated.')
      return updated
    } catch (e) {
      error.value = e
      notify.error('Failed to update hub.')
      throw e
    } finally {
      loading.value = false
    }
  }

  // ── Delete ─────────────────────────────────────────────────────────────────
  async function deleteHub(id) {
    loading.value = true
    try {
      await axios.delete(`/api/v1/fulfillment-hubs/${id}`)
      hubs.value.data = hubs.value.data.filter(h => h.id !== id)
      notify.success('Hub deleted.')
    } catch (e) {
      error.value = e
      notify.error('Failed to delete hub.')
      throw e
    } finally {
      loading.value = false
    }
  }

  // ── Toggle active status ───────────────────────────────────────────────────
  async function toggleStatus(id) {
    loading.value = true
    try {
      const hub     = hubs.value.data.find(h => h.id === id)
      const payload = { active: hub?.active ? 0 : 1 }
      const { data } = await axios.put(`/api/v1/fulfillment-hubs/${id}`, payload)
      const updated  = data.data ?? data
      const idx      = hubs.value.data.findIndex(h => h.id === id)
      if (idx !== -1) hubs.value.data.splice(idx, 1, updated)
      notify.success(`Hub ${updated.active ? 'activated' : 'deactivated'}.`)
    } catch (e) {
      error.value = e
      notify.error('Failed to toggle hub status.')
      throw e
    } finally {
      loading.value = false
    }
  }

  return {
    hubs,
    loading,
    error,
    fetchHubs,
    addHub,
    updateHub,
    deleteHub,
    toggleStatus,
  }
})