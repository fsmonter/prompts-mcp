<script setup>
import Card from './ui/card.vue'
import Badge from './ui/badge.vue'
import { Link } from '@inertiajs/vue3'

const promptExamples = [
  {
    name: "analyze_claims",
    description: "Extract and analyze claims from any text with reasoning",
    category: "Analysis",
    popular: true
  },
  {
    name: "create_logo",
    description: "Generate detailed logo design briefs and concepts",
    category: "Design"
  },
  {
    name: "extract_wisdom", 
    description: "Distill key insights and wisdom from complex content",
    category: "Learning",
    popular: true
  },
  {
    name: "write_essay",
    description: "Structure and write compelling essays on any topic",
    category: "Writing"
  }
]
</script>

<template>
  <div class="mb-4">
    <div class="flex items-center justify-between mb-2">
      <h3 class="text-sm font-medium text-foreground">Popular Patterns</h3>
      <Link :href="route('prompts.index')" class="text-xs text-muted-foreground hover:text-foreground transition-colors">
        View all →
      </Link>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
      <Card 
        v-for="(prompt, index) in promptExamples" 
        :key="index" 
        class="p-2 border border-border/30 bg-card/30 hover:bg-card/60 transition-all hover:shadow-sm group cursor-pointer"
      >
        <div class="flex items-start justify-between mb-1">
          <div class="flex items-center gap-2">
            <code class="text-xs bg-muted px-1.5 py-0.5 rounded font-mono text-foreground">
              {{ prompt.name }}
            </code>
            <Badge v-if="prompt.popular" variant="secondary" class="text-xs px-1.5 py-0 h-4">
              Popular
            </Badge>
          </div>
          <div class="text-xs text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity">
            Try it
          </div>
        </div>
        <p class="text-xs text-muted-foreground leading-relaxed mb-1">
          {{ prompt.description }}
        </p>
        <div class="text-xs text-muted-foreground">
          {{ prompt.category }}
        </div>
      </Card>
    </div>
  </div>
</template>