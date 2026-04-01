<template>
    <AppLayout>
        <CompanyAdmin />
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue'
import { computed } from 'vue'
import CompanyAdmin from './CompanyAdmin.vue'

const user = computed(() => {
  const stored = localStorage.getItem('user')
  return stored ? JSON.parse(stored) : null
})
</script>
