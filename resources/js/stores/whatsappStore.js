// stores/whatsappStore.js
import { defineStore } from 'pinia'
import axios from 'axios'
import { comma } from 'postcss/lib/list'

export const useWhatsAppStore = defineStore('whatsapp', {
  state: () => ({
    // conversation 
    dialog: false,
    // loading: false,
    conversation: [],
    replyMessage: '',
    showEmojiPicker: false,
    attachment: {
      image: null,
      audio: null,
    },
    // Core data
    messages: [],
    contacts: [],
    orders: [],
    templates: [],
    riders: [],
    agents: [],

    // Selected items
    selectedRiders: [],
    selectedAgents: [],
    selectedContacts: [],
    selectedOrders: [],
    selectedTemplate: null,
    messageText: '',

    // Pagination
    currentPage: 1,
    perPage: 20,
    totalMessages: 0,
    totalOrders: 0,

    // Loading states
    loading: {
            conversation: false,  // ✅ Add this for conversation dialog

      messages: false,
      contacts: false,
      orders: false,
      templates: false,
      sending: false,
      importing: false,
      savingTemplate: false,
      deletingTemplate: false,
      uploadingOrders: false
    },

    // UI states
    showImportDialog: false,
    showNewMessageDialog: false,
    showTemplateDialog: false,
    showOrderImportDialog: false,
    showOrderMessageDialog: false,
    activeTab: 'messages',

    // Filters
    search: '',
    filterType: 'all',
    filterStatus: 'all',
    orderFilters: {
      status: 'all',
      product: '',
      agent: '',
      zone: '',
      createdDateStart: null,
      createdDateEnd: null,
      deliveryDateStart: null,
      deliveryDateEnd: null,
      vendorRecallStart: null,
      vendorRecallEnd: null
    },

    // Messages
    errorMessage: '',
    successMessage: '',

    // Statistics
    stats: {
      sent: 0,
      delivered: 0,
      read: 0,
      failed: 0,
      pending: 0,
      totalOrders: 0,
      pendingOrders: 0,
      deliveredOrders: 0
    },

    // Connection status
    whatsappStatus: 'Connected',

    // File uploads
    csvFile: null,
    orderFile: null,

    // Predefined templates
    orderTemplates: [
      // {
      //   name: 'Order Confirmation',
      //   content: 'Hi {{customer_name}}, your order #{{order_number}} for {{product_name}} worth ${{price}} has been confirmed. We will notify you once shipped. Thank you for choosing our courier service!'
      // },
      // {
      //   name: 'Shipping Notification',
      //   content: 'Hello {{customer_name}}, great news! Your order #{{order_number}} is now shipped and on its way to you. Track your package with tracking ID: {{tracking_id}}'
      // },
      // {
      //   name: 'Delivery Confirmation',
      //   content: 'Hi {{customer_name}}, your order #{{order_number}} has been successfully delivered. Thank you for your business! Please rate our service.'
      // },
      // {
      //   name: 'Payment Reminder',
      //   content: 'Dear {{customer_name}}, this is a reminder that payment for order #{{order_number}} worth ${{price}} is still pending. Please complete payment to avoid delays.'
      // }
    ]
  }),

  getters: {

    // conversation getters

    hasAttachment: (state) => !!(state.attachment.image || state.attachment.audio),

    filteredContacts: (state) => {
      if (!Array.isArray(state.contacts)) return []

      let filtered = [...state.contacts]

      if (state.filterType !== 'all') {
        filtered = filtered.filter(contact => contact.type === state.filterType)
      }

      if (state.filterStatus !== 'all') {
        filtered = filtered.filter(contact => contact.status === state.filterStatus)
      }

      return filtered
    },

    filteredOrders: (state) => {
      if (!Array.isArray(state.orders)) return []

      let filtered = [...state.orders]

      // Status filter
      if (state.orderFilters.status !== 'all') {
        filtered = filtered.filter(order => order.status === state.orderFilters.status)
      }

      // Product filter
      if (state.orderFilters.product) {
        filtered = filtered.filter(order =>
          order.product_name?.toLowerCase().includes(state.orderFilters.product.toLowerCase())
        )
      }

      // Agent filter
      if (state.orderFilters.agent) {
        filtered = filtered.filter(order =>
          order.agent_name?.toLowerCase().includes(state.orderFilters.agent.toLowerCase())
        )
      }

      // Zone filter
      if (state.orderFilters.zone) {
        filtered = filtered.filter(order =>
          order.zone?.toLowerCase().includes(state.orderFilters.zone.toLowerCase())
        )
      }

      // Created date range filter
      if (state.orderFilters.createdDateStart) {
        filtered = filtered.filter(order => {
          const orderDate = new Date(order.created_at)
          const startDate = new Date(state.orderFilters.createdDateStart)
          return orderDate >= startDate
        })
      }

      if (state.orderFilters.createdDateEnd) {
        filtered = filtered.filter(order => {
          const orderDate = new Date(order.created_at)
          const endDate = new Date(state.orderFilters.createdDateEnd)
          return orderDate <= endDate
        })
      }

      // Delivery date range filter
      if (state.orderFilters.deliveryDateStart && state.orderFilters.deliveryDateEnd) {
        filtered = filtered.filter(order => {
          if (!order.delivery_date) return false
          const deliveryDate = new Date(order.delivery_date)
          const startDate = new Date(state.orderFilters.deliveryDateStart)
          const endDate = new Date(state.orderFilters.deliveryDateEnd)
          return deliveryDate >= startDate && deliveryDate <= endDate
        })
      }

      // Vendor recall date range filter
      if (state.orderFilters.vendorRecallStart && state.orderFilters.vendorRecallEnd) {
        filtered = filtered.filter(order => {
          if (!order.vendor_recall_date) return false
          const recallDate = new Date(order.vendor_recall_date)
          const startDate = new Date(state.orderFilters.vendorRecallStart)
          const endDate = new Date(state.orderFilters.vendorRecallEnd)
          return recallDate >= startDate && recallDate <= endDate
        })
      }

      return filtered
    },

    filteredMessages: (state) => {
      if (!state.search || !Array.isArray(state.messages)) return state.messages

      const searchTerm = state.search.toLowerCase()
      return state.messages.filter(message =>
        (message.content?.toLowerCase().includes(searchTerm)) ||
        (message.status?.toLowerCase().includes(searchTerm)) ||
        (message.recipient_name?.toLowerCase().includes(searchTerm)) ||
        (message.recipient_phone?.toLowerCase().includes(searchTerm)) ||
        (message.order_number?.toLowerCase().includes(searchTerm))
      )
    },

    validWhatsappContacts: (state) => {
      if (!Array.isArray(state.contacts)) return []
      return state.contacts.filter(contact => contact.whatsapp || contact.alt_phone || contact.phone)
    },

    totalPages: (state) => Math.ceil(state.totalMessages / state.perPage),
    totalOrderPages: (state) => Math.ceil(state.totalOrders / state.perPage),

    allTemplates: (state) => [...state.templates, ...state.orderTemplates]
  },

  actions: {
    // conversation actions


    setDialog(value) {
      this.dialog = value
    },

    appendEmoji(emoji) {
      this.replyMessage += emoji
    },

    setAttachmentImage(file) {
      this.attachment.image = file
    },

    setAttachmentAudio(file) {
      this.attachment.audio = file
    },

    resetAttachment() {
      this.attachment.image = null
      this.attachment.audio = null
    },

    toggleEmojiPicker() {
      this.showEmojiPicker = !this.showEmojiPicker
    },


    // Utility actions
    showError(message) {
      this.errorMessage = message
      setTimeout(() => {
        this.errorMessage = ''
      }, 5000)
    },

    showSuccess(message) {
      this.successMessage = message
      setTimeout(() => {
        this.successMessage = ''
      }, 5000)
    },

    formatPhoneNumber(phone) {
      if (!phone) return 'Unknown'
      return phone.replace(/@c\.us$/, '')
    },

    // Data loading actions
    async loadMessages(page = 1) {
      try {
        this.loading.messages = true
        this.currentPage = page

        const response = await axios.get(`/api/v1/whatsapp-messages`, {
          params: {
            page: page,
            per_page: this.perPage
          }
        })

        if (Array.isArray(response.data.data)) {
          this.messages = response.data.data
          this.totalMessages = response.data.meta?.total || this.messages.length
          this.calculateStats()
        } else {
          console.error('Unexpected API response format:', response.data)
          this.messages = []
          this.showError('Invalid data format received from server')
        }
      } catch (error) {
        console.error('Error loading messages:', error)
        this.showError(`Failed to load messages: ${error.response?.data?.message || error.message}`)
        this.messages = []
      } finally {
        this.loading.messages = false
      }
    },

    async loadContacts() {
      try {
        this.loading.contacts = true
        const response = await axios.get('/api/v1/contacts')

        if (response.data?.data?.data && Array.isArray(response.data.data.data)) {
          this.contacts = response.data.data.data
          console.log('Contacts loaded:', this.contacts.length)
        } else {
          console.error('Unexpected API response format:', response.data)
          this.contacts = []
          this.showError('Invalid contact data format received from server')
        }
      } catch (error) {
        console.error('Error loading contacts:', error)
        this.showError(`Failed to load contacts: ${error.response?.data?.message || error.message}`)
        this.contacts = []
      } finally {
        this.loading.contacts = false
      }
    },

    // async loadOrders(page = 1) {
    //   try {
    //     this.loading.orders = true
    //     const response = await axios.get('/api/v1/orders', {
    //       params: {
    //         page: page,
    //         per_page: this.perPage
    //       }
    //     })

    //     if (Array.isArray(response.data.data)) {
    //       this.orders = response.data.data
    //       this.totalOrders = response.data.meta?.total || this.orders.length
    //       this.calculateOrderStats()
    //     } else {
    //       console.error('Unexpected API response format:', response.data)
    //       this.orders = []
    //       // this.showError('Invalid order data format received from server')
    //     }
    //   } catch (error) {
    //     console.error('Error loading orders:', error)
    //     this.showError(`Failed to load orders: ${error.response?.data?.message || error.message}`)
    //     this.orders = []
    //   } finally {
    //     this.loading.orders = false
    //   }
    // },

    async loadTemplates() {
      try {
        this.loading.templates = true
        const response = await axios.get('/api/v1/templates')

        if (response.data?.data && Array.isArray(response.data.data)) {
          this.templates = response.data.data
          console.log('Templates loaded:', this.templates.length)
        } else {
          console.error('Unexpected API response format:', response.data)
          this.templates = []
          this.showError('Failed to load custom templates, using default order templates')
        }
      } catch (error) {
        console.error('Error loading templates:', error)
        this.templates = []
        this.showError('Failed to load custom templates, using default order templates')
      } finally {
        this.loading.templates = false
      }
    },


    async loadRiders() {
      try {
        this.loading.riders = true
        const response = await axios.get('/api/v1/riders')  // Adjust the endpoint as needed    
        if (response.data?.data && Array.isArray(response.data.data)) {
          this.riders = response.data.data
          console.log('Riders loaded:', this.riders.length)
        } else {
          console.error('Unexpected API response format:', response.data)
          this.riders = []
          this.showError('Failed to load riders')
        }
      } catch (error) {
        console.error('Error loading riders:', error)
        this.riders = []
        this.showError(`Failed to load riders: ${error.response?.data?.message || error.message}`)
      } finally {
        this.loading.riders = false
      }
    },

    async loadAgents() {
      try {
        this.loading.agents = true
        const response = await axios.get('/api/v1/agents') // Adjust the endpoint as needed
        if (response.data?.data && Array.isArray(response.data.data)) {
          this.agents = response.data.data.map(agent => ({
            id: agent.id,
            name: agent.name,
            phone: agent.phone,
            email: agent.email,
            ...agent
          }))
          console.log('Agents loaded:', this.agents.length)
        } else {
          console.error('Unexpected API response format:', response.data)
          this.agents = []
          this.showError('Failed to load agents')
        }
      } catch (error) {
        console.error('Error loading agents:', error)
        this.agents = []
        this.showError(`Failed to load agents: ${error.response?.data?.message || error.message}`)
      } finally {
        this.loading.agents = false
      }
    },

    // Statistics calculation
    calculateStats() {
      if (!Array.isArray(this.messages)) {
        console.error('messages is not an array:', this.messages)
        return
      }

      try {
        const statuses = this.messages.reduce((acc, message) => {
          const status = (message && message.status && typeof message.status === 'string')
            ? message.status.toLowerCase()
            : 'unknown'
          acc[status] = (acc[status] || 0) + 1
          return acc
        }, {})

        this.stats = {
          ...this.stats,
          sent: statuses.sent || 0,
          delivered: statuses.delivered || 0,
          read: statuses.read || 0,
          failed: statuses.failed || 0,
          pending: statuses.pending || 0
        }
      } catch (error) {
        console.error('Error calculating message stats:', error)
      }
    },

    calculateOrderStats() {
      if (!Array.isArray(this.orders)) return

      try {
        const orderStats = this.orders.reduce((acc, order) => {
          const status = order.status?.toLowerCase() || 'unknown'
          acc[status] = (acc[status] || 0) + 1
          return acc
        }, {})

        this.stats = {
          ...this.stats,
          totalOrders: this.orders.length,
          pendingOrders: orderStats.pending || 0,
          deliveredOrders: orderStats.delivered || 0
        }
      } catch (error) {
        console.error('Error calculating order stats:', error)
      }
    },

    // Updated onTemplateSelect method in your Pinia store
    onTemplateSelect(template) {
      console.log('Template selected:', template); // Debug log

      if (!template || !template.content) {
        console.log('No template or content found');
        return;
      }

      this.selectedTemplate = template;

      // Use first selected contact and order for placeholder replacement
      const contact = this.selectedContacts?.[0] || {};
      const order = this.selectedOrders?.[0] || {};

      // Build placeholders object for template parsing
      const placeholders = {
        customer_name: contact.name || order.customer_name || 'Customer',
        customer_phone: contact.phone || order.customer_phone || '',
        order_number: order.order_number || '',
        product_name: order.product_name || '',
        price: order.price || '',
        tracking_id: order.tracking_id || ''
      };

      // Parse template and update message text
      this.messageText = this.parseTemplate(template.content, placeholders);

      console.log('Parsed messageText:', this.messageText); // Debug log
    },

    // parseTemplate(template, data = {}) {
    //   return Object.entries(data).reduce((result, [key, value]) => {
    //     const regex = new RegExp(`{{\\s*${key}\\s*}}`, 'g')
    //     return result.replace(regex, value ?? '')
    //   }, template)
    // },

    parseTemplate(template, data = {}) {
      return Object.entries(data).reduce((result, [key, value]) => {
        const regex = new RegExp(`{{\\s*${key}\\s*}}`, 'g')
        return result.replace(regex, String(value ?? ''))
      }, template)
    }
    ,



    // ,    parseTemplate(template, data = {}) {
    //       return Object.entries(data).reduce((result, [key, value]) => {
    //         const regex = new RegExp(`{{\\s*${key}\\s*}}`, 'g')
    //         return result.replace(regex, value ?? '')
    //       }, template)
    //     }
    //     ,


    // Filter actions
    resetFilters() {
      this.search = ''
      this.filterType = 'all'
      this.filterStatus = 'all'
      this.orderFilters = {
        status: 'all',
        product: '',
        agent: '',
        zone: '',
        createdDateStart: null,
        createdDateEnd: null,
        deliveryDateStart: null,
        deliveryDateEnd: null,
        vendorRecallStart: null,
        vendorRecallEnd: null
      }
    },


    // async sendMessage(userId) {
    //   console.log('Sending message with userId:', userId)
    //   if (!this.messageText.trim()) {
    //     return this.showError('Please enter a message')
    //   }
    //   if (!Array.isArray(this.selectedContacts) || this.selectedContacts.length === 0) {
    //     return this.showError('Please select at least one recipient')
    //   }
    //   try {
    //     this.loading.sending = true

    //     const templateId = this.selectedTemplate?.id || null

    //     // Send personalized messages
    //     const results = []
    //     for (const contact of this.selectedContacts) {
    //       // Debug: log selectedOrders and orders
    //       console.log('selectedOrders:', this.selectedOrders)
    //       console.log('orders:', this.orders)

    //       // Ensure selectedOrders is an array of IDs
    //       const selectedOrderIds = this.selectedOrders.map(o => typeof o === 'object' ? o.id : o)
    //       const order = this.orders.find(o => {
    //         if (!o) return false
    //         const match = selectedOrderIds.includes(o.id) && (
    //           o.customer_phone?.replace(/\D/g, '') === contact.phone?.replace(/\D/g, '') ||
    //           o.client?.phone_number?.replace(/\D/g, '') === contact.phone?.replace(/\D/g, '') ||
    //           o.client?.id === contact.id
    //         )
    //         return match
    //       })

    //       if (!order) {
    //         console.warn(`No matching order found for contact:`, contact)
    //         // Optionally skip sending for this contact:
    //         // continue
    //       }

    //       let orderItemsList = ''
    //       if (order && Array.isArray(order.orderItems) && order.orderItems.length > 0) {
    //         orderItemsList = order.orderItems.map(item =>
    //           `- ${item.quantity} × ${item.product?.product_name || ''}`
    //         ).join('\n')
    //       }

    //       const placeholders = {
    //         customer_name: contact.name || order?.customer_name || 'Customer',
    //         customer_phone: contact.chatId || '',
    //         order_number: order?.order_no || order?.order_number || '',
    //         tracking_id: order?.tracking_no || '',
    //         client: order?.client || null,
    //         order_items: orderItemsList,
    //         agent_name: order?.agent?.name || '',
    //         agent_phone: order?.agent?.phone || '',
    //         vendor_name: order?.vendor?.name || '',
    //         rider_name: order?.rider?.name || '',
    //         rider_phone: order?.rider?.phone || '',
    //         total_price: order?.total_price || '0.00'
    //       };

    //       console.log('Placeholders', placeholders)
    //       const personalizedMessage = this.parseTemplate(this.selectedTemplate?.content || this.messageText, placeholders)
    //       const payload = {
    //         user_id: userId,
    //         contacts: [{
    //           id: contact.id,
    //           name: contact.name,
    //           chatId: contact.whatsapp || contact.alt_phone || contact.phone,
    //         }],
    //         message: personalizedMessage,
    //         template_id: templateId,
    //         order: order ? {
    //           id: order.id,
    //           order_no: order.order_no,
    //           orderItem: order.orderItems,
    //           total_price: order.total_price,
    //         } : null,
    //         rider: order?.rider || null,
    //         agent: order?.agent || null,
    //         vendor: order?.vendor || null,
    //       }

    //       console.log('Sending payload:', payload)
    //       const response = await axios.post('/api/v1/whatsapp/send-message', payload)

    //       results.push({
    //         contact: contact.name,
    //         phone: contact.phone,
    //         status: 'sent',
    //         result: response.data?.results || []
    //       })
    //     }

    //     this.showSuccess(`Messages sent to ${results.length} recipients`)
    //     this.messageText = ''
    //     this.selectedContacts = []
    //     this.selectedOrders = []
    //     this.selectedTemplate = null
    //     this.showNewMessageDialog = false

    //     setTimeout(() => {
    //       this.loadMessages(1)
    //     }, 1000)

    //   } catch (error) {
    //     console.error('Error sending personalized messages:', error)
    //     this.showError(`Failed: ${error.response?.data?.message || error.message}`)
    //   } finally {
    //     this.loading.sending = false
    //   }
    // }
    // ,


    async sendMessage(userId) {
    console.log('🚀 Sending message, userId:', userId);
    
    if (!this.messageText.trim()) {
              console.log('Message text is empty');

        return this.showError('Please enter a message');
    }
    
    if (!this.selectedContacts?.length) {
              console.log('No contacts selected');

        return this.showError('Please select at least one recipient');
    }

    try {
        this.loading.sending = true;
        const results = [];

        for (const contact of this.selectedContacts) {
            console.log('📧 Processing:', contact.name);

            // ⭐ Find order using orderId (most reliable)
            let order = null;
            
            if (contact.orderId) {
                order = this.orders.find(o => o.id === contact.orderId);
                console.log('✅ Found by orderId:', order?.order_no);
            } else if (contact.orderData) {
                order = contact.orderData;
                console.log('✅ Using orderData:', order?.order_no);
            }

            if (!order) {
                console.warn('⚠️ No order for:', contact.name);
            }

            // Build order items text
            let orderItemsList = '';
            if (order?.order_items?.length > 0) {
                orderItemsList = order.order_items.map(item => {
                    const name = item.name || 
                                 item.product?.product_name || 
                                 item.sku || 
                                 'Item';
                    return `- ${item.quantity} × ${name}`;
                }).join('\n');
            }

            // Extract agent and rider from assignments
            const callAgent = order?.assignments?.find(
                a => a.role === 'CallAgent'
            );
            const deliveryAgent = order?.assignments?.find(
                a => a.role === 'Delivery Agent'
            );

            // Build template placeholders
            const placeholders = {
                customer_name: contact.name || order?.customer?.full_name || 'Customer',
                customer_phone: contact.phone || '',
                order_number: order?.order_no || '',
                order_no: order?.order_no || '',
                tracking_id: order?.tracking_no || order?.tracking_number || '',
                order_items: orderItemsList,
                total_price: order?.total_price || '0.00',
                delivery_date: order?.delivery_date || '',
                
                // Agent info
                agent_name: callAgent?.user?.name || '',
                agent_phone: callAgent?.user?.phone_number || '',
                
                // Rider info  
                rider_name: deliveryAgent?.user?.name || '',
                rider_phone: deliveryAgent?.user?.phone_number || '',
                
                // Vendor info
                vendor_name: order?.vendor?.name || '',
                
                // Status
                status: order?.latest_status?.status?.name || '',
            };

            console.log('📋 Placeholders:', placeholders);

            // Parse template
            const personalizedMessage = this.parseTemplate(
                this.selectedTemplate?.content || this.messageText,
                placeholders
            );

            // Build payload for backend
            const payload = {
                user_id: userId,
                template_id: this.selectedTemplate?.id || null,
                template_slug: this.selectedTemplate?.slug || 'general',
                contacts: [{
                    id: contact.id,
                    name: contact.name,
                    phone: contact.phone,
                    chatId: contact.whatsapp || contact.phone,
                    
                    // ⭐ CRITICAL: Include order IDs
                    orderId: contact.orderId || order?.id || null,
                    orderOid: contact.orderOid || order?.order_no || null,
                }],
                message: personalizedMessage,
                
                // Optional: Include full order data
                order: order ? {
                    id: order.id,
                    order_no: order.order_no,
                    order_items: order.order_items,
                    total_price: order.total_price,
                    customer: order.customer,
                } : null,
            };

            console.log('📤 Payload:', payload);

            const response = await axios.post(
                '/api/v1/whatsapp/send-message', 
                payload
            );

            results.push({
                contact: contact.name,
                phone: contact.phone,
                orderId: order?.id,
                status: 'sent',
                result: response.data,
            });
        }

        this.showSuccess(`Messages sent to ${results.length} recipients`);
        
        // Reset
        this.messageText = '';
        this.selectedContacts = [];
        this.selectedOrders = [];
        this.selectedTemplate = null;
        this.showNewMessageDialog = false;

        setTimeout(() => this.loadMessages(1), 1000);

    } catch (error) {
        console.error('❌ Error:', error);
        this.showError(
            error.response?.data?.message || error.message
        );
    } finally {
        this.loading.sending = false;
    }
},


    // Order messaging
    async sendOrderMessage(userId) {
      if (!this.messageText.trim()) {
        return this.showError('Please enter a message')
      }

      if (!Array.isArray(this.selectedOrders) || this.selectedOrders.length === 0) {
        return this.showError('Please select at least one order')
      }

      try {
        this.loading.sending = true

        const response = await axios.post('/api/v1/whatsapp-send-orders', {
          user_id: userId,
          orders: this.selectedOrders.map(order => ({
            id: order.id,
            order_number: order.order_number,
            customer_name: order.customer_name,
            customer_phone: order.customer_phone,
            product_name: order.product_name,
            price: order.price,
            tracking_id: order.tracking_id
          })),
          message: this.messageText,
          template_id: this.selectedTemplate?.id || null,
        })

        this.showSuccess(`Order messages sent to ${this.selectedOrders.length} customers`)

        this.messageText = ''
        this.selectedOrders = []
        this.selectedTemplate = null
        this.showOrderMessageDialog = false

        setTimeout(() => {
          this.loadMessages(1)
          // this.loadOrders(1)
        }, 1000)

      } catch (error) {
        console.error('Error sending order messages:', error)
        this.showError(`Failed to send order messages: ${error.response?.data?.message || error.message}`)
      } finally {
        this.loading.sending = false
      }
    },

    // File import
    async importContacts(userId) {
      if (!this.csvFile) {
        return this.showError('Please select a CSV file to import')
      }

      try {
        this.loading.importing = true

        const formData = new FormData()
        formData.append('file', this.csvFile)
        formData.append('user_id', userId)

        const response = await axios.post('/api/v1/contacts/import', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })

        if (response.data && response.data.success) {
          this.showSuccess(`Successfully imported ${response.data.imported || 'multiple'} contacts`)
          await this.loadContacts()
          this.showImportDialog = false
          this.csvFile = null
        } else {
          this.showError(response.data?.message || 'Import failed')
        }
      } catch (error) {
        console.error('Error importing contacts:', error)
        this.showError(`Failed to import contacts: ${error.response?.data?.message || error.message}`)
      } finally {
        this.loading.importing = false
      }
    },

    async importOrders(userId) {
      if (!this.orderFile) {
        return this.showError('Please select an Excel or CSV file to import')
      }

      try {
        this.loading.uploadingOrders = true

        const formData = new FormData()
        formData.append('file', this.orderFile)
        formData.append('user_id', userId)

        const response = await axios.post('/api/v1/orders/import', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })

        if (response.data && response.data.success) {
          this.showSuccess(`Successfully imported ${response.data.imported || 'multiple'} orders`)
          // await this.loadOrders()
          this.showOrderImportDialog = false
          this.orderFile = null
        } else {
          this.showError(response.data?.message || 'Order import failed')
        }
      } catch (error) {
        console.error('Error importing orders:', error)
        this.showError(`Failed to import orders: ${error.response?.data?.message || error.message}`)
      } finally {
        this.loading.uploadingOrders = false
      }
    },

    // Dialog actions
    async openNewMessageDialog() {
      this.errorMessage = ''
      this.messageText = ''
      this.selectedContacts = []
      this.selectedTemplate = null

      if ((!Array.isArray(this.contacts) || this.contacts.length === 0) && !this.loading.contacts) {
        await this.loadContacts()
      }

      if ((!Array.isArray(this.templates) || this.templates.length === 0) && !this.loading.templates) {
        await this.loadTemplates()
      }

      this.showNewMessageDialog = true
    },




    async openNewMessageDialog(selectedOrders = []) {
      this.errorMessage = ''
      this.messageText = ''
      this.selectedContacts = []
      this.selectedTemplate = null

      // Load templates if not already available
      if ((!Array.isArray(this.templates) || this.templates.length === 0) && !this.loading.templates) {
        await this.loadTemplates()
      }

      // If order IDs are passed, map them to client info
      if (Array.isArray(selectedOrders) && selectedOrders.length > 0) {
        console.log('openNewMessageDialog: selectedOrders:', selectedOrders)
        this.selectedContacts = selectedOrders
          .map(id => {
            const order = this.orders.find(o => o.id === id)
            if (!order) {
              console.warn('Order not found for id:', id)
              return null
            }
            if (!order.client) {
              console.warn('Order has no client:', order)
              return null
            }
            return {
              id: order.client.id,
              name: order.client.name || order.client.phone,
              phone: order.client.phone_number,
              alt_phone: order.client.alt_phone

            }
          })
          .filter(client => {
            const valid = client?.id && client?.phone
            if (!valid) {
              console.warn('Invalid client object:', client)
            }
            return valid
          })
        console.log('openNewMessageDialog: selectedContacts from orders:', this.selectedContacts)
      }
      // 
      else {
        // If no orders passed, fallback to loading all contacts
        if ((!Array.isArray(this.contacts) || this.contacts.length === 0) && !this.loading.contacts) {
          await this.loadContacts()
        }
        this.selectedContacts = []
        console.log('Loaded all contacts:', this.contacts)
      }

      this.showNewMessageDialog = true
    },

    async openOrderMessageDialog() {
      this.errorMessage = ''
      this.messageText = ''
      this.selectedOrders = []
      this.selectedTemplate = null

      if ((!Array.isArray(this.orders) || this.orders.length === 0) && !this.loading.orders) {
        // await this.loadOrders()
      }

      if ((!Array.isArray(this.templates) || this.templates.length === 0) && !this.loading.templates) {
        await this.loadTemplates()
      }

      this.showOrderMessageDialog = true
    },

    // Delete message
    async deleteMessage(messageId) {
      if (!messageId) {
        return this.showError('Invalid message ID')
      }

      if (confirm('Are you sure you want to delete this message?')) {
        try {
          await axios.delete(`/api/v1/whatsapp-messages/${messageId}`)

          if (Array.isArray(this.messages)) {
            this.messages = this.messages.filter(msg => msg.id !== messageId)
            this.calculateStats()
          }

          this.showSuccess('Message deleted successfully')
        } catch (error) {
          console.error('Error deleting message:', error)
          this.showError(`Failed to delete message: ${error.response?.data?.message || error.message}`)
        }
      }
    },

    // Initialize store
    async initialize() {
      this.messages = []
      this.contacts = []
      this.orders = []
      this.templates = []
      this.riders = []
      this.agents = []

      await Promise.all([
        this.loadMessages(1),
        this.loadContacts(),
        // this.loadOrders(1),
        this.loadTemplates(),
        this.loadRiders(),
        this.loadAgents(),

      ])
    }
  }
})