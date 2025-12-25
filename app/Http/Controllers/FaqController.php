<?php
// app/Http/Controllers/FaqController.php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class FaqController extends Controller
{
    public function index(Request $request): View
    {
        $query = Faq::active()->ordered();
        $user = \Auth::user();

        // Apply category filter
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        // Apply search filter
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $faqs = $query->get();

        // Get available categories
        $availableCategories = Faq::active()
            ->select('category')
            ->distinct()
            ->pluck('category')
            ->mapWithKeys(function ($category) {
                return [$category => Faq::getCategories()[$category] ?? ucfirst($category)];
            });

        // Get featured FAQs
        $featuredFaqs = Faq::active()->featured()->ordered()->limit(6)->get();

        // Group FAQs by category
        $faqsByCategory = $faqs->groupBy('category');

        // Statistics
        $stats = [
            'total_faqs' => Faq::active()->count(),
            'total_categories' => $availableCategories->count(),
            'featured_count' => $featuredFaqs->count(),
        ];

        $totalViews = Faq::sum('views');

        return view('support.faqs', compact(
            'faqs',
            'availableCategories',
            'featuredFaqs',
            'faqsByCategory',
            'stats',
            'totalViews',
            'user'
        ));
    }

    public function show(Faq $faq): View
    {
        $user = \Auth::user();

        // Only show active FAQs to regular users
        if ($faq->status !== 'active') {
            abort(404);
        }

        // Increment view count
        $faq->incrementViews();

        // Load related FAQs from same category
        $relatedFaqs = Faq::active()
            ->byCategory($faq->category)
            ->where('id', '!=', $faq->id)
            ->ordered()
            ->limit(5)
            ->get();

        return view('support.faqs_show', compact('faq', 'relatedFaqs', 'user'));
    }

    public function search(Request $request): JsonResponse
    {
        $search = $request->get('q', '');
        
        if (strlen($search) < 3) {
            return response()->json([
                'success' => false,
                'message' => 'Search query must be at least 3 characters long'
            ]);
        }

        $faqs = Faq::active()
            ->search($search)
            ->ordered()
            ->limit(10)
            ->get(['id', 'question', 'category', 'views']);

        return response()->json([
            'success' => true,
            'faqs' => $faqs->map(function ($faq) {
                return [
                    'id' => $faq->id,
                    'question' => $faq->question,
                    'category' => $faq->category_text,
                    'views' => number_format($faq->views),
                    'url' => route('user.faq.show', $faq)
                ];
            })
        ]);
    }
}