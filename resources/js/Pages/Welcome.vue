<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

// Form setup using Inertia's useForm for Laravel integration
const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const loading = ref(false);
const showPassword = ref(false);

// Form submission handler
const submit = () => {
  loading.value = true;
  
  // Transform the data to avoid cross-origin object issues
  const formData = {
    email: form.email,
    password: form.password,
    remember: form.remember ? 'on' : '',
  };
  
  form.post(route('login'), {
    data: formData,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false;
      form.reset('password');
    },
  });
};

// Toggle password visibility
const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};
</script>

<template>
  <v-app>
    <v-main>
      <v-container fluid class="pa-0 fill-height">
        <v-row no-gutters class="fill-height">
          <!-- Left Side (Brand) -->
          <v-col cols="12" md="6" class="bg-primary d-flex align-center">
            <v-container>
              <v-row justify="center" align="center" class="h-100">
                <v-col cols="12" sm="8" md="10">
                  <div class="pa-4 pa-md-8 text-center text-md-left">
                    <!-- <h1 class="text-h2 font-weight-bold text-white mb-4">Solssa</h1> -->
                    <h2 class="text-h4 text-white mb-8">Customer Support</h2>
                    <v-btn
                      variant="outlined"
                      color="white"
                      size="large"
                      class="mt-4"
                      rounded="md"
                    >
                      We Tailor Your Coat →
                    </v-btn>
                  </div>
                </v-col>
              </v-row>
            </v-container>
          </v-col>

          <!-- Right Side (Login Form) -->
          <v-col cols="12" md="6" class="d-flex align-center justify-center">
            <v-card
              max-width="450"
              width="100%"
              class="mx-auto my-4 pa-4"
              elevation="2"
              rounded="lg"
            >
              <v-card-title class="text-h4 font-weight-bold mb-4">
                Log in
              </v-card-title>

              <v-card-text>
                <v-form @submit.prevent="submit">
                  <!-- Email Field -->
                  <v-text-field
                    v-model="form.email"
                    :error-messages="form.errors.email"
                    label="Email"
                    variant="outlined"
                    prepend-inner-icon="mdi-email-outline"
                    type="email"
                    autocomplete="email"
                    required
                    class="mb-2"
                  ></v-text-field>

                  <!-- Password Field -->
                  <v-text-field
                    v-model="form.password"
                    :error-messages="form.errors.password"
                    :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                    :type="showPassword ? 'text' : 'password'"
                    label="Password"
                    variant="outlined"
                    prepend-inner-icon="mdi-lock-outline"
                    @click:append-inner="togglePasswordVisibility"
                    autocomplete="current-password"
                    required
                    class="mb-4"
                  ></v-text-field>

                  <!-- Remember Me Checkbox -->
                  <v-checkbox
                    v-model="form.remember"
                    label="Remember me"
                    hide-details
                    class="mb-4"
                  ></v-checkbox>

                  <!-- Submit Button -->
                  <v-btn
                    @click="submit"
                    color="primary"
                    block
                    size="large"
                    :loading="form.processing || loading"
                    class="mb-4"
                  >
                    Log In
                  </v-btn>

                  <!-- Links -->
                  <div class="d-flex flex-column gap-2 mt-4">
                    <!-- <Link
                      :href="route('register')"
                      class="text-decoration-none"
                    >
                      <v-btn
                        variant="text"
                        color="primary"
                        class="px-0"
                        density="comfortable"
                      >
                        Sign Up
                      </v-btn>
                    </Link> -->
                    <Link
                      :href="route('password.request')"
                      class="text-decoration-none"
                    >
                      <v-btn
                        variant="text"
                        color="primary"
                        class="px-0"
                        density="comfortable"
                      >
                        Forgot your password?
                      </v-btn>
                    </Link>
                  </div>
                </v-form>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<style scoped>
.fill-height {
  height: 100vh;
}

.bg-primary {
  background-color: #2596be !important;
}

/* Override Vuetify's default styles if needed */
:deep(.v-btn) {
  text-transform: none;
}
</style>