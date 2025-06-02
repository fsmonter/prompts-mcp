# Fabric Patterns MCP Server

A Laravel application that exposes [Fabric's](https://github.com/danielmiessler/fabric) extensive collection of AI patterns through the Model Context Protocol (MCP), making them instantly accessible to AI assistants like Claude Desktop, Cursor, and other MCP-compatible clients.

## What This Does

This Laravel MCP server transforms Fabric's 200+ curated AI patterns into standardized MCP tools that can be seamlessly integrated with AI clients. Instead of manually copying prompts or setting up CLI tools, you can access all Fabric patterns directly through your AI assistant.

## Features

* **Complete Fabric Pattern Library**: Access to all 200+ patterns from the official Fabric repository
* **Real-time Sync**: Automatic synchronization with the latest Fabric patterns
* **MCP Standard Compliance**: Works with any MCP-compatible client (Claude Desktop, Cursor, Windsurf, etc.)
* **Pattern Discovery**: Search, filter, and browse patterns by category
* **Usage Analytics**: Track pattern execution and performance
* **Laravel Native**: Built with Laravel's elegant architecture and tooling

## Installation

### Prerequisites

* PHP 8.2+
* Composer
* Laravel 12.x
* SQLite (default) or your preferred database

### Setup

1. **Clone and install dependencies**:

```bash
git clone <repository-url> fabric-prompts
cd fabric-prompts
composer install
```

2. **Configure environment**:

```bash
cp .env.example .env
php artisan key:generate
```

3. **Run migrations**:

```bash
php artisan migrate
```

4. **Sync Fabric patterns**:

```bash
php artisan fabric:sync-patterns
```

## Configuration

### MCP Server Setup

Generate configuration for your MCP client:

```bash
php artisan loop:mcp:generate-config
```

This will guide you through setting up:

* Claude Desktop
* Cursor
* Other MCP clients

### Environment Variables

The following environment variables control MCP server behavior:

```env
# Enable MCP transport
LOOP_SSE_ENABLED=true
```

## Usage

### Available MCP Tools

Once configured, your AI client will have access to these streamlined tools:

#### Core Execution Tool

* `fabric_execute_pattern` - Execute any Fabric pattern by name
  * Parameters: `pattern_name` (required), `input_content`, `additional_context`

#### Discovery and Browsing Tools

* `fabric_search_patterns` - Search patterns by keyword
  * Parameters: `query` (required), `limit` (optional)
* `fabric_list_patterns_by_category` - Browse patterns by category
  * Parameters: `category` (optional)
* `fabric_list_all_patterns` - Get complete pattern list
  * Parameters: `format` (optional: "compact" or "detailed")
* `fabric_get_pattern_details` - Get detailed pattern information
  * Parameters: `pattern_name` (required)

### Example Usage in Claude Desktop

1. **Find and execute a pattern**:
   ```
   Search for patterns related to "analysis"
   Then use fabric_execute_pattern with pattern_name "analyze_claims" and this article: [paste content]
   ```

2. **Browse patterns by category**:
   ```
   Show me all patterns in the "writing" category
   ```

3. **Get pattern details**:
   ```
   Get details for the pattern "create_summary"
   ```

4. **Quick execution**:
   ```
   Execute the analyze_claims pattern on this text: [content]
   ```

See more examples using cursor in [./docs/cursor-examples](./docs/cursor-examples)

### Connecting to Claude Desktop

1. **Generate configuration**:
   ```bash
   php artisan loop:mcp:generate-config
   ```

2. **Add to Claude Desktop config** (`~/Library/Application Support/Claude/claude_desktop_config.json`):
   ```json
   {
     "mcpServers": {
       "fabric-patterns": {
         "transport": "sse",
         "url": "http://your-app-url/mcp/sse"
       }
     }
   }
   ```

3. **Restart Claude Desktop**

### Connecting to Cursor

1. **Using STDIO** (recommended):
   ```json
   {
     "mcpServers": {
       "fabric-patterns": {
         "command": "php",
         "args": ["/path/to/your/project/artisan", "loop:mcp:start"]
       }
     }
   }
   ```

## Available Patterns

The server includes all official Fabric patterns organized by category:

* **Analysis**: `analyze_claims`, `analyze_debate`, `analyze_paper`, etc.
* **Writing**: `write_essay`, `write_micro_essay`, `improve_writing`, etc.
* **Coding**: `create_coding_project`, `code_review`, `explain_code`, etc.
* **Business**: `create_business_plan`, `analyze_market`, etc.
* **Research**: `extract_wisdom`, `summarize_paper`, `find_logical_fallacies`, etc.

## Artisan Commands

### Pattern Management

```bash
# Sync all patterns from Fabric repository
php artisan fabric:sync-patterns

# Force sync (ignore cache)
php artisan fabric:sync-patterns --force

# Sync specific pattern
php artisan fabric:sync-patterns --pattern=analyze_claims
```

### MCP Server

```bash
# Generate MCP client configuration
php artisan loop:mcp:generate-config

# Start STDIO MCP server
php artisan loop:mcp:start

# Start with debug mode
php artisan loop:mcp:start --debug
```

### Adding Custom Patterns

You can add custom patterns by creating them in the database or extending the service to load from additional sources.

### Testing

```bash
# Run tests
php artisan test

# Test specific pattern
php artisan tinker
>>> $pattern = App\Models\FabricPattern::where('name', 'analyze_claims')->first();
>>> $service = app(App\Services\FabricPatternService::class);
>>> $result = $service->executePattern($pattern, 'Test content');
```

## Architecture

### MCP Integration

Built on [Laravel Loop](https://github.com/kirschbaum-development/laravel-loop), this server:

1. **Dynamically generates MCP tools** for each Fabric pattern
2. **Provides pattern discovery** through utility tools
3. **Tracks usage analytics** for monitoring and optimization
4. **Handles real-time updates** from the Fabric repository

### Pattern Processing

1. Patterns are fetched from the Fabric GitHub repository
2. Parsed for metadata (title, description, category, tags)
3. Stored in the database with change detection
4. Exposed as individual MCP tools with appropriate schemas

## Troubleshooting

### Common Issues

**"No patterns found"**

* Run `php artisan fabric:sync-patterns` to sync patterns
* Check internet connectivity to GitHub

**"MCP connection failed"**

* Ensure Laravel server is running
* Check the MCP endpoint URL
* Verify authentication if configured
* \[Laravel Loop] Error checking session existence: Connection refused: Check your Redis server is running

**"Tool not found"**

* Pattern names use format `fabric_{pattern_name}`
* Use `fabric_search_patterns` to find available tools
