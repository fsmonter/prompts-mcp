<script setup>
import Button from './ui/button.vue'
import Input from './ui/input.vue'
import { Link } from '@inertiajs/vue3'
</script>

<template>
  <div class="space-y-3">
    <!-- Search Preview -->
    <div class="max-w-md mx-auto">
      <div class="relative">
        <Input 
          placeholder="Search patterns... (try 'analyze' or 'write')"
          class="text-xs h-7 pr-12 bg-input-background border-border/50"
        />
        <div class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-muted-foreground">
          ⌘K
        </div>
      </div>
      <div class="text-xs text-muted-foreground text-center mt-1">
        Instant access to 247+ patterns
      </div>
    </div>

    <!-- Main CTA Buttons -->
    <div class="flex flex-col sm:flex-row items-center justify-center gap-2">
      <Link :href="route('prompts.index')" class="w-full sm:w-auto">
        <Button size="sm" class="w-full sm:w-auto min-w-[100px] h-7 text-xs">
          Get Started
        </Button>
      </Link>
      <Button variant="outline" size="sm" class="w-full sm:w-auto min-w-[100px] h-7 text-xs">
        View Demo
      </Button>
    </div>
  </div>
</template>