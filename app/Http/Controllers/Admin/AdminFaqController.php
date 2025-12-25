<?php
// app/Http/Controllers/Admin/AdminFaqController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class AdminFaqController extends Controller
{
    /**
     * Display FAQ dashboard
     */
    public function index(): View
    {
        $this->checkAccess();
        $user = \Auth::user();
        
        $stats = [
            'total_faqs' => Faq::count(),
            'active_faqs' => Faq::where('status', 'active')->count(),
            'inactive_faqs' => Faq::where('status', 'inactive')->count(),
            'featured_faqs' => Faq::where('is_featured', true)->count(),
            'total_views' => Faq::sum('views'),
            'faqs_by_category' => $this->getFaqsByCategory(),
            'recent_views' => Faq::where('updated_at', '>=', now()->subDays(7))->sum('views'),
            'most_viewed' => Faq::orderBy('views', 'desc')->limit(5)->get(),
        ];

        // Recent FAQs for quick overview
        $recentFaqs = Faq::with(['creator', 'updater'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.support.faq.index', compact('user', 'stats', 'recentFaqs'));
    }

    /**
     * Display all FAQs with filtering
     */
    public function faqs(Request $request): View
    {
        $this->checkAccess();
        $user = \Auth::user();

        $query = Faq::with(['creator', 'updater'])
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('is_featured')) {
            $query->where('is_featured', $request->is_featured === '1');
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $faqs = $query->paginate(20)->withQueryString();

        return view('admin.support.faq.faqs', compact('faqs', 'user'));
    }

    /**
     * Show the form for creating a new FAQ
     */
    public function create(): View
    {
        $this->checkAccess();
        $user = \Auth::user();
        
        $categories = Faq::getCategories();
        
        return view('admin.support.faq.create', compact('categories', 'user'));
    }

    /**
     * Store a newly created FAQ
     */
    public function store(Request $request): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'required|string|in:' . implode(',', array_keys(Faq::getCategories())),
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
            'is_featured' => 'boolean',
            'tags' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Process tags
            $tags = null;
            if ($request->filled('tags')) {
                $tags = array_map('trim', explode(',', $validated['tags']));
                $tags = array_filter($tags); // Remove empty tags
            }

            $faq = Faq::create([
                'question' => $validated['question'],
                'answer' => $validated['answer'],
                'category' => $validated['category'],
                'status' => $validated['status'],
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_featured' => $validated['is_featured'] ?? false,
                'tags' => $tags,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            Log::info('FAQ created', [
                'faq_id' => $faq->id,
                'question' => $faq->question,
                'category' => $faq->category,
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'FAQ created successfully',
                'faq' => $faq->load(['creator'])
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create FAQ', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create FAQ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show specific FAQ details
     */
    public function show(Faq $faq): View
    {
        $this->checkAccess();
        $user = \Auth::user();

        $faq->load(['creator', 'updater']);

        return view('admin.support.faq.show', compact('faq', 'user'));
    }

    /**
     * Show the form for editing FAQ
     */
    public function edit(Faq $faq): View
    {
        $this->checkAccess();
        $user = \Auth::user();
        
        $categories = Faq::getCategories();
        
        return view('admin.support.faq.edit', compact('faq', 'categories', 'user'));
    }

    /**
     * Update FAQ
     */
    public function update(Request $request, Faq $faq): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'required|string|in:' . implode(',', array_keys(Faq::getCategories())),
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
            'is_featured' => 'boolean',
            'tags' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Process tags
            $tags = null;
            if ($request->filled('tags')) {
                $tags = array_map('trim', explode(',', $validated['tags']));
                $tags = array_filter($tags); // Remove empty tags
            }

            $faq->update([
                'question' => $validated['question'],
                'answer' => $validated['answer'],
                'category' => $validated['category'],
                'status' => $validated['status'],
                'sort_order' => $validated['sort_order'] ?? $faq->sort_order,
                'is_featured' => $validated['is_featured'] ?? false,
                'tags' => $tags,
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            Log::info('FAQ updated', [
                'faq_id' => $faq->id,
                'question' => $faq->question,
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'FAQ updated successfully',
                'faq' => $faq->load(['creator', 'updater'])
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update FAQ', [
                'faq_id' => $faq->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update FAQ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete FAQ
     */
    public function destroy(Faq $faq): JsonResponse
    {
        $this->checkAccess();

        try {
            $faqId = $faq->id;
            $question = $faq->question;
            
            $faq->delete();

            Log::info('FAQ deleted', [
                'faq_id' => $faqId,
                'question' => $question,
                'deleted_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'FAQ deleted successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to delete FAQ', [
                'faq_id' => $faq->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete FAQ'
            ], 500);
        }
    }

    /**
     * Toggle FAQ status
     */
    public function toggleStatus(Faq $faq): JsonResponse
    {
        $this->checkAccess();

        try {
            $newStatus = $faq->status === 'active' ? 'inactive' : 'active';
            $faq->update([
                'status' => $newStatus,
                'updated_by' => auth()->id()
            ]);

            Log::info('FAQ status toggled', [
                'faq_id' => $faq->id,
                'new_status' => $newStatus,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "FAQ {$newStatus} successfully",
                'status' => $newStatus
            ]);

        } catch (Exception $e) {
            Log::error('Failed to toggle FAQ status', [
                'faq_id' => $faq->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(Faq $faq): JsonResponse
    {
        $this->checkAccess();

        try {
            $newFeatured = !$faq->is_featured;
            $faq->update([
                'is_featured' => $newFeatured,
                'updated_by' => auth()->id()
            ]);

            Log::info('FAQ featured status toggled', [
                'faq_id' => $faq->id,
                'is_featured' => $newFeatured,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => $newFeatured ? 'FAQ marked as featured' : 'FAQ removed from featured',
                'is_featured' => $newFeatured
            ]);

        } catch (Exception $e) {
            Log::error('Failed to toggle FAQ featured status', [
                'faq_id' => $faq->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update featured status'
            ], 500);
        }
    }

    /**
     * Update sort order
     */
    public function updateOrder(Request $request): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:faqs,id',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            foreach ($validated['items'] as $item) {
                Faq::where('id', $item['id'])->update([
                    'sort_order' => $item['sort_order'],
                    'updated_by' => auth()->id()
                ]);
            }

            DB::commit();

            Log::info('FAQ order updated', [
                'items_count' => count($validated['items']),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'FAQ order updated successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update FAQ order', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update order'
            ], 500);
        }
    }

    /**
     * Bulk actions
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'action' => 'required|in:activate,deactivate,feature,unfeature,delete',
            'faq_ids' => 'required|array',
            'faq_ids.*' => 'exists:faqs,id',
        ]);

        try {
            DB::beginTransaction();

            $faqs = Faq::whereIn('id', $validated['faq_ids']);
            $count = $faqs->count();

            switch ($validated['action']) {
                case 'activate':
                    $faqs->update(['status' => 'active', 'updated_by' => auth()->id()]);
                    $message = "{$count} FAQs activated successfully";
                    break;

                case 'deactivate':
                    $faqs->update(['status' => 'inactive', 'updated_by' => auth()->id()]);
                    $message = "{$count} FAQs deactivated successfully";
                    break;

                case 'feature':
                    $faqs->update(['is_featured' => true, 'updated_by' => auth()->id()]);
                    $message = "{$count} FAQs marked as featured";
                    break;

                case 'unfeature':
                    $faqs->update(['is_featured' => false, 'updated_by' => auth()->id()]);
                    $message = "{$count} FAQs removed from featured";
                    break;

                case 'delete':
                    $faqs->delete();
                    $message = "{$count} FAQs deleted successfully";
                    break;
            }

            DB::commit();

            Log::info('FAQ bulk action performed', [
                'action' => $validated['action'],
                'count' => $count,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to perform bulk action', [
                'action' => $validated['action'],
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk action'
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function getStatistics(): JsonResponse
    {
        $this->checkAccess();

        try {
            $stats = [
                'total_faqs' => Faq::count(),
                'active_faqs' => Faq::where('status', 'active')->count(),
                'inactive_faqs' => Faq::where('status', 'inactive')->count(),
                'featured_faqs' => Faq::where('is_featured', true)->count(),
                'total_views' => Faq::sum('views'),
                'faqs_by_category' => $this->getFaqsByCategory(),
                'faqs_by_status' => $this->getFaqsByStatus(),
                'recent_activity' => $this->getRecentActivity(),
                'top_viewed' => Faq::orderBy('views', 'desc')->limit(5)->get(['id', 'question', 'views']),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics'
            ], 500);
        }
    }

    /**
     * Search FAQs
     */
    public function search(Request $request): JsonResponse
    {
        $this->checkAccess();

        $query = $request->get('q', '');
        
        $faqs = Faq::search($query)
            ->with(['creator'])
            ->limit(10)
            ->get(['id', 'question', 'category', 'status', 'views']);

        return response()->json([
            'success' => true,
            'faqs' => $faqs
        ]);
    }

    /**
     * Export FAQs to CSV
     */
    public function export(Request $request)
    {
        $this->checkAccess();

        $query = Faq::with(['creator', 'updater']);

        // Apply same filters as the main listing
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $faqs = $query->orderBy('sort_order')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="faqs_' . now()->format('Y-m-d_H-i-s') . '.csv"',
        ];

        $callback = function() use ($faqs) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID',
                'Question',
                'Answer',
                'Category',
                'Status',
                'Featured',
                'Sort Order',
                'Views',
                'Tags',
                'Created By',
                'Created At',
                'Updated By',
                'Updated At'
            ]);

            // CSV data
            foreach ($faqs as $faq) {
                fputcsv($file, [
                    $faq->id,
                    $faq->question,
                    strip_tags($faq->answer),
                    $faq->category_text,
                    $faq->status_text,
                    $faq->is_featured ? 'Yes' : 'No',
                    $faq->sort_order,
                    $faq->views,
                    $faq->tags ? implode(', ', $faq->tags) : '',
                    $faq->creator->name ?? 'Unknown',
                    $faq->created_at->format('Y-m-d H:i:s'),
                    $faq->updater->name ?? '',
                    $faq->updated_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Private helper methods
     */
    private function getFaqsByCategory(): array
    {
        return Faq::selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();
    }

    private function getFaqsByStatus(): array
    {
        return Faq::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    private function getRecentActivity(): array
    {
        return Faq::with(['creator'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get(['id', 'question', 'status', 'updated_at', 'created_by'])
            ->toArray();
    }

    /**
     * Check admin access
     */
    private function checkAccess(): void
    {
        if (!auth()->user()->hasStaffPrivileges()) {
            abort(403, 'Access denied. Staff privileges required.');
        }
    }
}