<script setup>
import Badge from './ui/badge.vue'

defineProps({
    laravelVersion: String,
    phpVersion: String,
})
</script>

<template>
  <footer class="h-12 flex items-center justify-between px-4 md:px-6 border-t border-border/20 bg-card/20 flex-shrink-0">
    <div class="flex items-center gap-3 text-xs text-muted-foreground">
      <span>Powered by Laravel Loop</span>
      <span>•</span>
      <a href="#" class="hover:text-foreground transition-colors">
        GitHub
      </a>
      <span>•</span>
      <a href="#" class="hover:text-foreground transition-colors">
        Docs
      </a>
    </div>
    
    <div class="flex items-center gap-2">
      <Badge variant="outline" class="text-xs px-2 py-0 h-5 text-green-600 border-green-600/20">
        <div class="w-1.5 h-1.5 bg-green-600 rounded-full mr-1"></div>
        API Online
      </Badge>
      <div class="text-xs text-muted-foreground">
        Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})
      </div>
    </div>
  </footer>
</template>