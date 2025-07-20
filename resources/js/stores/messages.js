// stores/messages.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useMessagesStore = defineStore('messages', () => {
  // State
  const messages = ref([])
  const loading = ref(false)
  const error = ref(null)

  // Mock data for demonstration
  const mockMessages = [
    {
      id: 1,
      sender: {
        name: 'John Doe',
        email: 'john.doe@example.com'
      },
      subject: 'Project Update - Q4 Progress',
      preview: 'Hi team, I wanted to share the latest updates on our Q4 progress...',
      content: `Hi team,

I wanted to share the latest updates on our Q4 progress. We've made significant strides in several key areas:

1. Development milestone reached ahead of schedule
2. User testing feedback has been overwhelmingly positive
3. Budget tracking shows we're on target

Please review the attached documents and let me know if you have any questions.

Best regards,
John`,
      timestamp: Date.now() - 2 * 60 * 60 * 1000, // 2 hours ago
      read: false,
      favorite: false,
      tags: ['work', 'urgent']
    },
    {
      id: 2,
      sender: {
        name: 'Sarah Wilson',
        email: 'sarah.wilson@example.com'
      },
      subject: 'Meeting Reminder: Weekly Sync',
      preview: 'Just a friendly reminder about our weekly sync meeting scheduled for tomorrow...',
      content: `Hi everyone,

Just a friendly reminder about our weekly sync meeting scheduled for tomorrow at 10 AM.

Agenda:
- Review last week's deliverables
- Discuss upcoming deadlines
- Address any blockers

Please come prepared with your status updates.

Thanks,
Sarah`,
      timestamp: Date.now() - 5 * 60 * 60 * 1000, // 5 hours ago
      read: true,
      favorite: true,
      tags: ['meeting']
    },
    {
      id: 3,
      sender: {
        name: 'Marketing Team',
        email: 'marketing@example.com'
      },
      subject: 'New Campaign Launch Success',
      preview: 'Great news! Our latest marketing campaign has exceeded expectations...',
      content: `Team,

Great news! Our latest marketing campaign has exceeded expectations with a 25% increase in engagement and 15% boost in conversions.

Key metrics:
- Reach: 100K+ users
- Engagement rate: 8.5%
- Conversion rate: 3.2%
- ROI: 150%

Congratulations to everyone involved!

Marketing Team`,
      timestamp: Date.now() - 24 * 60 * 60 * 1000, // 1 day ago
      read: true,
      favorite: false,
      tags: ['marketing', 'success']
    },
    {
      id: 4,
      sender: {
        name: 'IT Support',
        email: 'support@example.com'
      },
      subject: 'System Maintenance Scheduled',
      preview: 'Please be aware that we have scheduled system maintenance for this weekend...',
      content: `Dear Users,

Please be aware that we have scheduled system maintenance for this weekend (Saturday, 2:00 AM - 6:00 AM).

During this time:
- Email services may be temporarily unavailable
- File sharing systems will be offline
- Database backups will be performed

We apologize for any inconvenience and appreciate your patience.

IT Support Team`,
      timestamp: Date.now() - 2 * 24 * 60 * 60 * 1000, // 2 days ago
      read: false,
      favorite: false,
      tags: ['maintenance', 'system']
    },
    {
      id: 5,
      sender: {
        name: 'HR Department',
        email: 'hr@example.com'
      },
      subject: 'Employee Benefits Update',
      preview: 'We are excited to announce some improvements to our employee benefits package...',
      content: `Dear Team,

We are excited to announce some improvements to our employee benefits package effective next month:

New Benefits:
- Enhanced health insurance coverage
- Increased vacation days
- Professional development budget
- Flexible working arrangements

Please review the updated benefits handbook and contact HR with any questions.

Best regards,
HR Department`,
      timestamp: Date.now() - 3 * 24 * 60 * 60 * 1000, // 3 days ago
      read: true,
      favorite: true,
      tags: ['hr', 'benefits']
    }
  ]

  // Getters
  const unreadCount = computed(() => 
    messages.value.filter(message => !message.read).length
  )

  const favoriteMessages = computed(() => 
    messages.value.filter(message => message.favorite)
  )

  const messagesByTag = computed(() => {
    const tagMap = {}
    messages.value.forEach(message => {
      message.tags.forEach(tag => {
        if (!tagMap[tag]) {
          tagMap[tag] = []
        }
        tagMap[tag].push(message)
      })
    })
    return tagMap
  })

  // Actions
  const fetchMessages = async () => {
    loading.value = true
    error.value = null
    
    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1000))
      
      // In a real app, this would be an API call
      messages.value = [...mockMessages].sort((a, b) => b.timestamp - a.timestamp)
    } catch (err) {
      error.value = 'Failed to fetch messages'
      console.error('Error fetching messages:', err)
    } finally {
      loading.value = false
    }
  }

  const sendMessage = async (messageData) => {
    loading.value = true
    error.value = null
    
    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 500))
      
      const newMessage = {
        id: messages.value.length + 1,
        sender: {
          name: 'You',
          email: 'you@example.com'
        },
        subject: messageData.subject,
        preview: messageData.content.substring(0, 50) + '...',
        content: messageData.content,
        timestamp: Date.now(),
        read: true, // Sent messages are marked as read
        favorite: false,
        tags: []
      }
      
      messages.value.unshift(newMessage)
    } catch (err) {
      error.value = 'Failed to send message'
      throw err
    } finally {
      loading.value = false
    }
  }

  const markAsRead = (messageId) => {
    const message = messages.value.find(msg => msg.id === messageId)
    if (message) {
      message.read = true
    }
  }

  const markAsUnread = (messageId) => {
    const message = messages.value.find(msg => msg.id === messageId)
    if (message) {
      message.read = false
    }
  }

  const toggleFavorite = (messageId) => {
    const message = messages.value.find(msg => msg.id === messageId)
    if (message) {
      message.favorite = !message.favorite
    }
  }

  const deleteMessage = (messageId) => {
    const index = messages.value.findIndex(msg => msg.id === messageId)
    if (index > -1) {
      messages.value.splice(index, 1)
    }
  }

  const addTag = (messageId, tag) => {
    const message = messages.value.find(msg => msg.id === messageId)
    if (message && !message.tags.includes(tag)) {
      message.tags.push(tag)
    }
  }

  const removeTag = (messageId, tag) => {
    const message = messages.value.find(msg => msg.id === messageId)
    if (message) {
      const index = message.tags.indexOf(tag)
      if (index > -1) {
        message.tags.splice(index, 1)
      }
    }
  }

  const searchMessages = (query) => {
    if (!query) return messages.value
    
    const searchTerm = query.toLowerCase()
    return messages.value.filter(message =>
      message.subject.toLowerCase().includes(searchTerm) ||
      message.sender.name.toLowerCase().includes(searchTerm) ||
      message.content.toLowerCase().includes(searchTerm) ||
      message.tags.some(tag => tag.toLowerCase().includes(searchTerm))
    )
  }

  const getMessageById = (messageId) => {
    return messages.value.find(msg => msg.id === messageId)
  }

  const markAllAsRead = () => {
    messages.value.forEach(message => {
      message.read = true
    })
  }

  const deleteMultipleMessages = (messageIds) => {
    messages.value = messages.value.filter(msg => !messageIds.includes(msg.id))
  }

  const getMessageStats = computed(() => ({
    total: messages.value.length,
    unread: unreadCount.value,
    favorites: favoriteMessages.value.length,
    tags: Object.keys(messagesByTag.value).length
  }))

  return {
    // State
    messages,
    loading,
    error,

    // Getters
    unreadCount,
    favoriteMessages,
    messagesByTag,
    getMessageStats,

    // Actions
    fetchMessages,
    sendMessage,
    markAsRead,
    markAsUnread,
    toggleFavorite,
    deleteMessage,
    addTag,
    removeTag,
    searchMessages,
    getMessageById,
    markAllAsRead,
    deleteMultipleMessages
  }
})