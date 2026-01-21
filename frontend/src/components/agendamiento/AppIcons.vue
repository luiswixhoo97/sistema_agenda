<script setup lang="ts">
defineProps<{
  name: string
  size?: number
  strokeWidth?: number
}>()
</script>

<template>
  <!-- User / Person -->
  <svg v-if="name === 'user'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
    <circle cx="12" cy="7" r="4"></circle>
  </svg>

  <!-- Users / Multiple people -->
  <svg v-else-if="name === 'users'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
    <circle cx="9" cy="7" r="4"></circle>
    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
  </svg>

  <!-- Phone -->
  <svg v-else-if="name === 'phone'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
  </svg>

  <!-- Mail / Email -->
  <svg v-else-if="name === 'mail'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
    <polyline points="22,6 12,13 2,6"></polyline>
  </svg>

  <!-- Calendar -->
  <svg v-else-if="name === 'calendar'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
    <line x1="16" y1="2" x2="16" y2="6"></line>
    <line x1="8" y1="2" x2="8" y2="6"></line>
    <line x1="3" y1="10" x2="21" y2="10"></line>
  </svg>

  <!-- Clock / Time -->
  <svg v-else-if="name === 'clock'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="10"></circle>
    <polyline points="12 6 12 12 16 14"></polyline>
  </svg>

  <!-- Check -->
  <svg v-else-if="name === 'check'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <polyline points="20 6 9 17 4 12"></polyline>
  </svg>

  <!-- Check Circle -->
  <svg v-else-if="name === 'check-circle'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
    <polyline points="22 4 12 14.01 9 11.01"></polyline>
  </svg>

  <!-- X / Close -->
  <svg v-else-if="name === 'x'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <line x1="18" y1="6" x2="6" y2="18"></line>
    <line x1="6" y1="6" x2="18" y2="18"></line>
  </svg>

  <!-- X Circle -->
  <svg v-else-if="name === 'x-circle'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="10"></circle>
    <line x1="15" y1="9" x2="9" y2="15"></line>
    <line x1="9" y1="9" x2="15" y2="15"></line>
  </svg>

  <!-- Chevron Left -->
  <svg v-else-if="name === 'chevron-left'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <polyline points="15 18 9 12 15 6"></polyline>
  </svg>

  <!-- Chevron Right -->
  <svg v-else-if="name === 'chevron-right'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <polyline points="9 18 15 12 9 6"></polyline>
  </svg>

  <!-- Arrow Left -->
  <svg v-else-if="name === 'arrow-left'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <line x1="19" y1="12" x2="5" y2="12"></line>
    <polyline points="12 19 5 12 12 5"></polyline>
  </svg>

  <!-- Arrow Right -->
  <svg v-else-if="name === 'arrow-right'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <line x1="5" y1="12" x2="19" y2="12"></line>
    <polyline points="12 5 19 12 12 19"></polyline>
  </svg>

  <!-- Spa / Leaf / Service -->
  <svg v-else-if="name === 'spa'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M12 22c-4-2.5-6.5-5.5-7.5-8.5-1-3 0-6 2.5-8 2.5 2 5 3 7.5 3s5-1 7.5-3c2.5 2 3.5 5 2.5 8-1 3-3.5 6-7.5 8.5"></path>
    <path d="M12 22c0-6 3.5-10 3.5-10"></path>
  </svg>

  <!-- Star -->
  <svg v-else-if="name === 'star'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
  </svg>

  <!-- Star Filled -->
  <svg v-else-if="name === 'star-filled'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
  </svg>

  <!-- Sun -->
  <svg v-else-if="name === 'sun'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="5"></circle>
    <line x1="12" y1="1" x2="12" y2="3"></line>
    <line x1="12" y1="21" x2="12" y2="23"></line>
    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
    <line x1="1" y1="12" x2="3" y2="12"></line>
    <line x1="21" y1="12" x2="23" y2="12"></line>
    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
  </svg>

  <!-- Moon -->
  <svg v-else-if="name === 'moon'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
  </svg>

  <!-- Alert Circle / Info -->
  <svg v-else-if="name === 'alert-circle'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="10"></circle>
    <line x1="12" y1="8" x2="12" y2="12"></line>
    <line x1="12" y1="16" x2="12.01" y2="16"></line>
  </svg>

  <!-- Info -->
  <svg v-else-if="name === 'info'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="10"></circle>
    <line x1="12" y1="16" x2="12" y2="12"></line>
    <line x1="12" y1="8" x2="12.01" y2="8"></line>
  </svg>

  <!-- Shield -->
  <svg v-else-if="name === 'shield'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
  </svg>

  <!-- Shield Check -->
  <svg v-else-if="name === 'shield-check'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
    <polyline points="9 12 11 14 15 10"></polyline>
  </svg>

  <!-- Dollar Sign -->
  <svg v-else-if="name === 'dollar'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <line x1="12" y1="1" x2="12" y2="23"></line>
    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
  </svg>

  <!-- Credit Card -->
  <svg v-else-if="name === 'credit-card'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
    <line x1="1" y1="10" x2="23" y2="10"></line>
  </svg>

  <!-- Building / Bank -->
  <svg v-else-if="name === 'building'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M3 21h18"></path>
    <path d="M3 10h18"></path>
    <path d="M5 6l7-3 7 3"></path>
    <path d="M4 10v11"></path>
    <path d="M20 10v11"></path>
    <path d="M8 14v3"></path>
    <path d="M12 14v3"></path>
    <path d="M16 14v3"></path>
  </svg>

  <!-- Tag / Discount -->
  <svg v-else-if="name === 'tag'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
    <line x1="7" y1="7" x2="7.01" y2="7"></line>
  </svg>

  <!-- Gift -->
  <svg v-else-if="name === 'gift'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <polyline points="20 12 20 22 4 22 4 12"></polyline>
    <rect x="2" y="7" width="20" height="5"></rect>
    <line x1="12" y1="22" x2="12" y2="7"></line>
    <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path>
    <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path>
  </svg>

  <!-- Percent -->
  <svg v-else-if="name === 'percent'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <line x1="19" y1="5" x2="5" y2="19"></line>
    <circle cx="6.5" cy="6.5" r="2.5"></circle>
    <circle cx="17.5" cy="17.5" r="2.5"></circle>
  </svg>

  <!-- Scissors / Haircut -->
  <svg v-else-if="name === 'scissors'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="6" cy="6" r="3"></circle>
    <circle cx="6" cy="18" r="3"></circle>
    <line x1="20" y1="4" x2="8.12" y2="15.88"></line>
    <line x1="14.47" y1="14.48" x2="20" y2="20"></line>
    <line x1="8.12" y1="8.12" x2="12" y2="12"></line>
  </svg>

  <!-- Search -->
  <svg v-else-if="name === 'search'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="11" cy="11" r="8"></circle>
    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
  </svg>

  <!-- Settings / Gear -->
  <svg v-else-if="name === 'settings'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="3"></circle>
    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
  </svg>

  <!-- Refresh / Redo -->
  <svg v-else-if="name === 'refresh'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <polyline points="23 4 23 10 17 10"></polyline>
    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
  </svg>

  <!-- Plus -->
  <svg v-else-if="name === 'plus'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <line x1="12" y1="5" x2="12" y2="19"></line>
    <line x1="5" y1="12" x2="19" y2="12"></line>
  </svg>

  <!-- Minus -->
  <svg v-else-if="name === 'minus'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <line x1="5" y1="12" x2="19" y2="12"></line>
  </svg>

  <!-- Message / Chat -->
  <svg v-else-if="name === 'message'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
  </svg>

  <!-- WhatsApp -->
  <svg v-else-if="name === 'whatsapp'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
  </svg>

  <!-- Clipboard -->
  <svg v-else-if="name === 'clipboard'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
    <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
  </svg>

  <!-- Clipboard Check -->
  <svg v-else-if="name === 'clipboard-check'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
    <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
    <path d="M9 14l2 2 4-4"></path>
  </svg>

  <!-- Grid -->
  <svg v-else-if="name === 'grid'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <rect x="3" y="3" width="7" height="7"></rect>
    <rect x="14" y="3" width="7" height="7"></rect>
    <rect x="14" y="14" width="7" height="7"></rect>
    <rect x="3" y="14" width="7" height="7"></rect>
  </svg>

  <!-- Loader / Spinner -->
  <svg v-else-if="name === 'loader'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round" class="animate-spin">
    <line x1="12" y1="2" x2="12" y2="6"></line>
    <line x1="12" y1="18" x2="12" y2="22"></line>
    <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
    <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
    <line x1="2" y1="12" x2="6" y2="12"></line>
    <line x1="18" y1="12" x2="22" y2="12"></line>
    <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
    <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
  </svg>

  <!-- Award / Certificate -->
  <svg v-else-if="name === 'award'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="8" r="7"></circle>
    <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
  </svg>

  <!-- User Check -->
  <svg v-else-if="name === 'user-check'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
    <circle cx="8.5" cy="7" r="4"></circle>
    <polyline points="17 11 19 13 23 9"></polyline>
  </svg>

  <!-- Hourglass -->
  <svg v-else-if="name === 'hourglass'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M5 22h14"></path>
    <path d="M5 2h14"></path>
    <path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22"></path>
    <path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2"></path>
  </svg>

  <!-- Map Pin / Location -->
  <svg v-else-if="name === 'map-pin'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
    <circle cx="12" cy="10" r="3"></circle>
  </svg>

  <!-- Edit / Pencil -->
  <svg v-else-if="name === 'edit'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
  </svg>

  <!-- User Slash / No User -->
  <svg v-else-if="name === 'user-x'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
    <circle cx="8.5" cy="7" r="4"></circle>
    <line x1="18" y1="8" x2="23" y2="13"></line>
    <line x1="23" y1="8" x2="18" y2="13"></line>
  </svg>

  <!-- Lock -->
  <svg v-else-if="name === 'lock'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
  </svg>

  <!-- Piggy Bank / Savings -->
  <svg v-else-if="name === 'piggy-bank'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M19 5c-1.5 0-2.8 1.4-3 2-3.5-1.5-11-.3-11 5 0 1.8 0 3 2 4.5V20h4v-2h3v2h4v-4c1-.5 1.7-1 2-2h2v-4h-2c0-1-.5-1.5-1-2h0V5z"></path>
    <path d="M2 9v1c0 1.1.9 2 2 2h1"></path>
    <path d="M16 11h0"></path>
  </svg>

  <!-- Briefcase -->
  <svg v-else-if="name === 'briefcase'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
  </svg>

  <!-- Calendar Check -->
  <svg v-else-if="name === 'calendar-check'" :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
    <line x1="16" y1="2" x2="16" y2="6"></line>
    <line x1="8" y1="2" x2="8" y2="6"></line>
    <line x1="3" y1="10" x2="21" y2="10"></line>
    <path d="M9 16l2 2 4-4"></path>
  </svg>

  <!-- Fallback -->
  <svg v-else :width="size || 24" :height="size || 24" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth || 1.5" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="10"></circle>
    <line x1="12" y1="8" x2="12" y2="12"></line>
    <line x1="12" y1="16" x2="12.01" y2="16"></line>
  </svg>
</template>

<style scoped>
svg {
  display: inline-block;
  vertical-align: middle;
  flex-shrink: 0;
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
