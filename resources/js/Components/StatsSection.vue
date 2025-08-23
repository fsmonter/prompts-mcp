<script setup>
import Card from './ui/card.vue'

const stats = [
  { value: "247", label: "Fabric Patterns", sublabel: "Curated prompts" },
  { value: "1.2k", label: "Active Users", sublabel: "This month" },
  { value: "12", label: "AI Tools", sublabel: "MCP compatible" },
  { value: "99.9%", label: "Uptime", sublabel: "Reliable access" }
]
</script>

<template>
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 mb-4">
    <Card 
      v-for="(stat, index) in stats" 
      :key="index" 
      class="p-2 text-center border border-border/30 bg-card/50 hover:bg-card/80 transition-colors"
    >
      <div class="text-lg font-medium text-primary">{{ stat.value }}</div>
      <div class="text-xs font-medium text-foreground">{{ stat.label }}</div>
      <div v-if="stat.sublabel" class="text-xs text-muted-foreground">{{ stat.sublabel }}</div>
    </Card>
  </div>
</template>