<?php

namespace App\Http\Controllers;

use App\Models\Prompt;
use App\Services\PromptService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PromptController extends Controller
{
    public function __construct(
        private readonly PromptService $promptService
    ) {}

    /**
     * Display prompt library
     */
    public function index(Request $request)
    {
        $query = Prompt::active()->public();

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }

        if ($request->filled('source')) {
            $query->where('source_type', $request->get('source'));
        }

        $prompts = $query->orderBy('title')->paginate(20);
        $categories = $this->promptService->getCategories();
        $sources = Prompt::select('source_type')->distinct()->pluck('source_type')->toArray();

        return view('prompts.index', compact('prompts', 'categories', 'sources'));
    }

    /**
     * Show form to create new prompt
     */
    public function create()
    {
        $categories = $this->promptService->getCategories();

        return view('prompts.create', compact('categories'));
    }

    /**
     * Store new prompt
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'content' => 'required|string',
            'category' => 'required|string|max:100',
            'tags' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        // Process tags
        $validated['tags'] = $request->filled('tags')
            ? array_map('trim', explode(',', $validated['tags']))
            : [];

        // Ensure unique name
        $baseSlug = Str::slug($validated['title']);
        $slug = $baseSlug;
        $counter = 1;

        while (Prompt::where('source_type', 'manual')->where('name', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        $validated['name'] = $slug;

        $prompt = $this->promptService->createManualPrompt($validated);

        return redirect()->route('prompts.show', $prompt)
            ->with('success', 'Prompt created successfully!');
    }

    /**
     * Show prompt details
     */
    public function show(Prompt $prompt)
    {
        $prompt->load('compositions');
        $recentCompositions = $prompt->compositions()->recent(30)->count();

        return view('prompts.show', compact('prompt', 'recentCompositions'));
    }

    /**
     * Show form to edit prompt (manual prompts only)
     */
    public function edit(Prompt $prompt)
    {
        if ($prompt->source_type !== 'manual') {
            abort(403, 'Only manual prompts can be edited.');
        }

        $categories = $this->promptService->getCategories();

        return view('prompts.edit', compact('prompt', 'categories'));
    }

    /**
     * Update prompt
     */
    public function update(Request $request, Prompt $prompt)
    {
        if ($prompt->source_type !== 'manual') {
            abort(403, 'Only manual prompts can be edited.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'content' => 'required|string',
            'category' => 'required|string|max:100',
            'tags' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        // Process tags
        $validated['tags'] = $request->filled('tags')
            ? array_map('trim', explode(',', $validated['tags']))
            : [];

        $prompt->update($validated);

        return redirect()->route('prompts.show', $prompt)
            ->with('success', 'Prompt updated successfully!');
    }

    /**
     * Delete prompt (manual prompts only)
     */
    public function destroy(Prompt $prompt)
    {
        if ($prompt->source_type !== 'manual') {
            abort(403, 'Only manual prompts can be deleted.');
        }

        $prompt->delete();

        return redirect()->route('prompts.index')
            ->with('success', 'Prompt deleted successfully!');
    }
}
