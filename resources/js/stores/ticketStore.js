// stores/ticketStore.js
import { defineStore, storeToRefs } from 'pinia'
import { ref, computed } from 'vue'

export const useTicketStore = defineStore('tickets', () => {
  // State
  const tickets = ref([
    {
      id: 1,
      ticketNumber: 'TK-2025-001',
      clientName: 'John Doe',
      clientPhone: '+1234567890',
      clientEmail: 'john@example.com',
      subject: 'Payment Processing Issue',
      description: 'Unable to process payment through the online portal',
      priority: 'high',
      status: 'open',
      category: 'billing',
      assignedTo: 'Agent Smith',
      createdAt: new Date('2025-07-19T10:30:00'),
      updatedAt: new Date('2025-07-19T10:30:00'),
      callDuration: '5:32',
      resolution: null,
      notes: []
    },
    {
      id: 2,
      ticketNumber: 'TK-2025-002',
      clientName: 'Jane Smith',
      clientPhone: '+1234567891',
      clientEmail: 'jane@example.com',
      subject: 'Technical Support Request',
      description: 'Website not loading properly on mobile device',
      priority: 'medium',
      status: 'in-progress',
      category: 'technical',
      assignedTo: 'Agent Jones',
      createdAt: new Date('2025-07-19T09:15:00'),
      updatedAt: new Date('2025-07-19T14:20:00'),
      callDuration: '8:45',
      resolution: null,
      notes: [
        {
          id: 1,
          content: 'Initial troubleshooting completed',
          createdBy: 'Agent Jones',
          createdAt: new Date('2025-07-19T14:20:00')
        }
      ]
    }
  ])

  const currentFilter = ref('all')
  const searchQuery = ref('')

  // Getters/Computed
  const filteredTickets = computed(() => {
    let filtered = tickets.value

    // Filter by status
    if (currentFilter.value !== 'all') {
      filtered = filtered.filter(ticket => ticket.status === currentFilter.value)
    }

    // Search functionality
    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase()
      filtered = filtered.filter(ticket => 
        ticket.clientName.toLowerCase().includes(query) ||
        ticket.subject.toLowerCase().includes(query) ||
        ticket.ticketNumber.toLowerCase().includes(query) ||
        ticket.clientPhone.includes(query)
      )
    }

    return filtered.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
  })

  const ticketStats = computed(() => ({
    total: tickets.value.length,
    open: tickets.value.filter(t => t.status === 'open').length,
    inProgress: tickets.value.filter(t => t.status === 'in-progress').length,
    resolved: tickets.value.filter(t => t.status === 'resolved').length,
    closed: tickets.value.filter(t => t.status === 'closed').length
  }))

  const priorityStats = computed(() => ({
    high: tickets.value.filter(t => t.priority === 'high').length,
    medium: tickets.value.filter(t => t.priority === 'medium').length,
    low: tickets.value.filter(t => t.priority === 'low').length
  }))

  // Actions
  const addTicket = (ticketData) => {
    const newTicket = {
      id: Date.now(),
      ticketNumber: generateTicketNumber(),
      ...ticketData,
      status: 'open',
      createdAt: new Date(),
      updatedAt: new Date(),
      notes: []
    }
    tickets.value.unshift(newTicket)
    return newTicket
  }

  const updateTicket = (ticketId, updates) => {
    const index = tickets.value.findIndex(t => t.id === ticketId)
    if (index !== -1) {
      tickets.value[index] = {
        ...tickets.value[index],
        ...updates,
        updatedAt: new Date()
      }
    }
  }

  const addNote = (ticketId, noteContent, createdBy) => {
    const ticket = tickets.value.find(t => t.id === ticketId)
    if (ticket) {
      const note = {
        id: Date.now(),
        content: noteContent,
        createdBy: createdBy,
        createdAt: new Date()
      }
      ticket.notes.push(note)
      ticket.updatedAt = new Date()
    }
  }

  const deleteTicket = (ticketId) => {
    const index = tickets.value.findIndex(t => t.id === ticketId)
    if (index !== -1) {
      tickets.value.splice(index, 1)
    }
  }

  const setFilter = (filter) => {
    currentFilter.value = filter
  }

  const setSearch = (query) => {
    searchQuery.value = query
  }

  const generateTicketNumber = () => {
    const year = new Date().getFullYear()
    const count = tickets.value.length + 1
    return `TK-${year}-${count.toString().padStart(3, '0')}`
  }

  const getTicketById = (id) => {
    return tickets.value.find(ticket => ticket.id === parseInt(id))
  }

  return {
    // State
    tickets,
    currentFilter,
    searchQuery,
    
    // Getters
    filteredTickets,
    ticketStats,
    priorityStats,
    
    // Actions
    addTicket,
    updateTicket,
    addNote,
    deleteTicket,
    setFilter,
    setSearch,
    getTicketById
  }
})