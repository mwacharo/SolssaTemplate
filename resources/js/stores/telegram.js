// stores/crm.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useCrmStore = defineStore('crm', () => {
  // State
  const stats = ref({
    totalSms: 0,
    formResponses: 0,
    telegramMessages: 0,
    aiQueries: 0
  })

  const vendors = ref([
    { id: 1, name: 'Vendor A', phone: '+254700000001', email: 'vendora@example.com' },
    { id: 2, name: 'Vendor B', phone: '+254700000002', email: 'vendorb@example.com' },
    { id: 3, name: 'Vendor C', phone: '+254700000003', email: 'vendorc@example.com' }
  ])

  const smsCampaigns = ref([])
  const formResponses = ref([])
  const telegramGroups = ref([])
  const telegramBroadcasts = ref([])
  const aiResponses = ref([])
  const newResponse = ref(null)

  // Computed
  const activeVendors = computed(() => 
    vendors.value.filter(vendor => vendor.active !== false)
  )

  const recentResponses = computed(() => 
    formResponses.value.slice(-10).reverse()
  )

  // Actions
  const loadDashboardData = async () => {
    try {
      // Simulate API calls to load data
      await Promise.all([
        loadStats(),
        loadSmsCampaigns(),
        loadFormResponses(),
        loadTelegramGroups(),
        loadTelegramBroadcasts(),
        loadAiResponses()
      ])
    } catch (error) {
      console.error('Failed to load dashboard data:', error)
    }
  }

  const loadStats = async () => {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 500))
    stats.value = {
      totalSms: 1250,
      formResponses: 890,
      telegramMessages: 340,
      aiQueries: 156
    }
  }

  const loadSmsCampaigns = async () => {
    await new Promise(resolve => setTimeout(resolve, 300))
    smsCampaigns.value = [
      {
        id: 1,
        name: 'Product Launch Campaign',
        recipients: 500,
        status: 'completed',
        createdAt: Date.now() - 86400000
      },
      {
        id: 2,
        name: 'Customer Feedback Survey',
        recipients: 300,
        status: 'pending',
        createdAt: Date.now() - 43200000
      },
      {
        id: 3,
        name: 'Service Update Notification',
        recipients: 750,
        status: 'active',
        createdAt: Date.now() - 21600000
      }
    ]
  }

  const loadFormResponses = async () => {
    await new Promise(resolve => setTimeout(resolve, 400))
    formResponses.value = [
      {
        id: 1,
        clientName: 'John Doe',
        clientPhone: '+254700000001',
        vendorId: 1,
        vendorName: 'Vendor A',
        message: 'I am interested in your product. Please contact me for more details.',
        timestamp: Date.now() - 3600000,
        status: 'new'
      },
      {
        id: 2,
        clientName: 'Jane Smith',
        clientPhone: '+254700000002',
        vendorId: 2,
        vendorName: 'Vendor B',
        message: 'What are your pricing options for bulk orders?',
        timestamp: Date.now() - 7200000,
        status: 'responded'
      },
      {
        id: 3,
        clientName: 'Mike Johnson',
        clientPhone: '+254700000003',
        vendorId: 1,
        vendorName: 'Vendor A',
        message: 'Can you provide delivery to Nairobi CBD?',
        timestamp: Date.now() - 10800000,
        status: 'broadcasted'
      }
    ]
  }

  const loadTelegramGroups = async () => {
    await new Promise(resolve => setTimeout(resolve, 200))
    telegramGroups.value = [
      {
        id: 1,
        name: 'Vendor A Customers',
        chatId: '-1001234567890',
        members: 150,
        active: true
      },
      {
        id: 2,
        name: 'General Support',
        chatId: '-1001234567891',
        members: 300,
        active: true
      },
      {
        id: 3,
        name: 'VIP Customers',
        chatId: '-1001234567892',
        members: 45,
        active: false
      }
    ]
  }

  const loadTelegramBroadcasts = async () => {
    await new Promise(resolve => setTimeout(resolve, 250))
    telegramBroadcasts.value = [
      {
        id: 1,
        groupName: 'Vendor A Customers',
        message: 'New customer inquiry: John Doe is interested in our product...',
        timestamp: Date.now() - 1800000
      },
      {
        id: 2,
        groupName: 'General Support',
        message: 'Jane Smith asked about bulk pricing options...',
        timestamp: Date.now() - 5400000
      }
    ]
  }

  const loadAiResponses = async () => {
    await new Promise(resolve => setTimeout(resolve, 350))
    aiResponses.value = [
      {
        id: 1,
        query: 'What is the best response for a pricing inquiry?',
        response: 'For pricing inquiries, acknowledge their interest and provide a personalized quote based on their specific needs...',
        timestamp: Date.now() - 900000
      },
      {
        id: 2,
        query: 'How to handle delivery questions?',
        response: 'For delivery questions, confirm the delivery location and provide accurate delivery timeframes and costs...',
        timestamp: Date.now() - 2700000
      }
    ]
  }

  const createSmsCampaign = async (campaignData) => {
    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1000))
      
      const newCampaign = {
        id: Date.now(),
        name: campaignData.name,
        message: campaignData.message,
        vendorId: campaignData.vendorId,
        recipients: Math.floor(Math.random() * 500) + 100,
        status: 'pending',
        createdAt: Date.now()
      }
      
      smsCampaigns.value.unshift(newCampaign)
      stats.value.totalSms += newCampaign.recipients
      
      // Simulate sending SMS and generating form link
      const formLink = `https://forms.example.com/vendor-${campaignData.vendorId}/${newCampaign.id}`
      
      // Start campaign processing
      setTimeout(() => {
        newCampaign.status = 'active'
        // Simulate some responses coming in
        simulateFormResponses(newCampaign)
      }, 2000)
      
      return newCampaign
    } catch (error) {
      console.error('Failed to create SMS campaign:', error)
      throw error
    }
  }

  const simulateFormResponses = (campaign) => {
    const responseCount = Math.floor(Math.random() * 10) + 3
    
    for (let i = 0; i < responseCount; i++) {
      setTimeout(() => {
        const newResponse = {
          id: Date.now() + i,
          clientName: `Client ${Math.floor(Math.random() * 1000)}`,
          clientPhone: `+25470${Math.floor(Math.random() * 10000000).toString().padStart(7, '0')}`,
          vendorId: campaign.vendorId,
          vendorName: vendors.value.find(v => v.id === campaign.vendorId)?.name || 'Unknown Vendor',
          message: generateRandomResponse(),
          timestamp: Date.now() + (i * 1000),
          status: 'new',
          campaignId: campaign.id
        }
        
        formResponses.value.unshift(newResponse)
        stats.value.formResponses++
        setNewResponse(newResponse)
      }, i * 2000)
    }
  }

  const generateRandomResponse = () => {
    const responses = [
      'I am interested in your product. Please contact me.',
      'What are your prices for bulk orders?',
      'Do you deliver to my area?',
      'Can I get a discount on my first order?',
      'I need more information about your services.',
      'Is this product available in different colors?',
      'What is your return policy?',
      'Can you provide installation services?'
    ]
    return responses[Math.floor(Math.random() * responses.length)]
  }

  const broadcastToTelegram = async (response) => {
    try {
      await new Promise(resolve => setTimeout(resolve, 800))
      
      // Find appropriate Telegram group for the vendor
      const vendorGroup = telegramGroups.value.find(group => 
        group.active && group.name.includes(response.vendorName)
      ) || telegramGroups.value.find(group => group.active)
      
      if (!vendorGroup) {
        throw new Error('No active Telegram group found')
      }
      
      const broadcastMessage = `🔔 New Customer Inquiry\n\n` +
        `Customer: ${response.clientName}\n` +
        `Phone: ${response.clientPhone}\n` +
        `Vendor: ${response.vendorName}\n` +
        `Message: ${response.message}\n\n` +
        `Time: ${new Date(response.timestamp).toLocaleString()}`
      
      // Simulate Telegram API call
      const broadcast = {
        id: Date.now(),
        groupName: vendorGroup.name,
        message: broadcastMessage,
        timestamp: Date.now(),
        responseId: response.id
      }
      
      telegramBroadcasts.value.unshift(broadcast)
      stats.value.telegramMessages++
      
      // Update response status
      const responseIndex = formResponses.value.findIndex(r => r.id === response.id)
      if (responseIndex !== -1) {
        formResponses.value[responseIndex].status = 'broadcasted'
      }
      
      return broadcast
    } catch (error) {
      console.error('Failed to broadcast to Telegram:', error)
      throw error
    }
  }

  const generateAiResponse = async (response) => {
    try {
      await new Promise(resolve => setTimeout(resolve, 1200))
      
      // Simulate AI processing of the customer query
      const aiResponse = await processCustomerQuery(response.message)
      
      const newAiResponse = {
        id: Date.now(),
        query: response.message,
        response: aiResponse,
        timestamp: Date.now(),
        customerName: response.clientName,
        vendorName: response.vendorName,
        originalResponseId: response.id
      }
      
      aiResponses.value.unshift(newAiResponse)
      stats.value.aiQueries++
      
      // Update response status
      const responseIndex = formResponses.value.findIndex(r => r.id === response.id)
      if (responseIndex !== -1) {
        formResponses.value[responseIndex].status = 'ai_responded'
        formResponses.value[responseIndex].aiResponse = aiResponse
      }
      
      return newAiResponse
    } catch (error) {
      console.error('Failed to generate AI response:', error)
      throw error
    }
  }

  const processCustomerQuery = async (query) => {
    // Simulate AI processing logic
    const lowerQuery = query.toLowerCase()
    
    if (lowerQuery.includes('price') || lowerQuery.includes('cost') || lowerQuery.includes('pricing')) {
      return `Thank you for your interest in our pricing. We offer competitive rates with flexible payment options. Our team will contact you within 24 hours to discuss a personalized quote based on your specific needs. For immediate assistance, please call our sales hotline.`
    }
    
    if (lowerQuery.includes('deliver') || lowerQuery.includes('shipping')) {
      return `We provide delivery services across Nairobi and surrounding areas. Standard delivery takes 2-3 business days, with express options available. Delivery charges depend on location and order size. We'll confirm delivery details and timeline once we process your order.`
    }
    
    if (lowerQuery.includes('product') || lowerQuery.includes('service')) {
      return `Thank you for your interest in our products/services. We'd be happy to provide detailed information and help you find the perfect solution for your needs. Our customer service team will reach out to you shortly with comprehensive details and answer any questions you may have.`
    }
    
    if (lowerQuery.includes('contact') || lowerQuery.includes('call')) {
      return `We appreciate your interest in getting in touch with us. Our customer service team is available Monday-Friday 8AM-6PM, Saturday 9AM-2PM. We'll prioritize your inquiry and ensure you receive a prompt response within 4 hours during business hours.`
    }
    
    // Default response
    return `Thank you for reaching out to us. We've received your inquiry and our team will review it carefully. We'll get back to you within 24 hours with a detailed response. In the meantime, feel free to contact us directly if you have any urgent questions.`
  }

  const addTelegramGroup = async (groupData) => {
    try {
      await new Promise(resolve => setTimeout(resolve, 600))
      
      // Simulate Telegram bot verification
      const isValidBot = await verifyTelegramBot(groupData.botToken, groupData.chatId)
      
      if (!isValidBot) {
        throw new Error('Invalid bot token or chat ID')
      }
      
      const newGroup = {
        id: Date.now(),
        name: groupData.name,
        chatId: groupData.chatId,
        botToken: groupData.botToken,
        members: Math.floor(Math.random() * 200) + 50,
        active: true,
        createdAt: Date.now()
      }
      
      telegramGroups.value.push(newGroup)
      
      return newGroup
    } catch (error) {
      console.error('Failed to add Telegram group:', error)
      throw error
    }
  }

  const verifyTelegramBot = async (botToken, chatId) => {
    // Simulate bot verification
    await new Promise(resolve => setTimeout(resolve, 500))
    return botToken && chatId && botToken.length > 20
  }

  const processAiQuery = async (query) => {
    try {
      await new Promise(resolve => setTimeout(resolve, 1000))
      
      const response = await generateAiQueryResponse(query)
      
      const newResponse = {
        id: Date.now(),
        query: query,
        response: response,
        timestamp: Date.now(),
        type: 'manual_query'
      }
      
      aiResponses.value.unshift(newResponse)
      stats.value.aiQueries++
      
      return newResponse
    } catch (error) {
      console.error('Failed to process AI query:', error)
      throw error
    }
  }

  const generateAiQueryResponse = async (query) => {
    const lowerQuery = query.toLowerCase()
    
    if (lowerQuery.includes('best') && lowerQuery.includes('response')) {
      return `Based on successful customer interactions, the best responses include: 1) Acknowledge their interest immediately, 2) Provide specific information relevant to their query, 3) Offer multiple contact options, 4) Set clear expectations for follow-up, 5) Include a personal touch when possible.`
    }
    
    if (lowerQuery.includes('improve') && (lowerQuery.includes('conversion') || lowerQuery.includes('response rate'))) {
      return `To improve response rates: 1) Personalize SMS messages with customer names, 2) Include clear call-to-action with benefits, 3) Optimize form length (max 3-5 fields), 4) Send follow-up reminders after 48 hours, 5) A/B test different message formats and timing.`
    }
    
    if (lowerQuery.includes('telegram') && lowerQuery.includes('engagement')) {
      return `Telegram engagement best practices: 1) Post regular updates and valuable content, 2) Encourage community interaction with polls and questions, 3) Respond promptly to group messages, 4) Share success stories and testimonials, 5) Use multimedia content when appropriate.`
    }
    
    if (lowerQuery.includes('analytics') || lowerQuery.includes('metrics')) {
      return `Key metrics to track: 1) SMS delivery and read rates, 2) Form completion rates by vendor, 3) Response time to customer queries, 4) Telegram group engagement levels, 5) Customer acquisition cost per channel. Monitor these weekly and adjust strategies based on trends.`
    }
    
    return `I'd be happy to help with your query. Based on the current CRM data and best practices, I recommend analyzing your specific use case in detail. Consider factors like your target audience, current performance metrics, and business objectives. Would you like me to provide more specific guidance on any particular aspect?`
  }

  const trainAiModel = async () => {
    try {
      await new Promise(resolve => setTimeout(resolve, 2000))
      
      // Simulate model training process
      const trainingData = {
        responses: formResponses.value.length,
        campaigns: smsCampaigns.value.length,
        telegramMessages: telegramBroadcasts.value.length,
        queries: aiResponses.value.length
      }
      
      console.log('Training AI model with data:', trainingData)
      
      // Simulate training completion
      setTimeout(() => {
        console.log('AI model training completed successfully')
      }, 3000)
      
      return { status: 'training_started', data: trainingData }
    } catch (error) {
      console.error('Failed to train AI model:', error)
      throw error
    }
  }

  const setNewResponse = (response) => {
    newResponse.value = response
    // Clear after 5 seconds to avoid repeated notifications
    setTimeout(() => {
      if (newResponse.value?.id === response.id) {
        newResponse.value = null
      }
    }, 5000)
  }

  const updateResponseStatus = (responseId, status) => {
    const index = formResponses.value.findIndex(r => r.id === responseId)
    if (index !== -1) {
      formResponses.value[index].status = status
    }
  }

  const getVendorStats = () => {
    return vendors.value.map(vendor => {
      const vendorResponses = formResponses.value.filter(r => r.vendorId === vendor.id)
      const vendorCampaigns = smsCampaigns.value.filter(c => c.vendorId === vendor.id)
      
      return {
        ...vendor,
        responseCount: vendorResponses.length,
        campaignCount: vendorCampaigns.length,
        conversionRate: vendorCampaigns.length > 0 ? 
          ((vendorResponses.length / vendorCampaigns.reduce((sum, c) => sum + c.recipients, 0)) * 100).toFixed(1) : 0
      }
    })
  }

  // Real-time updates simulation
  const startRealTimeUpdates = () => {
    // Simulate periodic new responses
    setInterval(() => {
      if (Math.random() > 0.7) { // 30% chance every interval
        const randomVendor = vendors.value[Math.floor(Math.random() * vendors.value.length)]
        const newResp = {
          id: Date.now(),
          clientName: `Walk-in Customer ${Math.floor(Math.random() * 1000)}`,
          clientPhone: `+25470${Math.floor(Math.random() * 10000000).toString().padStart(7, '0')}`,
          vendorId: randomVendor.id,
          vendorName: randomVendor.name,
          message: generateRandomResponse(),
          timestamp: Date.now(),
          status: 'new'
        }
        
        formResponses.value.unshift(newResp)
        stats.value.formResponses++
        setNewResponse(newResp)
      }
    }, 30000) // Every 30 seconds
  }

  return {
    // State
    stats,
    vendors,
    smsCampaigns,
    formResponses,
    telegramGroups,
    telegramBroadcasts,
    aiResponses,
    newResponse,
    
    // Computed
    activeVendors,
    recentResponses,
    
    // Actions
    loadDashboardData,
    createSmsCampaign,
    broadcastToTelegram,
    generateAiResponse,
    addTelegramGroup,
    processAiQuery,
    trainAiModel,
    updateResponseStatus,
    getVendorStats,
    startRealTimeUpdates
  }
})