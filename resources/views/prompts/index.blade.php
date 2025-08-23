<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prompt Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Prompt Library</h1>
                <p class="text-gray-600 mt-2">Manage your AI prompts and patterns</p>
            </div>

        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <form method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-64">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search prompts..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <select name="category" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>
                                {{ ucfirst($category) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="source" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Sources</option>
                        @foreach($sources as $source)
                            <option value="{{ $source }}" {{ request('source') === $source ? 'selected' : '' }}>
                                {{ ucfirst($source) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                    Filter
                </button>
                <a href="{{ route('prompts.index') }}" class="text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-100">
                    Clear
                </a>
            </form>
        </div>

        <!-- Prompts Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($prompts as $prompt)
                <div class="bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <h3 class="font-semibold text-lg text-gray-900">{{ $prompt->title }}</h3>
                                <p class="text-sm text-gray-500">{{ $prompt->name }}</p>
                            </div>
                            <div class="ml-3">
                                @if($prompt->source_type === 'fabric')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        🔧 Fabric
                                    </span>
                                @elseif($prompt->source_type === 'manual')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        ✏️ Custom
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        📁 {{ ucfirst($prompt->source_type) }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if($prompt->description)
                            <p class="text-gray-600 text-sm mb-4 line-clamp-3">{{ $prompt->description }}</p>
                        @endif

                        <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                            <span class="bg-gray-100 px-2 py-1 rounded">{{ ucfirst($prompt->category) }}</span>
                            <span>{{ $prompt->estimated_tokens }} tokens</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="text-xs text-gray-400">
                                {{ $prompt->compositions_count ?? 0 }} uses
                            </div>
                            <div class="flex space-x-2">
                                <a href="{{ route('prompts.show', $prompt) }}"
                                   class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    View
                                </a>
                                @if($prompt->source_type === 'manual')
                                    <a href="{{ route('prompts.edit', $prompt) }}"
                                       class="text-gray-600 hover:text-gray-800 text-sm font-medium">
                                        Edit
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12">
                    <div class="text-gray-400 text-lg mb-4">No prompts found</div>

                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($prompts->hasPages())
            <div class="mt-8">
                {{ $prompts->links() }}
            </div>
        @endif
    </div>
</body>
</html>
