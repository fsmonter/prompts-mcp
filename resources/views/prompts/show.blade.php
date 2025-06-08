<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $prompt->title }} - Prompt Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <div class="flex items-center space-x-2 text-sm text-gray-500 mb-4">
                <a href="{{ route('prompts.index') }}" class="hover:text-gray-700">Prompt Library</a>
                <span>/</span>
                <span>{{ $prompt->name }}</span>
            </div>

            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $prompt->title }}</h1>
                    @if($prompt->description)
                        <p class="text-gray-600 text-lg">{{ $prompt->description }}</p>
                    @endif
                </div>

                <div class="ml-6 flex items-center space-x-3">
                    @if($prompt->source_type === 'fabric')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            🔧 Fabric Pattern
                        </span>
                    @elseif($prompt->source_type === 'manual')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            ✏️ Custom Prompt
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                            📁 {{ ucfirst($prompt->source_type) }}
                        </span>
                    @endif

                    @if($prompt->source_type === 'manual')
                        <a href="{{ route('prompts.edit', $prompt) }}"
                           class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 text-sm font-medium">
                            Edit
                        </a>
                    @endif
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Prompt Content -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Prompt Content</h2>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <pre class="text-sm text-gray-800 whitespace-pre-wrap font-mono">{{ $prompt->content }}</pre>
                    </div>
                </div>

                <!-- Usage Instructions -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">How to Use</h2>
                    <div class="space-y-3">
                        <div>
                            <h3 class="font-medium text-gray-900 mb-1">Via MCP (Claude, Cursor, etc.)</h3>
                            <code class="block bg-gray-100 px-3 py-2 rounded text-sm">
                                Execute the {{ $prompt->name }} pattern with your content
                            </code>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900 mb-1">Direct Tool Call</h3>
                            <code class="block bg-gray-100 px-3 py-2 rounded text-sm">
                                fabric_execute_pattern with pattern_name "{{ $prompt->name }}"
                            </code>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Metadata -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Details</h2>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Name</dt>
                            <dd class="text-sm text-gray-900 font-mono">{{ $prompt->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Category</dt>
                            <dd class="text-sm text-gray-900">{{ ucfirst($prompt->category) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Source</dt>
                            <dd class="text-sm text-gray-900">{{ ucfirst($prompt->source_type) }}</dd>
                        </div>
                        @if(!empty($prompt->tags))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Tags</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @foreach($prompt->tags as $tag)
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ $tag }}
                                            </span>
                                        @endforeach
                                    </div>
                                </dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Estimated Tokens</dt>
                            <dd class="text-sm text-gray-900">{{ $prompt->estimated_tokens }}</dd>
                        </div>
                        @if($prompt->synced_at)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Last Synced</dt>
                                <dd class="text-sm text-gray-900">{{ $prompt->synced_at->diffForHumans() }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                            <dd class="text-sm text-gray-900">{{ $prompt->created_at->diffForHumans() }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Usage Stats -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Usage Statistics</h2>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Total Compositions</dt>
                            <dd class="text-2xl font-bold text-gray-900">{{ $prompt->compositions()->count() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Recent (30 days)</dt>
                            <dd class="text-2xl font-bold text-blue-600">{{ $recentCompositions }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Actions -->
                @if($prompt->source_type === 'manual')
                    <div class="bg-white rounded-lg shadow-sm border p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Actions</h2>
                        <div class="space-y-3">
                            <a href="{{ route('prompts.edit', $prompt) }}"
                               class="block w-full bg-blue-600 text-white text-center px-4 py-2 rounded-lg hover:bg-blue-700 font-medium">
                                Edit Prompt
                            </a>
                            <form action="{{ route('prompts.destroy', $prompt) }}" method="POST"
                                  onsubmit="return confirm('Are you sure you want to delete this prompt?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="block w-full bg-red-600 text-white text-center px-4 py-2 rounded-lg hover:bg-red-700 font-medium">
                                    Delete Prompt
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
