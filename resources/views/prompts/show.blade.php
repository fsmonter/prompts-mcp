<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $prompt->title }} - Prompt Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="px-4 py-8 mx-auto max-w-4xl sm:px-6 lg:px-8">
        <div class="mb-8">
            <div class="flex items-center mb-4 space-x-2 text-sm text-gray-500">
                <a href="{{ route('prompts.index') }}" class="hover:text-gray-700">Prompt Library</a>
                <span>/</span>
                <span>{{ $prompt->name }}</span>
            </div>

            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <h1 class="mb-2 text-3xl font-bold text-gray-900">{{ $prompt->title }}</h1>
                    @if($prompt->description)
                        <p class="text-lg text-gray-600">{{ $prompt->description }}</p>
                    @endif
                </div>

                <div class="flex items-center ml-6 space-x-3">
                    @if($prompt->source_type === 'fabric')
                        <span class="inline-flex items-center px-3 py-1 text-sm font-medium text-blue-800 bg-blue-100 rounded-full">
                            🔧 Fabric Pattern
                        </span>
                    @elseif($prompt->source_type === 'manual')
                        <span class="inline-flex items-center px-3 py-1 text-sm font-medium text-green-800 bg-green-100 rounded-full">
                            ✏️ Custom Prompt
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 text-sm font-medium text-gray-800 bg-gray-100 rounded-full">
                            📁 {{ ucfirst($prompt->source_type) }}
                        </span>
                    @endif

                    @if($prompt->source_type === 'manual')
                        <a href="{{ route('prompts.edit', $prompt) }}"
                           class="px-4 py-2 text-sm font-medium text-white bg-gray-600 rounded-lg hover:bg-gray-700">
                            Edit
                        </a>
                    @endif
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="px-4 py-3 mb-6 text-green-700 bg-green-100 rounded border border-green-400">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main Content -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Prompt Content -->
                <div class="p-6 bg-white rounded-lg border shadow-sm">
                    <h2 class="mb-4 text-xl font-semibold text-gray-900">Prompt Content</h2>
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <pre class="font-mono text-sm text-gray-800 whitespace-pre-wrap">{{ $prompt->content }}</pre>
                    </div>
                </div>

                <!-- Usage Instructions -->
                <div class="p-6 bg-white rounded-lg border shadow-sm">
                    <h2 class="mb-4 text-xl font-semibold text-gray-900">How to Use</h2>
                    <div class="space-y-3">
                        <div>
                            <h3 class="mb-1 font-medium text-gray-900">Via MCP (Claude, Cursor, etc.)</h3>
                            <code class="block px-3 py-2 text-sm bg-gray-100 rounded">
                                Execute the {{ $prompt->name }} pattern with your content
                            </code>
                        </div>
                        <div>
                            <h3 class="mb-1 font-medium text-gray-900">Direct Tool Call</h3>
                            <code class="block px-3 py-2 text-sm bg-gray-100 rounded">
                                compose_prompt with pattern_name "{{ $prompt->name }}"
                            </code>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Metadata -->
                <div class="p-6 bg-white rounded-lg border shadow-sm">
                    <h2 class="mb-4 text-xl font-semibold text-gray-900">Details</h2>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Name</dt>
                            <dd class="font-mono text-sm text-gray-900">{{ $prompt->name }}</dd>
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
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100 rounded">
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
                <div class="p-6 bg-white rounded-lg border shadow-sm">
                    <h2 class="mb-4 text-xl font-semibold text-gray-900">Usage Statistics</h2>
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
                    <div class="p-6 bg-white rounded-lg border shadow-sm">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">Actions</h2>
                        <div class="space-y-3">
                            <a href="{{ route('prompts.edit', $prompt) }}"
                               class="block px-4 py-2 w-full font-medium text-center text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                                Edit Prompt
                            </a>
                            <form action="{{ route('prompts.destroy', $prompt) }}" method="POST"
                                  onsubmit="return confirm('Are you sure you want to delete this prompt?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="block px-4 py-2 w-full font-medium text-center text-white bg-red-600 rounded-lg hover:bg-red-700">
                                    Delete Prompt
                                </button>
                            </form>
                        </div>
                    </div>
            </div>
        </div>
    </div>
</body>
</html>
