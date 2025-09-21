<template>
  <div v-if="isOpen" class="dialog-overlay" @click="closeDialog">
    <div class="dialog-container" @click.stop>
      <div class="dialog-header">
        <h2>Edit Stock for "{{ productName }}"</h2>
      </div>
      
      <div class="dialog-content">
        <!-- Stock Stats Cards -->
        <div class="stock-stats">
          <div class="stat-card">
            <div class="stat-label">Total received: {{ totalReceived }}</div>
            <div class="stat-value">
              <span class="home-icon">🏠</span>
              <span class="flag-icon">🏁</span>
              <span class="value">{{ totalReceived }}</span>
            </div>
          </div>
          
          <div class="stat-card current">
            <div class="stat-label">Current: {{ currentStock }}</div>
            <div class="stat-value">
              <span class="home-icon">🏠</span>
              <span class="flag-icon">🏁</span>
              <span class="value">{{ currentStock }}</span>
            </div>
          </div>
          
          <div class="stat-card defected">
            <div class="stat-label">Total defected: {{ defectedStock }}</div>
            <div class="stat-value">
              <span class="home-icon">🏠</span>
              <span class="flag-icon">🏁</span>
              <span class="value">{{ defectedStock }}</span>
            </div>
          </div>
        </div>

        <!-- Form Fields -->
        <div class="form-section">
          <div class="form-row">
            <div class="form-group">
              <label for="stockType">STOCK TYPE</label>
              <select id="stockType" v-model="formData.stockType" class="form-select">
                <option value="current">current</option>
                <option value="committed">committed</option>
                <option value="defected">defected</option>
                <option value="historical">historical</option>
              </select>
            </div>
            
            <div class="form-group">
              <label for="operationType">OPERATION TYPE</label>
              <select id="operationType" v-model="formData.operationType" class="form-select">
                <option value="in">in</option>
                <option value="out">out</option>
                <option value="adjust">adjust</option>
              </select>
            </div>
            
            <div class="form-group">
              <label for="quantity">QUANTITY</label>
              <input 
                id="quantity" 
                type="number" 
                v-model.number="formData.current_stock" 
                class="form-input"
                min="0"
              >
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group full-width">
              <label for="warehouse">WAREHOUSE STOCK</label>
              <select id="warehouse" v-model="formData.warehouseId" class="form-select">
                <option value="">Select warehouse for stock</option>
                <option 
                  v-for="warehouse in warehouses" 
                  :key="warehouse.id" 
                  :value="warehouse.id"
                >
                  {{ warehouse.name }}
                </option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Dialog Footer -->
      <div class="dialog-footer">
        <button @click="closeDialog" class="btn-cancel">Cancel</button>
        <button @click="saveStock" class="btn-save">Save</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'

// Props
const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  productId: {
    type: [String, Number],
    required: true
  },
  productName: {
    type: String,
    default: 'Unknown Product'
  },
  warehouseId: {
    type: [String, Number],
    default: null
  },
  currentStock: {
    type: Number,
    default: 0
  },
  committedStock: {
    type: Number,
    default: 0
  },
  defectedStock: {
    type: Number,
    default: 0
  },
  historicalStock: {
    type: Number,
    default: 0
  },
  warehouses: {
    type: Array,
    default: () => []
  }
})

// Emits
const emit = defineEmits(['close', 'save'])

// Reactive form data
const formData = reactive({
  stockType: 'current',
  operationType: 'in',
  current_stock: '',
  warehouseId: props.warehouseId || ''
})

// Computed properties
const totalReceived = computed(() => {
  return props.currentStock + props.committedStock + props.historicalStock
})

// Methods
const closeDialog = () => {
  emit('close')
}


const saveStock = async () => {
    const stockData = {
        product_id: props.productId,
        warehouse_id: formData.warehouseId,
        current_stock: props.currentStock,
        committed_stock: props.committedStock,
        defected_stock: props.defectedStock,
        historical_stock: props.historicalStock,
        stock: {
            type: formData.operationType,
            stock_type: formData.stockType,
            current_stock: formData.current_stock
        }
    }

    try {
        const response = await axios.put(`/api/v1/products/${stockData.product_id}`, stockData)
        console.log('Stock updated successfully:', response.data)
        
        // Update local product data
        const updatedProduct = response.data.product
        if (updatedProduct.stocks && updatedProduct.stocks.length > 0) {
            const stock = updatedProduct.stocks[0]
            // If you have selectedProduct in parent, emit the updated stock
            emit('save', {
                current_stock: stock.current_stock,
                committed_stock: stock.committed_stock,
                defected_stock: stock.defected_stock
            })
        } else {
            emit('save', stockData)
        }
        closeDialog()
    } catch (error) {
        console.error('Error updating stock:', error)
        // Handle error (show toast, etc.)
    }
}

// Reset form when dialog opens
watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    formData.stockType = 'current'
    formData.operationType = 'in'
    formData.current_stock = 0
    formData.warehouseId = props.warehouseId || ''
  }
})
</script>

<style scoped>
.dialog-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.dialog-container {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.dialog-header {
  padding: 20px 24px;
  border-bottom: 1px solid #e5e7eb;
}

.dialog-header h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
}

.dialog-content {
  padding: 24px;
}

.stock-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}

.stat-card {
  padding: 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  background: #f9fafb;
}

.stat-card.current {
  border-color: #3b82f6;
  background: #eff6ff;
}

.stat-card.defected {
  border-color: #f59e0b;
  background: #fffbeb;
}

.stat-label {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 8px;
}

.stat-value {
  display: flex;
  align-items: center;
  gap: 8px;
}

.home-icon, .flag-icon {
  font-size: 14px;
}

.value {
  font-weight: 600;
  color: #1f2937;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-group label {
  font-size: 12px;
  font-weight: 600;
  color: #dc2626;
  margin-bottom: 8px;
  text-transform: uppercase;
}

.form-select, .form-input {
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  background: white;
  transition: border-color 0.2s;
}

.form-select:focus, .form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-select {
  cursor: pointer;
}

.form-input[type="number"] {
  -moz-appearance: textfield;
}

.form-input[type="number"]::-webkit-outer-spin-button,
.form-input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.dialog-footer {
  padding: 16px 24px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.btn-cancel, .btn-save {
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-cancel {
  background: #f9fafb;
  border: 1px solid #d1d5db;
  color: #374151;
}

.btn-cancel:hover {
  background: #f3f4f6;
}

.btn-save {
  background: #dc2626;
  border: 1px solid #dc2626;
  color: white;
}

.btn-save:hover {
  background: #b91c1c;
}

@media (max-width: 640px) {
  .dialog-container {
    width: 95%;
    margin: 20px;
  }
  
  .stock-stats {
    grid-template-columns: 1fr;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .dialog-footer {
    flex-direction: column-reverse;
  }
  
  .btn-cancel, .btn-save {
    width: 100%;
    padding: 12px;
  }
}
</style>