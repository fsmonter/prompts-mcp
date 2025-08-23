<script setup>
import Button from './ui/button.vue'
import Badge from './ui/badge.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    auth: Object,
})
</script>

<template>
  <header class="h-16 flex items-center justify-between px-4 md:px-6 border-b border-border/20 bg-card/30 flex-shrink-0">
    <div class="flex items-center gap-4">
      <div class="flex items-center gap-2">
        <div class="w-6 h-6 bg-gradient-to-br from-primary to-primary/70 rounded flex items-center justify-center">
          <div class="w-2 h-2 bg-background rounded-full"></div>
        </div>
        <h1 class="text-base font-medium">Prompt Library</h1>
        <Badge variant="secondary" class="text-xs px-1.5 py-0 h-4 ml-1">
          MCP
        </Badge>
      </div>
      <div class="hidden md:flex items-center gap-1 text-xs text-muted-foreground">
        <span>247 patterns</span>
        <span>•</span>
        <span>12 tools</span>
        <span>•</span>
        <span class="text-green-600">Online</span>
      </div>
    </div>
    
    <div class="flex items-center gap-2">
      <Button variant="ghost" size="sm" class="text-xs h-7">
        Docs
      </Button>
      <template v-if="canLogin">
        <Link v-if="auth?.user" :href="route('dashboard')">
          <Button variant="ghost" size="sm" class="text-xs h-7">
            Dashboard
          </Button>
        </Link>
        <template v-else>
          <Link :href="route('login')">
            <Button variant="ghost" size="sm" class="text-xs h-7">
              Login
            </Button>
          </Link>
          <Link v-if="canRegister" :href="route('register')">
            <Button size="sm" class="text-xs h-7">
              Register
            </Button>
          </Link>
        </template>
      </template>
      <Link v-else :href="route('prompts.index')">
        <Button size="sm" class="text-xs h-7">
          Get Started
        </Button>
      </Link>
    </div>
  </header>
</template>