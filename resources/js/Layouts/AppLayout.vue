<script setup>
import { ref } from 'vue';
import { computed } from 'vue'


import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import { useNavStore } from '@/stores/nav' 



defineProps({
    title: String,
});

const navStore = useNavStore()


// Assume userRole, userPlan, and enabledFeatures come from auth store or props
const userRole = 'admin' // e.g., from authStore.user.role
const userPlan = 'Pro'
const enabledFeatures = ['reports', 'billing', 'notifications','team-management','integrations',''] // e.g., from authStore.user.features

const navItems = computed(() =>
  navStore.filteredNavItems(userRole, userPlan, enabledFeatures)
)

// Navigation drawer control
const drawer = ref(true);

// Dark mode toggle
const darkMode = ref(false);

const toggleDarkMode = () => {
  darkMode.value = !darkMode.value;
};

// Notifications
const notifications = ref([
  { id: 1, text: 'New support ticket opened', read: false },
  { id: 2, text: 'Customer waiting for callback', read: false },
  { id: 3, text: 'Weekly report ready', read: true }
]);

const unreadNotifications = () => {
  return notifications.value.filter(n => !n.read).length;
};

const notificationMenu = ref(false);

const markAsRead = (id) => {
  const notification = notifications.value.find(n => n.id === id);
  if (notification) {
    notification.read = true;
  }
};

const markAllAsRead = () => {
  notifications.value.forEach(n => n.read = true);
};

// Team management
const switchToTeam = (team) => {
    router.put(route('current-team.update'), {
        team_id: team.id,
    }, {
        preserveState: false,
    });
};

// Logout function
const logout = () => {
    router.post(route('logout'));
};

// Items for navigation menu
// const navItems = [
//   {
//     title: 'Dashboard',
//     route: 'dashboard',
//     icon: 'mdi-view-dashboard',
//   },

  
// ];
</script>

<template>
  <v-app :theme="darkMode ? 'dark' : 'light'">





    <!-- Navigation Drawer -->
    <v-navigation-drawer v-model="drawer" app permanent :rail="!drawer" color="primary" :dark="true">
      <v-list>
        <v-list-item class="px-2">
          <template v-slot:prepend>
            <ApplicationMark height="32" />
          </template>
          <v-list-item-title class="text-h6">
            Solssa
          </v-list-item-title>
          <v-list-item-subtitle>
            We tailor your coat
          </v-list-item-subtitle>
        </v-list-item>

        <v-divider class="my-2"></v-divider>
        
        <!-- Main Navigation -->
        <v-list-item 
          v-for="item in navItems"
          :key="item.title"
          :href="route(item.route)"
          :active="route().current(item.route)"
          link
          class="mb-2"
        >
          <template v-slot:prepend>
            <v-icon>{{ item.icon }}</v-icon>
          </template>
          <v-list-item-title>{{ item.title }}</v-list-item-title>
        </v-list-item>
      </v-list>
      
      <template v-slot:append>
        <!-- User Profile at Bottom -->
        <div class="pa-2">
          <v-list-item>
            <template v-slot:prepend>
              <v-avatar size="40">
                <v-img 
                  :src="$page.props.auth.user?.profile_photo_url || 'https://cdn.vuetifyjs.com/images/lists/1.jpg'" 
                  :alt="$page.props.auth.user?.name || 'User'"
                ></v-img>
              </v-avatar>
            </template>
            <v-list-item-title>{{ $page.props.auth.user?.name || 'User' }}</v-list-item-title>
            <v-list-item-subtitle>{{ $page.props.auth.user?.email || 'user@example.com' }}</v-list-item-subtitle>
          </v-list-item>
        </div>
      </template>
    </v-navigation-drawer>
    <Head :title="title" />
    
    <Banner />
    
    <!-- App Bar -->
    <v-app-bar color="primary" :dark="darkMode" app flat>
      <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>
      
      <v-app-bar-title>
        <Link :href="route('dashboard')" class="text-decoration-none" :class="darkMode ? 'text-white' : 'text-primary'">
          <ApplicationMark class="mr-2" height="32" />
          {{ title }}
        </Link>



        <!-- /admin/users -->
      </v-app-bar-title>
      
      <v-spacer></v-spacer>
      
      <!-- Dark Mode Toggle -->
      <v-btn icon @click="toggleDarkMode">
        <v-icon>{{ darkMode ? 'mdi-weather-sunny' : 'mdi-weather-night' }}</v-icon>
      </v-btn>
      
      <!-- Notifications -->
      <v-menu v-model="notificationMenu" :close-on-content-click="false" offset-y>
        <template v-slot:activator="{ props }">
          <v-btn icon v-bind="props" class="mx-2">
            <v-badge :content="unreadNotifications()" color="error" :value="unreadNotifications()">
              <v-icon>mdi-bell</v-icon>
            </v-badge>
          </v-btn>
        </template>
        <v-card min-width="300">
          <v-card-title class="d-flex align-center">
            Notifications
            <v-spacer></v-spacer>
            <v-btn v-if="unreadNotifications()" variant="text" size="small" @click="markAllAsRead">
              Mark all as read
            </v-btn>
          </v-card-title>
          <v-divider></v-divider>
          <v-list>
            <v-list-item v-for="notification in notifications" :key="notification.id" @click="markAsRead(notification.id)">
              <v-list-item-title :class="{ 'font-weight-bold': !notification.read }">
                {{ notification.text }}
              </v-list-item-title>
              <template v-slot:append>
                <v-icon v-if="!notification.read" color="primary" size="small">mdi-circle</v-icon>
              </template>
            </v-list-item>
            <v-list-item v-if="!notifications.length">
              <v-list-item-title>No notifications</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-card>
      </v-menu>
      
      <!-- Teams Dropdown -->
      <!-- <v-menu v-if="$page.props.jetstream.hasTeamFeatures" offset-y>
        <template v-slot:activator="{ props }">
          <v-btn variant="text" v-bind="props">
            {{ $page.props.auth.user.current_team.name }}
            <v-icon right>mdi-chevron-down</v-icon>
          </v-btn>
        </template>
        <v-list>
          <v-list-subheader>Manage Team</v-list-subheader>
          
          <v-list-item :href="route('teams.show', $page.props.auth.user.current_team)" link>
            <v-list-item-title>Team Settings</v-list-item-title>
          </v-list-item>
          
          <v-list-item v-if="$page.props.jetstream.canCreateTeams" :href="route('teams.create')" link>
            <v-list-item-title>Create New Team</v-list-item-title>
          </v-list-item>
          
          <v-divider v-if="$page.props.auth.user.all_teams.length > 1"></v-divider>
          
          <v-list-subheader v-if="$page.props.auth.user.all_teams.length > 1">
            Switch Teams
          </v-list-subheader>
          
          <v-list-item 
            v-for="team in $page.props.auth.user.all_teams" 
            :key="team.id" 
            @click="switchToTeam(team)"
            link
          >
            <template v-slot:prepend>
              <v-icon v-if="team.id == $page.props.auth.user.current_team_id" color="success">
                mdi-check-circle
              </v-icon>
            </template>
            <v-list-item-title>{{ team.name }}</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu> -->
      
      <!-- User Menu -->
      <v-menu offset-y>
        <template v-slot:activator="{ props }">
          <v-btn variant="text" v-bind="props" class="ml-2">
            <v-avatar v-if="$page.props.jetstream.managesProfilePhotos" size="32" class="mr-2">
              <v-img :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name"></v-img>
            </v-avatar>
            {{ $page.props.auth.user.name }}
            <v-icon right>mdi-chevron-down</v-icon>
          </v-btn>
        </template>
        <v-list>
          <v-list-subheader>Manage Account</v-list-subheader>
          
          <v-list-item :href="route('profile.show')" link>
            <v-list-item-title>Profile</v-list-item-title>
          </v-list-item>
          
          <v-list-item v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')" link>
            <v-list-item-title>API Tokens</v-list-item-title>
          </v-list-item>
          
          <v-divider></v-divider>
          
          <v-list-item @click="logout" link>
            <v-list-item-title>Log Out</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>
    </v-app-bar>
    
    
    <!-- Page Content -->
    <v-main>
      <!-- Page Heading -->
      <v-container v-if="$slots.header" class="py-6">
        <slot name="header" />
      </v-container>
      
      <!-- Main Content -->
      <v-container fluid>
        <slot />
      </v-container>
    </v-main>
    
    <!-- Footer -->
    <v-footer app>
      <div class="text-center w-100">
        © {{ new Date().getFullYear() }} - Solssa     </div>
    </v-footer>
  </v-app>
</template>