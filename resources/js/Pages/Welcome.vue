<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import Header from '@/Components/Header.vue';
import HeroSection from '@/Components/HeroSection.vue';
import StatsSection from '@/Components/StatsSection.vue';
import PromptPreview from '@/Components/PromptPreview.vue';
import QuickActions from '@/Components/QuickActions.vue';
import CallToAction from '@/Components/CallToAction.vue';
import Footer from '@/Components/Footer.vue';

const props = defineProps({
  canLogin: {
    type: Boolean,
  },
  canRegister: {
    type: Boolean,
  },
  laravelVersion: {
    type: String,
    required: true,
  },
  phpVersion: {
    type: String,
    required: true,
  },
  auth: {
    type: Object,
  },
});

const isDark = ref(false);

function toggleDarkMode() {
  isDark.value = !isDark.value;
  document.documentElement.classList.toggle('dark');
}
</script>

<template>
  <Head title="Prompt Library - MCP Server" />
  <div
    class="flex h-screen flex-col overflow-hidden bg-background text-foreground"
  >
    <Header :canLogin="canLogin" :canRegister="canRegister" :auth="auth" />

    <main
      class="flex flex-1 items-center justify-center overflow-y-auto px-4 py-2 md:px-6"
    >
      <div class="w-full max-w-4xl space-y-4">
        <HeroSection />
        <!-- <StatsSection /> -->
        <PromptPreview />
        <QuickActions />
        <CallToAction />
      </div>
    </main>

    <Footer :laravelVersion="laravelVersion" :phpVersion="phpVersion" />
  </div>
</template>
