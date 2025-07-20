<template>
  <AppLayout title="Messages">
    <div class="max-w-6xl mx-auto p-6">
      <!-- Header -->
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Messages</h1>
        <button 
          @click="openComposeModal"
          class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
          </svg>
          New Message
        </button>
      </div>

      <!-- Messages List -->
      <div class="bg-white rounded-lg shadow-sm border">
        <!-- Search and Filters -->
        <div class="p-4 border-b bg-gray-50">
          <div class="flex gap-4">
            <div class="flex-1">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search messages..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
            <select 
              v-model="selectedFilter"
              class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="all">All Messages</option>
              <option value="unread">Unread</option>
              <option value="read">Read</option>
            </select>
          </div>
        </div>

        <!-- Messages -->
        <div class="divide-y">
          <div v-if="isLoading" class="p-8 text-center text-gray-500">
            Loading messages...
          </div>
          
          <div v-else-if="filteredMessages.length === 0" class="p-8 text-center text-gray-500">
            No messages found
          </div>
          
          <div 
            v-else
            v-for="message in filteredMessages" 
            :key="message.id"
            class="p-4 hover:bg-gray-50 cursor-pointer transition-colors"
            :class="{ 'bg-blue-50': !message.read }"
            @click="openMessage(message)"
          >
            <div class="flex items-start gap-4">
              <!-- Avatar -->
              <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold">
                {{ message.sender.name.charAt(0).toUpperCase() }}
              </div>
              
              <!-- Message Content -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-1">
                  <h3 class="text-sm font-semibold text-gray-900 truncate">
                    {{ message.sender.name }}
                  </h3>
                  <span class="text-xs text-gray-500">
                    {{ formatDate(message.timestamp) }}
                  </span>
                </div>
                
                <p class="text-sm font-medium text-gray-900 mb-1">
                  {{ message.subject }}
                </p>
                
                <p class="text-sm text-gray-600 truncate">
                  {{ message.preview }}
                </p>
                
                <!-- Tags -->
                <div v-if="message.tags.length" class="flex gap-1 mt-2">
                  <span 
                    v-for="tag in message.tags" 
                    :key="tag"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                  >
                    {{ tag }}
                  </span>
                </div>
              </div>
              
              <!-- Status Indicators -->
              <div class="flex items-center gap-2">
                <div v-if="!message.read" class="w-2 h-2 bg-blue-600 rounded-full"></div>
                <button 
                  @click.stop="toggleFavorite(message.id)"
                  class="text-gray-400 hover:text-yellow-500 transition-colors"
                >
                  <svg class="w-4 h-4" :class="{ 'text-yellow-500 fill-current': message.favorite }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Message Detail Modal -->
    <div v-if="selectedMessage" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-hidden">
        <!-- Modal Header -->
        <div class="p-6 border-b">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900">{{ selectedMessage.subject }}</h2>
            <button @click="closeMessage" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          <div class="mt-2 text-sm text-gray-600">
            From: {{ selectedMessage.sender.name }} &lt;{{ selectedMessage.sender.email }}&gt;
          </div>
          <div class="text-sm text-gray-600">
            {{ formatDate(selectedMessage.timestamp) }}
          </div>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto max-h-96">
          <div class="prose max-w-none">
            {{ selectedMessage.content }}
          </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="p-6 border-t bg-gray-50 flex gap-3">
          <button 
            @click="replyToMessage"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
          >
            Reply
          </button>
          <button 
            @click="markAsRead(selectedMessage.id)"
            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors"
          >
            Mark as Read
          </button>
          <button 
            @click="deleteMessage(selectedMessage.id)"
            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors"
          >
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Compose Modal -->
    <div v-if="showComposeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg max-w-2xl w-full">
        <div class="p-6 border-b">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900">New Message</h2>
            <button @click="closeComposeModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
        </div>
        
        <form @submit.prevent="sendMessage" class="p-6">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
              <input
                v-model="newMessage.to"
                type="email"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="recipient@example.com"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
              <input
                v-model="newMessage.subject"
                type="text"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Message subject"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
              <textarea
                v-model="newMessage.content"
                rows="6"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Type your message here..."
              ></textarea>
            </div>
          </div>
          
          <div class="mt-6 flex gap-3">
            <button 
              type="submit"
              class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors"
            >
              Send Message
            </button>
            <button 
              type="button"
              @click="closeComposeModal"
              class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-lg transition-colors"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useMessagesStore } from '@/stores/messages'
import AppLayout from '@/Layouts/AppLayout.vue'
import { notify } from '@/utils/toast'

// Store
const messagesStore = useMessagesStore()

// Reactive data
const searchQuery = ref('')
const selectedFilter = ref('all')
const selectedMessage = ref(null)
const showComposeModal = ref(false)
const isLoading = ref(false)

const newMessage = ref({
  to: '',
  subject: '',
  content: ''
})

// Computed properties
const filteredMessages = computed(() => {
  let messages = messagesStore.messages
  
  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    messages = messages.filter(message => 
      message.subject.toLowerCase().includes(query) ||
      message.sender.name.toLowerCase().includes(query) ||
      message.preview.toLowerCase().includes(query)
    )
  }
  
  // Apply read/unread filter
  if (selectedFilter.value === 'read') {
    messages = messages.filter(message => message.read)
  } else if (selectedFilter.value === 'unread') {
    messages = messages.filter(message => !message.read)
  }
  
  return messages
})

// Methods
const openMessage = (message) => {
  selectedMessage.value = message
  if (!message.read) {
    messagesStore.markAsRead(message.id)
  }
}

const closeMessage = () => {
  selectedMessage.value = null
}

const openComposeModal = () => {
  showComposeModal.value = true
  newMessage.value = { to: '', subject: '', content: '' }
}

const closeComposeModal = () => {
  showComposeModal.value = false
  newMessage.value = { to: '', subject: '', content: '' }
}

const sendMessage = async () => {
  try {
    await messagesStore.sendMessage(newMessage.value)
    notify('Message sent successfully!', 'success')
    closeComposeModal()
  } catch (error) {
    notify('Failed to send message', 'error')
  }
}

const replyToMessage = () => {
  newMessage.value = {
    to: selectedMessage.value.sender.email,
    subject: `Re: ${selectedMessage.value.subject}`,
    content: ''
  }
  closeMessage()
  openComposeModal()
}

const markAsRead = (messageId) => {
  messagesStore.markAsRead(messageId)
  notify('Message marked as read', 'success')
}

const deleteMessage = (messageId) => {
  if (confirm('Are you sure you want to delete this message?')) {
    messagesStore.deleteMessage(messageId)
    notify('Message deleted', 'success')
    closeMessage()
  }
}

const toggleFavorite = (messageId) => {
  messagesStore.toggleFavorite(messageId)
}

const formatDate = (timestamp) => {
  const date = new Date(timestamp)
  const now = new Date()
  const diff = now - date
  
  // If less than 24 hours, show time
  if (diff < 24 * 60 * 60 * 1000) {
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  }
  
  // If less than a week, show day
  if (diff < 7 * 24 * 60 * 60 * 1000) {
    return date.toLocaleDateString([], { weekday: 'short' })
  }
  
  // Otherwise show date
  return date.toLocaleDateString([], { month: 'short', day: 'numeric' })
}

// Lifecycle
onMounted(async () => {
  isLoading.value = true
  try {
    await messagesStore.fetchMessages()
  } catch (error) {
    notify('Failed to load messages', 'error')
  } finally {
    isLoading.value = false
  }
})

// Watchers
watch(searchQuery, () => {
  // Debounce search if needed
})
</script>