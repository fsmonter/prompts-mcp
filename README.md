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
# Enable MCP transports
LOOP_SSE_ENABLED=true
LOOP_STREAMABLE_HTTP_ENABLED=true

# Database (SQLite is default)
DB_CONNECTION=sqlite
```

## Usage

### Available MCP Tools

Once configured, your AI client will have access to:

#### Pattern Execution Tools

* `fabric_{pattern_name}` - Execute any Fabric pattern (200+ tools)
  * Parameters: `input_content`, `additional_context`

#### Discovery Tools

* `fabric_list_patterns_by_category` - List patterns by category
* `fabric_search_patterns` - Search patterns by keyword
* `fabric_get_pattern_details` - Get detailed pattern information

### Example Usage in Claude Desktop

1. **Execute a pattern**:
   ```
   Use the fabric_analyze_claims tool to analyze this article: [paste content]
   ```

2. **Discover patterns**:
   ```
   Search for patterns related to "writing"
   ```

3. **Browse by category**:
   ```
   Show me all patterns in the "analysis" category
   ```

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

## Development

### Project Structure

```
app/
├── Mcp/
│   └── FabricPatternsToolkit.php     # MCP tool definitions
├── Models/
│   ├── FabricPattern.php             # Pattern model
│   └── PatternExecution.php          # Execution tracking
├── Services/
│   └── FabricPatternService.php      # Pattern management logic
└── Console/Commands/
    └── SyncFabricPatternsCommand.php # Sync command
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

**"Tool not found"**

* Pattern names use format `fabric_{pattern_name}`
* Use `fabric_search_patterns` to find available tools

### Debugging

Enable debug mode:

```bash
php artisan loop:mcp:start --debug
```

Check application logs:

```bash
php artisan pail
```

## Security

### Authentication

For production deployments, configure authentication middleware in `config/loop.php`:

```php
'sse' => [
    'middleware' => ['auth:sanctum'],
],
```

### Rate Limiting

Consider implementing rate limiting for public endpoints to prevent abuse.

## Performance

### Caching

* Pattern sync results are cached for 1 hour
* Database queries are optimized with proper indexing
* Large pattern content is stored efficiently

### Scaling

For high-volume usage:

* Use Redis for caching and sessions
* Configure database connection pooling
* Consider read replicas for pattern data

## Contributing

1. Fork the repository
2. Create your feature branch
3. Run tests: `php artisan test`
4. Submit a pull request

## License

MIT License - see [LICENSE](LICENSE) for details.

## Related Projects

* [Fabric](https://github.com/danielmiessler/fabric) - The original AI pattern framework
* [Laravel Loop](https://github.com/kirschbaum-development/laravel-loop) - Laravel MCP server implementation
* [Model Context Protocol](https://modelcontextprotocol.io/) - The open standard for AI-application integration

***
