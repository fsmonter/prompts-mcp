# Design Brief: Minimal Welcome Page for Prompt Library MCP Server

## Project Overview
Design a clean, minimal single-page welcome screen for a Laravel application that provides AI prompts through MCP (Model Context Protocol). The page should fit entirely on screen without scrolling. Add a 100vh height constraint to ensure all content is visible.

## Technical Stack
- **Frontend**: Vue 3 with Inertia.js  
- **Styling**: Tailwind CSS
- **File**: `/resources/js/Pages/Welcome.vue`

## Design Requirements

### Layout Constraints
- **Single viewport height** - Everything must fit on one screen (100vh)
- **No scrolling** - All content visible at once
- **Responsive** - Works on desktop, tablet, and mobile without scroll
- **Dark mode support** - Essential for developer audience

### Page Structure

#### 1. Header (10% height)
- **Left**: Simple logo or "Prompt Library" text
- **Right**: Login / Register buttons (or Dashboard if logged in)

#### 2. Main Content (80% height)
Centered content with:

**Hero Section**
- **Title**: "Unified AI Prompt Library"
- **Subtitle**: "200+ Fabric patterns. Custom prompts. MCP-enabled."
- **Description** (1-2 lines): "Access curated AI prompts through Claude Desktop, Cursor, and other MCP clients."

**Key Features** (3 column grid, single line each):
- 🔧 **Fabric Patterns** - 200+ curated prompts
- ✏️ **Custom Prompts** - Create your own
- 🔌 **MCP Integration** - Works with AI tools

**Call to Action**:
- Primary button: "Get Started" → Register
- Secondary link: "Learn More" → Documentation

#### 3. Footer (10% height)
- Minimal info: "Powered by Laravel Loop" | GitHub link
- Laravel version (small text)

### Visual Style

**Colors**:
- Background: Light gray (light mode) / Dark (dark mode)
- Accent: Purple or blue gradient for buttons
- Text: High contrast for readability

**Typography**:
- Large, bold heading (3-4rem)
- Clear, readable body text
- Consistent font hierarchy

**Visual Elements**:
- Subtle gradient or pattern background
- Clean card design for features
- Smooth transitions on hover
- No images or illustrations (keep it minimal)

### Component Structure Example

```vue
<template>
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="h-[10vh] flex items-center justify-between px-8">
            <!-- Logo/Title -->
            <!-- Auth buttons -->
        </header>

        <!-- Main -->
        <main class="flex-1 flex items-center justify-center">
            <div class="text-center max-w-4xl px-6">
                <!-- Hero -->
                <!-- Features Grid -->
                <!-- CTA Buttons -->
            </div>
        </main>

        <!-- Footer -->
        <footer class="h-[10vh] flex items-center justify-center">
            <!-- Minimal info -->
        </footer>
    </div>
</template>
```

### Interaction States

**Buttons**:
- Hover: Slight scale and shadow
- Active: Pressed effect
- Focus: Visible outline for accessibility

**Dark Mode Toggle**:
- Smooth transition between themes
- Icon button in header

### Mobile Considerations
- Stack features vertically on small screens
- Reduce font sizes appropriately  
- Maintain no-scroll requirement
- Touch-friendly button sizes (min 44px)

## Design Principles
1. **Minimalism** - Only essential information
2. **Clarity** - Instant understanding of purpose
3. **Professionalism** - Clean, developer-friendly aesthetic
4. **Accessibility** - WCAG compliant, keyboard navigable
5. **Performance** - Lightweight, fast loading

## Example Welcome Message Variations
- "Your AI Prompts, Unified and Accessible"
- "Bridge Your Prompts to AI Tools via MCP"
- "Curated Patterns. Custom Prompts. One Library."

## Success Criteria
- User understands the app's purpose in 3 seconds
- Clear path to registration/login
- Professional appearance that builds trust
- Works perfectly without any scrolling
- Smooth experience across all devices
