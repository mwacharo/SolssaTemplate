// =============================================
// PINIA STORE - stores/reportStore.js
// =============================================

import { defineStore } from 'pinia'

export const useReportStore = defineStore('report', {
  state: () => ({
    selectedReportType: '',
    filters: {},
    reportData: null,
    loading: false,
    summary: null,
    availableReports: {
      'call-voice': {
        name: 'Call & Voice Reports',
        subTypes: [
          { id: 'call-volume', name: 'Call Volume Report' },
          { id: 'call-outcome', name: 'Call Outcome Report' },
          { id: 'agent-performance', name: 'Agent Performance' },
          { id: 'ivr-analysis', name: 'IVR Path Analysis' },
          { id: 'call-sentiment', name: 'Call Sentiment & Transcription' }
        ]
      },
      'sms': {
        name: 'SMS Reports',
        subTypes: [
          { id: 'sms-delivery', name: 'SMS Delivery Report' },
          { id: 'sms-engagement', name: 'SMS Engagement' },
          { id: 'sms-campaigns', name: 'Campaign Performance' }
        ]
      },
      'whatsapp': {
        name: 'WhatsApp Reports',
        subTypes: [
          { id: 'wa-volume', name: 'Message Volume' },
          { id: 'wa-response', name: 'Response Time' },
          { id: 'wa-templates', name: 'Template Usage' },
          { id: 'wa-engagement', name: 'Engagement by Message Type' }
        ]
      },
      'telegram': {
        name: 'Telegram Reports',
        subTypes: [
          { id: 'tg-trends', name: 'Message Trends' },
          { id: 'tg-bot', name: 'Bot Interaction Report' },
          { id: 'tg-response', name: 'Response Efficiency' }
        ]
      },
      'email': {
        name: 'Email Reports',
        subTypes: [
          { id: 'email-campaigns', name: 'Campaign Analytics' },
          { id: 'email-response', name: 'Response Reports' },
          { id: 'email-deliverability', name: 'Spam & Deliverability' }
        ]
      },
      'ticketing': {
        name: 'Ticketing Reports',
        subTypes: [
          { id: 'ticket-volume', name: 'Ticket Volume & Status' },
          { id: 'ticket-resolution', name: 'Resolution Time' },
          { id: 'ticket-agent', name: 'Agent Performance' },
          { id: 'ticket-categories', name: 'Issue Category Breakdown' }
        ]
      },
      'omnichannel': {
        name: 'Cross-Channel Reports',
        subTypes: [
          { id: 'customer-journey', name: 'Customer Journey Report' },
          { id: 'channel-effectiveness', name: 'Channel Effectiveness' },
          { id: 'channel-cost', name: 'Cost per Channel' },
          { id: 'customer-satisfaction', name: 'Customer Satisfaction (CSAT)' }
        ]
      }
    }
  }),

  getters: {
    hasReportData: (state) => state.reportData && state.reportData.length > 0,
    selectedReportName: (state) => {
      for (const category of Object.values(state.availableReports)) {
        const subType = category.subTypes.find(sub => sub.id === state.selectedReportType)
        if (subType) return subType.name
      }
      return ''
    },
    isValidDateRange: (state) => {
      const { start, end } = state.filters.dateRange || {}
      return start && end && new Date(start) <= new Date(end)
    }
  },

  actions: {
    setReportType(type) {
      this.selectedReportType = type
      this.filters = this.getDefaultFilters(type)
      this.reportData = null
      this.summary = null
    },

    updateFilter(key, value) {
      if (key.includes('.')) {
        const [parent, child] = key.split('.')
        if (!this.filters[parent]) this.filters[parent] = {}
        this.filters[parent][child] = value
      } else {
        this.filters[key] = value
      }
    },

    getDefaultFilters(reportType) {
      const today = new Date()
      const lastWeek = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000)
      
      const commonFilters = {
        dateRange: { 
          start: lastWeek.toISOString().split('T')[0], 
          end: today.toISOString().split('T')[0] 
        },
        department: '',
        agent: ''
      }
      
      const specificFilters = {
        'call-volume': { ...commonFilters, callType: 'all', callDirection: 'all' },
        'call-outcome': { ...commonFilters, outcome: 'all' },
        'sms-delivery': { ...commonFilters, provider: 'all', messageType: 'all' },
        'sms-campaigns': { ...commonFilters, campaignStatus: 'all' },
        'wa-volume': { ...commonFilters, messageType: 'all', conversationType: 'all' },
        'wa-templates': { ...commonFilters, templateStatus: 'all' },
        'email-campaigns': { ...commonFilters, campaignId: '', campaignType: 'all' },
        'ticket-volume': { ...commonFilters, priority: 'all', status: 'all', channel: 'all' },
        'ticket-resolution': { ...commonFilters, slaStatus: 'all' }
      }
      
      return specificFilters[reportType] || commonFilters
    },

    async generateReport() {
      if (!this.selectedReportType) {
        throw new Error('Please select a report type')
      }

      if (!this.isValidDateRange) {
        throw new Error('Please select a valid date range')
      }

      this.loading = true
      try {
        // In production, replace with actual API call
        const response = await this.fetchReportData()
        this.reportData = response.data
        this.summary = response.summary
      } catch (error) {
        console.error('Failed to generate report:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchReportData() {
      // Simulate API call - replace with actual endpoint
      await new Promise(resolve => setTimeout(resolve, 1500))
      
      // In production:
      // const response = await fetch('/api/reports/generate', {
      //   method: 'POST',
      //   headers: {
      //     'Content-Type': 'application/json',
      //     'Authorization': `Bearer ${token}`
      //   },
      //   body: JSON.stringify({
      //     reportType: this.selectedReportType,
      //     filters: this.filters
      //   })
      // })
      // return response.json()

      return this.getMockData()
    },

    getMockData() {
      const reportType = this.selectedReportType
      
      const mockDataMap = {
        'call-volume': {
          data: [
            { agent: 'John Doe', totalCalls: 45, inbound: 30, outbound: 15, avgHandleTime: '3:24', date: '2024-08-13' },
            { agent: 'Jane Smith', totalCalls: 52, inbound: 35, outbound: 17, avgHandleTime: '2:58', date: '2024-08-13' },
            { agent: 'Mike Johnson', totalCalls: 38, inbound: 25, outbound: 13, avgHandleTime: '4:12', date: '2024-08-13' },
            { agent: 'Sarah Wilson', totalCalls: 41, inbound: 28, outbound: 13, avgHandleTime: '3:45', date: '2024-08-13' }
          ],
          summary: {
            totalCalls: 176,
            avgHandleTime: '3:35',
            inboundCalls: 118,
            outboundCalls: 58
          }
        },
        'sms-delivery': {
          data: [
            { campaign: 'Promo Campaign A', sent: 1500, delivered: 1485, failed: 15, deliveryRate: '99%', cost: '$45.00' },
            { campaign: 'Survey Campaign', sent: 800, delivered: 792, failed: 8, deliveryRate: '99%', cost: '$24.00' },
            { campaign: 'Alert Campaign', sent: 2200, delivered: 2156, failed: 44, deliveryRate: '98%', cost: '$66.00' },
            { campaign: 'Welcome Series', sent: 950, delivered: 931, failed: 19, deliveryRate: '98%', cost: '$28.50' }
          ],
          summary: {
            totalSent: 5450,
            totalDelivered: 5364,
            avgDeliveryRate: '98.4%',
            totalCost: '$163.50'
          }
        },
        'wa-templates': {
          data: [
            { template: 'Welcome Message', sent: 320, delivered: 318, read: 298, readRate: '93%', clicks: 45 },
            { template: 'Order Confirmation', sent: 156, delivered: 156, read: 142, readRate: '91%', clicks: 23 },
            { template: 'Support Response', sent: 89, delivered: 87, read: 81, readRate: '93%', clicks: 12 },
            { template: 'Payment Reminder', sent: 243, delivered: 240, read: 215, readRate: '90%', clicks: 67 }
          ],
          summary: {
            totalSent: 808,
            totalDelivered: 801,
            avgReadRate: '91.8%',
            totalClicks: 147
          }
        },
        'ticket-volume': {
          data: [
            { date: '2024-08-13', created: 25, resolved: 22, pending: 8, escalated: 2, avgResolutionTime: '2.5h' },
            { date: '2024-08-12', created: 31, resolved: 28, pending: 5, escalated: 1, avgResolutionTime: '3.1h' },
            { date: '2024-08-11', created: 28, resolved: 30, pending: 3, escalated: 0, avgResolutionTime: '1.8h' },
            { date: '2024-08-10', created: 22, resolved: 24, pending: 4, escalated: 1, avgResolutionTime: '2.9h' }
          ],
          summary: {
            totalCreated: 106,
            totalResolved: 104,
            avgResolutionTime: '2.6h',
            slaCompliance: '94%'
          }
        }
      }

      return mockDataMap[reportType] || {
        data: [
          { metric: 'Total Interactions', value: 1250, change: '+12%', trend: 'up' },
          { metric: 'Avg Response Time', value: '2:34', change: '-8%', trend: 'down' },
          { metric: 'Customer Satisfaction', value: '4.2/5', change: '+0.3', trend: 'up' },
          { metric: 'Resolution Rate', value: '87%', change: '+5%', trend: 'up' }
        ],
        summary: {
          totalMetrics: 4,
          overallPerformance: 'Good',
          trend: 'Improving',
          period: '7 days'
        }
      }
    },

    async exportToExcel() {
      if (!this.reportData || this.reportData.length === 0) {
        throw new Error('No data to export')
      }

      try {
        const csv = this.generateCSV()
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        
        link.href = url
        link.download = `${this.selectedReportType}-${new Date().toISOString().split('T')[0]}.csv`
        link.style.visibility = 'hidden'
        
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
      } catch (error) {
        console.error('Export failed:', error)
        throw error
      }
    },

    async exportToPDF() {
      // In production, integrate with jsPDF or server-side PDF generation
      try {
        const reportContent = {
          title: this.selectedReportName,
          data: this.reportData,
          summary: this.summary,
          filters: this.filters,
          generatedAt: new Date().toISOString()
        }

        // Option 1: Server-side PDF generation
        // const response = await fetch('/api/reports/pdf', {
        //   method: 'POST',
        //   headers: { 'Content-Type': 'application/json' },
        //   body: JSON.stringify(reportContent)
        // })
        // const blob = await response.blob()
        // const url = window.URL.createObjectURL(blob)
        // const link = document.createElement('a')
        // link.href = url
        // link.download = `${this.selectedReportType}-report.pdf`
        // link.click()

        // For demo - show alert
        alert(`PDF export would generate a report for: ${this.selectedReportName}\nData points: ${this.reportData.length}\nImplement with jsPDF or server-side generation.`)
      } catch (error) {
        console.error('PDF export failed:', error)
        throw error
      }
    },

    generateCSV() {
      if (!this.reportData || this.reportData.length === 0) return ''
      
      const headers = Object.keys(this.reportData[0])
      const csvHeaders = headers.map(header => 
        header.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase())
      ).join(',')
      
      const csvRows = this.reportData.map(row => 
        headers.map(header => {
          const value = row[header]
          return typeof value === 'string' && value.includes(',') 
            ? `"${value}"` 
            : value
        }).join(',')
      ).join('\n')
      
      return csvHeaders + '\n' + csvRows
    },

    resetStore() {
      this.selectedReportType = ''
      this.filters = {}
      this.reportData = null
      this.summary = null
      this.loading = false
    }
  }
})