// stores/emailStore.js
import { defineStore } from 'pinia'

export const useEmailStore = defineStore('email', {
  state: () => ({
    // Email data
    sentEmails: [],
    drafts: [],
    templates: [],
    
    // Clients data
    clients: [],
    
    // Loading states
    isLoading: false,
    isSending: false
  }),

  getters: {
    // Get drafts count
    draftsCount: (state) => state.drafts.length,
    
    // Get sent emails count
    sentEmailsCount: (state) => state.sentEmails.length,
    
    // Get templates count
    templatesCount: (state) => state.templates.length,
    
    // Get emails by client
    getEmailsByClient: (state) => (clientId) => {
      return state.sentEmails.filter(email => email.clientId === clientId)
    },
    
    // Get emails by date range
    getEmailsByDateRange: (state) => (startDate, endDate) => {
      return state.sentEmails.filter(email => {
        const emailDate = new Date(email.sentAt)
        const start = new Date(startDate)
        const end = new Date(endDate)
        return emailDate >= start && emailDate <= end
      })
    },
    
    // Get recent emails (last 7 days)
    recentEmails: (state) => {
      const sevenDaysAgo = new Date()
      sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7)
      
      return state.sentEmails.filter(email => 
        new Date(email.sentAt) >= sevenDaysAgo
      ).sort((a, b) => new Date(b.sentAt) - new Date(a.sentAt))
    },
    
    // Get template by ID
    getTemplateById: (state) => (id) => {
      return state.templates.find(template => template.id === id)
    },
    
    // Get client by ID
    getClientById: (state) => (id) => {
      return state.clients.find(client => client.id === id)
    }
  },

  actions: {
    // Initialize store with sample data
    initializeData() {
      // Sample clients
      this.clients = [
        { id: 1, name: 'John Doe', email: 'john@example.com', company: 'Doe Enterprises' },
        { id: 2, name: 'Jane Smith', email: 'jane@example.com', company: 'Smith Corp' },
        { id: 3, name: 'Bob Johnson', email: 'bob@example.com', company: 'Johnson LLC' },
        { id: 4, name: 'Alice Brown', email: 'alice@example.com', company: 'Brown Industries' },
        { id: 5, name: 'Charlie Wilson', email: 'charlie@example.com', company: 'Wilson Co' }
      ]

      // Sample email templates
      this.templates = [
        {
          id: 1,
          name: 'Welcome Email',
          subject: 'Welcome to Our Service, {{client_name}}!',
          body: `Dear {{client_name}},

Welcome to our service! We're excited to have you on board.

We'll be in touch soon with next steps.

Best regards,
The Team`,
          createdAt: new Date('2024-01-15')
        },
        {
          id: 2,
          name: 'Follow-up Email',
          subject: 'Following up on our conversation',
          body: `Hi {{client_name}},

I wanted to follow up on our recent conversation and see if you have any questions.

Please don't hesitate to reach out if you need anything.

Best regards,
Sales Team`,
          createdAt: new Date('2024-01-20')
        },
        {
          id: 3,
          name: 'Invoice Reminder',
          subject: 'Invoice Reminder - {{client_name}}',
          body: `Dear {{client_name}},

This is a friendly reminder that your invoice is due on {{date}}.

Please let us know if you have any questions.

Thank you,
Billing Department`,
          createdAt: new Date('2024-02-01')
        },
        {
          id: 4,
          name: 'Project Update',
          subject: 'Project Update - {{date}}',
          body: `Hi {{client_name}},

Here's your weekly project update for {{date}}:

- Task 1: Completed
- Task 2: In Progress
- Task 3: Scheduled

We'll continue to keep you updated on our progress.

Best regards,
Project Team`,
          createdAt: new Date('2024-02-10')
        }
      ]

      // Sample sent emails
      this.sentEmails = [
        {
          id: 1,
          to: 'john@example.com',
          subject: 'Welcome to Our Service, John Doe!',
          body: 'Dear John Doe,\n\nWelcome to our service! We\'re excited to have you on board...',
          clientId: 1,
          sentAt: new Date('2024-02-15T10:30:00'),
          status: 'delivered',
          attachments: []
        },
        {
          id: 2,
          to: 'jane@example.com',
          subject: 'Following up on our conversation',
          body: 'Hi Jane Smith,\n\nI wanted to follow up on our recent conversation...',
          clientId: 2,
          sentAt: new Date('2024-02-14T14:15:00'),
          status: 'delivered',
          attachments: [{ name: 'proposal.pdf', size: 245000 }]
        },
        {
          id: 3,
          to: 'bob@example.com',
          subject: 'Invoice Reminder - Bob Johnson',
          body: 'Dear Bob Johnson,\n\nThis is a friendly reminder that your invoice is due...',
          clientId: 3,
          sentAt: new Date('2024-02-13T09:45:00'),
          status: 'delivered',
          attachments: [{ name: 'invoice_001.pdf', size: 156000 }]
        },
        {
          id: 4,
          to: 'alice@example.com',
          subject: 'Project Update - February 12, 2024',
          body: 'Hi Alice Brown,\n\nHere\'s your weekly project update...',
          clientId: 4,
          sentAt: new Date('2024-02-12T16:20:00'),
          status: 'sent',
          attachments: []
        }
      ]

      // Sample drafts
      this.drafts = [
        {
          id: 1,
          to: 'charlie@example.com',
          subject: 'Meeting Request',
          body: 'Hi Charlie,\n\nI would like to schedule a meeting to discuss...',
          client: '5',
          attachments: [],
          createdAt: new Date('2024-02-16T11:30:00')
        },
        {
          id: 2,
          to: 'multiple@recipients.com',
          subject: 'Newsletter Draft',
          body: 'Dear Subscribers,\n\nWe have some exciting updates to share...',
          client: '',
          attachments: [{ name: 'newsletter_images.zip', size: 1250000 }],
          createdAt: new Date('2024-02-15T15:45:00')
        }
      ]
    },

    // Email actions
    async sendEmail(emailData) {
      this.isSending = true
      
      try {
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 1000))
        
        // Add to sent emails
        const newEmail = {
          ...emailData,
          id: Date.now() + Math.random(),
          sentAt: new Date(),
          status: 'sent'
        }
        
        this.sentEmails.unshift(newEmail)
        
        // Update status after a delay (simulate delivery)
        setTimeout(() => {
          const email = this.sentEmails.find(e => e.id === newEmail.id)
          if (email) {
            email.status = 'delivered'
          }
        }, 3000)
        
        return newEmail
      } catch (error) {
        throw new Error('Failed to send email: ' + error.message)
      } finally {
        this.isSending = false
      }
    },

    // Draft actions
    saveDraft(draftData) {
      const existingDraftIndex = this.drafts.findIndex(d => d.id === draftData.id)
      
      if (existingDraftIndex !== -1) {
        // Update existing draft
        this.drafts[existingDraftIndex] = {
          ...draftData,
          updatedAt: new Date()
        }
      } else {
        // Create new draft
        const newDraft = {
          ...draftData,
          id: Date.now() + Math.random(),
          createdAt: new Date()
        }
        this.drafts.unshift(newDraft)
      }
    },

    deleteDraft(draftId) {
      const index = this.drafts.findIndex(d => d.id === draftId)
      if (index !== -1) {
        this.drafts.splice(index, 1)
      }
    },

    getDraftById(draftId) {
      return this.drafts.find(d => d.id === draftId)
    },

    // Template actions
    saveTemplate(templateData) {
      const existingTemplateIndex = this.templates.findIndex(t => t.id === templateData.id)
      
      if (existingTemplateIndex !== -1) {
        // Update existing template
        this.templates[existingTemplateIndex] = {
          ...templateData,
          updatedAt: new Date()
        }
      } else {
        // Create new template
        const newTemplate = {
          ...templateData,
          id: Date.now() + Math.random(),
          createdAt: new Date()
        }
        this.templates.push(newTemplate)
      }
    },

    deleteTemplate(templateId) {
      const index = this.templates.findIndex(t => t.id === templateId)
      if (index !== -1) {
        this.templates.splice(index, 1)
      }
    },

    duplicateTemplate(templateId) {
      const template = this.templates.find(t => t.id === templateId)
      if (template) {
        const duplicatedTemplate = {
          ...template,
          id: Date.now() + Math.random(),
          name: `${template.name} (Copy)`,
          createdAt: new Date()
        }
        this.templates.push(duplicatedTemplate)
        return duplicatedTemplate
      }
    },

    // Client actions
    addClient(clientData) {
      const newClient = {
        ...clientData,
        id: Date.now() + Math.random(),
        createdAt: new Date()
      }
      this.clients.push(newClient)
      return newClient
    },

    updateClient(clientId, clientData) {
      const index = this.clients.findIndex(c => c.id === clientId)
      if (index !== -1) {
        this.clients[index] = {
          ...this.clients[index],
          ...clientData,
          updatedAt: new Date()
        }
      }
    },

    deleteClient(clientId) {
      const index = this.clients.findIndex(c => c.id === clientId)
      if (index !== -1) {
        this.clients.splice(index, 1)
        
        // Also remove related emails and drafts
        this.sentEmails = this.sentEmails.filter(e => e.clientId !== clientId)
        this.drafts = this.drafts.filter(d => d.client !== clientId.toString())
      }
    },

    // Bulk email actions
    async sendBulkEmails(emailData, clientIds) {
      this.isSending = true
      const results = []
      
      try {
        for (const clientId of clientIds) {
          const client = this.clients.find(c => c.id === clientId)
          if (client) {
            const personalizedEmail = {
              ...emailData,
              to: client.email,
              clientId: clientId,
              subject: this.personalizeMail(emailData.subject, client),
              body: this.personalizeMail(emailData.body, client)
            }
            
            const sentEmail = await this.sendEmail(personalizedEmail)
            results.push(sentEmail)
          }
        }
        
        return results
      } catch (error) {
        throw new Error('Failed to send bulk emails: ' + error.message)
      } finally {
        this.isSending = false
      }
    },

    // Utility functions
    personalizeMail(content, client) {
      return content
        .replace(/\{\{client_name\}\}/g, client.name)
        .replace(/\{\{company_name\}\}/g, client.company || '')
        .replace(/\{\{email\}\}/g, client.email)
        .replace(/\{\{date\}\}/g, new Date().toLocaleDateString())
    },

    // Search and filter
    searchEmails(query, type = 'sent') {
      const emails = type === 'sent' ? this.sentEmails : this.drafts
      const lowerQuery = query.toLowerCase()
      
      return emails.filter(email => 
        email.subject.toLowerCase().includes(lowerQuery) ||
        email.body.toLowerCase().includes(lowerQuery) ||
        email.to.toLowerCase().includes(lowerQuery)
      )
    },

    // Statistics
    getEmailStats() {
      const today = new Date()
      const thisWeek = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000)
      const thisMonth = new Date(today.getFullYear(), today.getMonth(), 1)

      return {
        total: this.sentEmails.length,
        thisWeek: this.sentEmails.filter(e => new Date(e.sentAt) >= thisWeek).length,
        thisMonth: this.sentEmails.filter(e => new Date(e.sentAt) >= thisMonth).length,
        delivered: this.sentEmails.filter(e => e.status === 'delivered').length,
        pending: this.sentEmails.filter(e => e.status === 'sent').length,
        drafts: this.drafts.length,
        templates: this.templates.length
      }
    },

    // Export data
    exportEmails(type = 'sent', format = 'json') {
      const data = type === 'sent' ? this.sentEmails : this.drafts
      
      if (format === 'csv') {
        // Convert to CSV format
        const headers = ['Date', 'To', 'Subject', 'Status', 'Client']
        const rows = data.map(email => [
          new Date(email.sentAt || email.createdAt).toLocaleDateString(),
          email.to,
          email.subject,
          email.status || 'draft',
          this.getClientById(email.clientId)?.name || 'Unknown'
        ])
        
        return [headers, ...rows]
      }
      
      return data
    },

    // Clear data
    clearAllDrafts() {
      this.drafts = []
    },

    clearSentEmails() {
      this.sentEmails = []
    },

    // Import/Export templates
    importTemplates(templates) {
      templates.forEach(template => {
        this.saveTemplate({
          ...template,
          id: undefined // Let saveTemplate generate new ID
        })
      })
    },

    exportTemplates() {
      return this.templates
    }
  }
})