<script setup>
import Button from './ui/button.vue'
import Card from './ui/card.vue'
import { Link } from '@inertiajs/vue3'

const quickActions = [
  {
    title: "Setup MCP",
    description: "5-minute integration guide",
    action: "Start Setup",
    variant: "default"
  },
  {
    title: "Browse Library", 
    description: "Explore all patterns",
    action: "Browse",
    variant: "outline"
  },
  {
    title: "API Access",
    description: "Developer documentation",
    action: "View Docs",
    variant: "outline"
  },
  {
    title: "Create Prompt",
    description: "Build custom patterns",
    action: "Create",
    variant: "secondary"
  }
]
</script>

<template>
  <div class="mb-4">
    <h3 class="text-sm font-medium text-foreground mb-2">Quick Actions</h3>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
      <Card 
        v-for="(action, index) in quickActions" 
        :key="index" 
        class="p-2 border border-border/30 bg-card/30 hover:bg-card/60 transition-all group"
      >
        <div class="text-xs font-medium text-foreground mb-1">
          {{ action.title }}
        </div>
        <div class="text-xs text-muted-foreground mb-2 leading-relaxed">
          {{ action.description }}
        </div>
        <Link v-if="action.action === 'Browse'" :href="route('prompts.index')" class="w-full">
          <Button 
            size="sm" 
            :variant="action.variant"
            class="w-full h-6 text-xs"
          >
            {{ action.action }}
          </Button>
        </Link>
        <Button 
          v-else
          size="sm" 
          :variant="action.variant"
          class="w-full h-6 text-xs"
        >
          {{ action.action }}
        </Button>
      </Card>
    </div>
  </div>
</template>