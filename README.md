# Prompt Library MCP Server

A Laravel application that provides a unified prompt library accessible through the Model Context Protocol (MCP). Create custom prompts via a simple web interface or automatically sync prompts from external sources like [Fabric's](https://github.com/danielmiessler/fabric) extensive collection of AI patterns.

## Overview

This Laravel MCP server transforms any prompt collection into standardized MCP tools for seamless integration with AI clients. Access Fabric's 200+ curated patterns, create your own custom prompts, or sync from other repositories - all through a single, searchable library.

## Key Features

* **Unified Prompt Library**: Single interface for all prompt types (Fabric patterns, custom prompts, external sources)
* **Simple Web Interface**: Clean, intuitive form for creating and managing custom prompts
* **MCP Standard Compliance**: Compatible with any MCP-enabled client (Claude Desktop, Cursor, Windsurf, etc.)
* **Intelligent Discovery**: Search, filter, and browse prompts by category, source, and tags
* **Backward Compatible**: Existing Fabric integrations continue working seamlessly

## Installation

### Prerequisites

* PHP 8.2 or higher
* Composer
* Laravel 12.x
* SQLite (default) or your preferred database

### Setup

1. **Clone and install dependencies**:

```bash
git clone <repository-url> prompt-library
cd prompt-library
composer install
```

2. **Configure environment**:

```bash
cp .env.example .env
php artisan key:generate
```

3. **Initialize database**:

```bash
php artisan migrate
```

4. **Sync Fabric patterns** (optional):

```bash
php artisan prompts:sync --source=fabric
```

## Usage

### Web Interface

Visit your application URL to access the prompt library:

- **Browse Library**: View all prompts with filtering by source, category, and search
- **Create Custom Prompts**: Simple form with title, description, content, and categorization
- **Manage Prompts**: Edit and organize your custom prompts

### MCP Integration

Once configured, your AI client gains access to these unified tools:

#### Core Tools

* `fabric_execute_pattern` - Compose any prompt (Fabric or custom) with user input
  * Parameters: `pattern_name` (required), `input_content`, `additional_context`

#### Discovery Tools

* `fabric_search_patterns` - Search across all prompt sources
* `fabric_list_categories` - List categories with prompt counts
* `fabric_list_patterns_by_category` - Browse by category
* `fabric_list_all_patterns` - Complete prompt catalog
* `fabric_get_pattern_details` - Detailed prompt information

### Example Usage in Claude Desktop

1. **Use a Fabric pattern**:
   ```
   Execute the analyze_claims pattern on this article: [paste content]
   ```

2. **Use a custom prompt**:
   ```
   Execute my-custom-analysis with this data: [paste content]
   ```

3. **Browse available prompts**:
   ```
   List all prompts in the writing category
   ```

4. **Search prompts**:
   ```
   Search for prompts related to "code review"
   ```

## Connecting to AI Clients

### Claude Desktop

1. **Generate configuration**:
   ```bash
   php artisan loop:mcp:generate-config
   ```

2. **Add to Claude Desktop config** (`~/Library/Application Support/Claude/claude_desktop_config.json`):
   ```json
   {
     "mcpServers": {
       "prompt-library": {
         "transport": "sse",
         "url": "http://your-app-url/mcp/sse"
       }
     }
   }
   ```

3. **Restart Claude Desktop**

### Cursor

```json
{
  "mcpServers": {
    "prompt-library": {
      "command": "php",
      "args": ["/path/to/your/project/artisan", "loop:mcp:start"]
    }
  }
}
```

## Prompt Sources

### Fabric Patterns (Automatic)
- 200+ official patterns from danielmiessler/fabric
- Automatically categorized and tagged
- Synced via `php artisan prompts:sync --source=fabric`

### Custom Prompts (Manual)
- Create through web interface
- Full control over content, categorization, and visibility
- Support for template variables ({{INPUT}}, $INPUT)

### Future Sources
The architecture supports additional sources:
- GitHub repositories with similar structure
- API-based prompt services
- Imported prompt collections

## Commands

### Prompt Management

```bash
# Sync from external sources
php artisan prompts:sync --source=fabric
php artisan prompts:sync --source=all --force

# Generate MCP client configuration
php artisan loop:mcp:generate-config

# Start STDIO MCP server
php artisan loop:mcp:start
```

### Creating Custom Prompts

Via Web Interface:
1. Visit `/prompts/create`
2. Fill in title, description, content, and category
3. Use template variables like `{{INPUT}}` for user content
4. Set visibility (public/private)

Via Code:
```php
use App\Services\PromptService;

$promptService = app(PromptService::class);
$prompt = $promptService->createManualPrompt([
    'title' => 'My Custom Prompt',
    'description' => 'Analyzes marketing copy',
    'content' => 'You are a marketing expert. Analyze: {{INPUT}}',
    'category' => 'analysis',
    'tags' => ['marketing', 'analysis'],
    'is_public' => true,
]);
```

## Architecture

### Unified Data Model
- Single `prompts` table stores all prompt types
- `source_type` field differentiates: `'manual'`, `'fabric'`, `'github'`
- Consistent API across all prompt sources

### MCP Integration
Built on [Laravel Loop](https://github.com/kirschbaum-development/laravel-loop):
1. **Unified toolkit** exposes all prompts through same interface
2. **Backward compatible** - existing `fabric_execute_pattern` still works
3. **Source-aware** - indicates prompt origin in responses
4. **Usage tracking** - monitors prompt composition for analytics

## Troubleshooting

### Common Issues

**"No prompts found"**
* Run `php artisan prompts:sync --source=fabric` to sync Fabric patterns
* Check web interface at `/prompts` to verify prompts exist

**"MCP connection failed"**
* Ensure Laravel server is running
* Verify MCP endpoint URL in client configuration
* Check authentication if enabled

**"Tool not found"**
* Pattern names use exact format from prompt library
* Use `fabric_search_patterns` to find available prompts
* Check web interface for correct prompt names

## Testing

```bash
# Run all tests
php artisan test

# Test specific features
php artisan test --filter PromptManagementTest
php artisan test --filter McpIntegrationTest
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Add tests for new functionality
4. Submit a pull request

## License

MIT License - see LICENSE file for details.
