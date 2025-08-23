<script setup>
import Badge from './ui/badge.vue';
</script>

<template>
  <div class="mb-4 text-center">
    <div class="mb-2 flex justify-center">
      <!-- <Badge variant="outline" class="mb-2 px-2 py-1 text-xs">
        ✨ Now with 2 Prompts
      </Badge> -->
    </div>
    <h1
      class="mb-2 bg-gradient-to-r from-primary via-primary to-primary/70 bg-clip-text text-xl font-medium text-transparent md:text-2xl"
    >
      Prompt Library
    </h1>
    <p class="mx-auto mb-1 max-w-2xl text-sm text-muted-foreground">
      Access curated AI prompts through Claude Desktop, Cursor, and other MCP
      clients.
    </p>
    <p class="mx-auto max-w-xl text-xs text-muted-foreground">
      One connection, all your prompts. Compatible with any MCP-enabled AI tool.
    </p>
  </div>
</template>
