<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HelpCenterController extends Controller
{
    public function index(Request $request)
    {
        $topics = trans('help.topics');

        if (!is_array($topics) || empty($topics)) {
            $topics = [];
        }

        $quickLinks = trans('help.quick_links');
        if (!is_array($quickLinks) || empty($quickLinks)) {
            $quickLinks = [];
        }

        $categorySlug = $request->query('categoria');
        $articleSlug = $request->query('articulo');
        $searchTerm = trim((string) $request->query('buscar', ''));

        $selectedCategorySlug = null;
        $selectedArticleSlug = null;
        $selectedCategory = null;
        $selectedArticle = null;

        if ($categorySlug && isset($topics[$categorySlug])) {
            $selectedCategorySlug = $categorySlug;
            $selectedCategory = $topics[$categorySlug];
        }

        if (!$selectedCategory) {
            $selectedCategorySlug = array_key_first($topics);
            if ($selectedCategorySlug !== null) {
                $selectedCategory = $topics[$selectedCategorySlug];
            }
        }

        if ($selectedCategory && isset($selectedCategory['articles']) && is_array($selectedCategory['articles'])) {
            if ($articleSlug && isset($selectedCategory['articles'][$articleSlug])) {
                $selectedArticleSlug = $articleSlug;
                $selectedArticle = $selectedCategory['articles'][$articleSlug];
            }

            if (!$selectedArticle) {
                $selectedArticleSlug = array_key_first($selectedCategory['articles']);
                if ($selectedArticleSlug !== null) {
                    $selectedArticle = $selectedCategory['articles'][$selectedArticleSlug];
                }
            }
        }

        $searchResults = [];
        if (mb_strlen($searchTerm) >= 2) {
            $needle = mb_strtolower($searchTerm);
            foreach ($topics as $topicSlug => $topic) {
                $articles = $topic['articles'] ?? [];
                if (!is_array($articles)) {
                    continue;
                }
                foreach ($articles as $articleKey => $article) {
                    $haystack = mb_strtolower(
                        implode(' ', [
                            $article['title'] ?? '',
                            $article['summary'] ?? '',
                            strip_tags($article['content'] ?? ''),
                            implode(' ', $article['tags'] ?? []),
                        ])
                    );

                    if (Str::contains($haystack, $needle)) {
                        $searchResults[] = [
                            'category_slug' => $topicSlug,
                            'category_title' => $topic['title'] ?? '',
                            'article_slug' => $articleKey,
                            'title' => $article['title'] ?? '',
                            'summary' => $article['summary'] ?? '',
                        ];
                    }
                }
            }
        }

        return view('help.index', [
            'topics' => $topics,
            'quickLinks' => $quickLinks,
            'selectedCategorySlug' => $selectedCategorySlug,
            'selectedArticleSlug' => $selectedArticleSlug,
            'selectedCategory' => $selectedCategory,
            'selectedArticle' => $selectedArticle,
            'searchTerm' => $searchTerm,
            'searchResults' => $searchResults,
            'topicsJson' => json_encode($topics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
