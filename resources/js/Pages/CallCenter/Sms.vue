<template>
    <v-dialog v-model="showNewMessageDialog" max-width="600">
    <v-card>
      <v-card-title>
        <span class="text-h6">Send SMS Message</span>
      </v-card-title>
      
      <v-card-text>
        <!-- Error message display -->
        <v-alert v-if="errorMessage" type="error" class="mb-3">
          {{ errorMessage }}
        </v-alert>

        <!-- Recipients -->
        <v-text-field
          v-model="selectedContactId"
          label="Recipient Phone Number"
          type="text"
          placeholder="Enter phone number"
          :disabled="loading.contacts"
          class="mb-3"
        />

        <!-- Template Select -->
        <v-select 
          v-model="selectedTemplate" 
          :items="allTemplates" 
          item-title="name" 
          item-value="id"
          label="Select Template" 
          return-object 
          :disabled="loading.templates"
          :loading="loading.templates" 
          @update:model-value="store.onTemplateSelect" 
          class="mt-3" 
          clearable 
        />

        <!-- Message Preview -->
        <v-textarea 
          v-model="messageText" 
          label="Message" 
          rows="4" 
          auto-grow 
          class="mt-3"
          placeholder="Type your message or select a template above" 
        />
      </v-card-text>

      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn 
          color="primary" 
          :loading="loading.sending" 
          @click="sendMessage"
          :disabled="!messageText.trim()"
        >
          Send
        </v-btn>
        <v-btn text @click="closeDialog">Cancel</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

</template>
<script setup>
import { ref, computed, onMounted, toRefs } from 'vue';
import { useSmsStore } from '@/stores/smsStore';
import { useConversationStore } from '@/stores/useConversationStore';
import { useWhatsAppStore } from '@/stores/whatsappStore';
import { useAuthStore } from '@/stores/auth';
import WhatsApp from './WhatsApp.vue';

const auth = useAuthStore();
const userId = computed(() => auth.user?.id);

const store = useSmsStore();
const whatsappStore = useWhatsAppStore();

// Use the getter to get templates reactively
const allTemplates = computed(() => whatsappStore.allTemplates);

const {
  validWhatsappContacts,
  selectedContacts,
  selectedTemplate,
  messageText,
  showNewMessageDialog,
  selectedContactId,
  errorMessage,
  loading
} = toRefs(store);

// Send SMS function
const sendSms = (item) => {
  console.log('sendSms called with:', JSON.stringify(item));

  if (item && item.client?.phone_number) {
    store.openDialog(item.client.phone_number);
  } else {
    store.errorMessage = 'Recipient phone number is required.';
  }
};

// Send message function
const sendMessage = async () => {
  try {
    await store.sendSmsMessage();
    console.log('Message sent successfully');
  } catch (error) {
    console.error('Failed to send message:', error);
  }
};

// Close dialog
const closeDialog = () => {
  store.closeDialog();
};

// Clear form
const clearForm = () => {
  store.resetForm();
};

// Fetch templates on mount
onMounted(async () => {
  try {
    console.log('Fetching templates...');
    await whatsappStore.loadTemplates();
    console.log('Templates fetched:', whatsappStore.templates);
  } catch (error) {
    console.error('Failed to fetch templates:', error);
  }
});
</script>
