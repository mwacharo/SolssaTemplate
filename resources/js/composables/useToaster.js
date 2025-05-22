import { reactive } from 'vue';

const state = reactive({
  show: false,
  message: '',
  color: 'success', // success, error, warning, info
  timeout: 4000,
});

function notify(msg, variant = 'success', timeout = 4000) {
  state.message = msg;
  state.color = variant;
  state.timeout = timeout;
  state.show = true;
}

export function useToaster() {
  return {
    state,
    notify,
  };
}
