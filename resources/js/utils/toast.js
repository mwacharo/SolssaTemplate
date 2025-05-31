import { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'

// Default reusable messages
const messages = {
  created: (item = 'Item') => `${item} created successfully!`,
  updated: (item = 'Item') => `${item} updated successfully!`,
  deleted: (item = 'Item') => `${item} deleted successfully!`,
  error: (action = 'perform this action') => `Failed to ${action}. Please try again.`,
  required: (field = 'Field') => `${field} is required.`,
}

export const notify = {
  success: (msg) => toast.success(msg),
  error: (msg) => toast.error(msg),
  info: (msg) => toast.info(msg),
  warn: (msg) => toast.warn(msg),

  // Semantic shortcuts
  created: (item) => toast.success(messages.created(item)),
  updated: (item) => toast.success(messages.updated(item)),
  deleted: (item) => toast.success(messages.deleted(item)),
  failed: (action) => toast.error(messages.error(action)),
  required: (field) => toast.error(messages.required(field)),
}
