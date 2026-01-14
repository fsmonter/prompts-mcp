# CLAUDE.md - AI Assistant Guide

This document provides guidance for AI assistants working with the Prompt Library MCP Server codebase.

## Project Overview

A Laravel 12 application providing a unified prompt library accessible through the Model Context Protocol (MCP). It aggregates prompts from multiple sources (Fabric patterns, custom prompts, external Git repositories) into a single searchable library.

**Key Technologies:**
- PHP 8.2+, Laravel 12.x
- Laravel Loop (MCP server implementation)
- SQLite (default database)
- Pest (testing framework)
- Vite + Tailwind CSS 4 (frontend)

## Directory Structure

```
app/
├── Console/Commands/       # Artisan commands for prompt sync
├── Http/Controllers/       # Web interface controllers
├── Mcp/                    # MCP toolkit implementation
├── Models/                 # Eloquent models
├── Providers/              # Service providers
└── Services/               # Business logic services

config/
├── loop.php                # MCP/SSE configuration
└── ...                     # Standard Laravel config

database/
├── migrations/             # Database schema
└── schema/                 # SQLite schema dump

resources/
└── views/prompts/          # Blade templates for web UI

routes/
├── web.php                 # Web routes (prompt CRUD)
└── console.php             # Console route definitions

tests/
└── Feature/                # Feature tests using Pest
```

## Core Components

### Models

| Model | Purpose |
|-------|---------|
| `Prompt` | Stores all prompt types (manual, fabric, github). Key fields: `name`, `title`, `content`, `source_type`, `category`, `is_active`, `is_public` |
| `Composition` | Tracks prompt usage/executions with input content and metadata |
| `PromptSource` | Configures external prompt repositories for syncing |

### Services

| Service | Purpose |
|---------|---------|
| `PromptService` | Main business logic - prompt creation, composition, search |
| `GitSyncService` | Syncs prompts from Git repositories (Fabric, custom repos) |

### MCP Toolkit

`App\Mcp\PromptLibraryToolkit` exposes these MCP tools:
- `compose_prompt` - Compose a prompt with user input
- `search_prompts` - Search across all prompt sources
- `list_categories` - Get prompt categories with counts
- `list_prompts_by_category` - Browse prompts by category
- `list_all_prompts` - Complete prompt catalog
- `get_prompt_details` - Detailed prompt information

## Development Workflow

### Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan prompts:sync --source=fabric  # Initial Fabric sync
```

### Running the Application

```bash
# Development server with all services
composer dev

# Or individual services:
php artisan serve              # Web server
php artisan queue:listen       # Queue worker
npm run dev                    # Vite dev server
```

### Testing

```bash
# Run all tests
composer test

# Or directly with Pest
php artisan test

# Run specific test file
php artisan test tests/Feature/PromptManagementTest.php
```

### Code Formatting

```bash
# Format with Laravel Pint
./vendor/bin/pint
```

## Key Artisan Commands

| Command | Description |
|---------|-------------|
| `prompts:sync --source=fabric` | Sync Fabric patterns from GitHub |
| `prompts:sync --source=all` | Sync all configured sources |
| `prompts:sync --force` | Force sync (ignore rate limiting) |
| `prompts:add-source {name} {repo}` | Add new Git-based prompt source |
| `prompts:list-sources` | List configured prompt sources |
| `loop:mcp:start` | Start STDIO MCP server (for Cursor) |
| `loop:mcp:generate-config` | Generate MCP client config |

## Code Conventions

### PHP Standards

- Use `declare(strict_types=1)` in all PHP files
- Use constructor property promotion for dependency injection
- Follow PSR-12 coding style (enforced by Laravel Pint)
- Use typed properties and return types

### Prompt Source Types

- `manual` - User-created prompts via web UI
- `fabric` - Synced from danielmiessler/fabric repository
- `github` - Synced from custom Git repositories

### Template Variables

Prompts support these input placeholders (all replaced with user input):
- `{{INPUT}}`
- `{INPUT}`
- `$INPUT`
- `INPUT:`

### Model Scopes

Common query scopes used throughout the codebase:
```php
Prompt::active()           // is_active = true
Prompt::public()           // is_public = true
Prompt::fromSource('fabric')  // source_type = 'fabric'
Prompt::category('analysis')  // category = 'analysis'
```

## Database Schema

### prompts table
- `name` (unique per source_type) - URL-safe identifier
- `source_type` - enum: manual, fabric, github
- `source_identifier` - Links to prompt_sources.name
- `checksum` - SHA256 hash for sync change detection

### compositions table
- Tracks every prompt composition with timing and token estimates
- Links to prompt via `prompt_id` foreign key

### prompt_sources table
- Stores Git repository configurations for syncing
- Tracks sync status: pending, syncing, completed, failed

## MCP Configuration

### SSE Transport (Claude Desktop)
Default endpoint: `/mcp/sse`

### STDIO Transport (Cursor)
```bash
php artisan loop:mcp:start
```

### Environment Variables
```
LOOP_SSE_ENABLED=true
LOOP_SSE_PATH=/mcp/sse
LOOP_SSE_DRIVER=file  # or redis
```

## Testing Patterns

Tests use Pest with Laravel's RefreshDatabase trait:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_does_something()
    {
        // Create test data
        $prompt = Prompt::create([...]);

        // Assert database state
        $this->assertDatabaseHas('prompts', [...]);
    }
}
```

## Common Tasks

### Adding a New MCP Tool

1. Edit `app/Mcp/PromptLibraryToolkit.php`
2. Add new method returning `CustomTool`
3. Push to `$tools` collection in `getTools()`
4. Add test coverage in `tests/Feature/McpIntegrationTest.php`

### Adding a New Prompt Source

```bash
php artisan prompts:add-source my-repo https://github.com/user/repo.git \
    --branch=main \
    --path="prompts/*.md"
```

### Creating Custom Prompts Programmatically

```php
$promptService = app(PromptService::class);
$prompt = $promptService->createManualPrompt([
    'title' => 'My Prompt',
    'content' => 'Analyze: {{INPUT}}',
    'category' => 'analysis',
    'tags' => ['custom', 'analysis'],
]);
```

## Important Notes

- Only manual prompts can be edited/deleted via web UI
- Fabric prompts are read-only and synced from upstream
- Sync operations have rate limiting (1 hour cooldown, use `--force` to override)
- The `name` field must be unique within each `source_type`
- Inactive prompts (`is_active=false`) are hidden from all public views and MCP tools
