@extends('layout')

@section('content')
<div class="row g-4" id="helpCenterLayout"
     data-topics='@json($topicsJson)' data-initial-category="{{ $selectedCategorySlug }}" data-initial-article="{{ $selectedArticleSlug }}">
    <div class="col-12">
        <div class="p-4 rounded shadow-sm bg-white">
            <h1 class="mb-1">{{ __('help.title') }}</h1>
            <p class="text-muted mb-3">{{ __('help.subtitle') }}</p>

            <form action="{{ route('help.index') }}" method="GET" class="row g-2 align-items-center" role="search">
                <div class="col-md-9">
                    <label class="visually-hidden" for="help-search">{{ __('help.search_placeholder') }}</label>
                    <input type="search"
                           id="help-search"
                           name="buscar"
                           class="form-control form-control-lg"
                           placeholder="{{ __('help.search_placeholder') }}"
                           value="{{ $searchTerm }}"
                           minlength="2">
                    <div class="form-text">{{ __('help.search_hint') }}</div>
                </div>
                <div class="col-md-3 d-grid d-md-block">
                    <button class="btn btn-primary btn-lg w-100" type="submit">{{ __('help.search_action') }}</button>
                </div>
            </form>

            @if(!empty($searchTerm))
                <div class="mt-4">
                    <h2 class="h5">{{ __('help.search_results_title') }}</h2>
                    @if(empty($searchResults))
                        <p class="text-muted mb-0">{{ __('help.search_results_none') }}</p>
                    @else
                        <div class="list-group">
                            @foreach($searchResults as $result)
                                <a href="{{ route('help.index', ['categoria' => $result['category_slug'], 'articulo' => $result['article_slug']]) }}"
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between">
                                        <h3 class="h6 mb-1">{{ $result['title'] }}</h3>
                                        <span class="badge bg-secondary">{{ $result['category_title'] }}</span>
                                    </div>
                                    <p class="mb-0 text-muted">{{ $result['summary'] }}</p>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="col-lg-8 order-lg-2">
        <div class="p-4 rounded shadow-sm bg-white h-100">
            @if($selectedArticle)
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div>
                        <span class="badge bg-primary mb-2">{{ $selectedCategory['title'] ?? '' }}</span>
                        <h2>{{ $selectedArticle['title'] ?? '' }}</h2>
                    </div>
                    @if(!empty($selectedArticle['estimated_time']))
                        <div class="text-muted small">
                            <i class="bi bi-clock"></i> {{ $selectedArticle['estimated_time'] }}
                        </div>
                    @endif
                </div>
                <p class="lead text-muted">{{ $selectedArticle['summary'] ?? '' }}</p>

                <div class="help-article-content">
                    {!! $selectedArticle['content'] ?? '' !!}
                </div>

                @if(!empty($selectedArticle['tags']))
                    <div class="mt-4">
                        @foreach($selectedArticle['tags'] as $tag)
                            <span class="badge rounded-pill bg-light text-dark border">#{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif
            @else
                <p class="text-muted mb-0">Selecciona un artículo en la barra lateral para comenzar.</p>
            @endif
        </div>
    </div>

    <div class="col-lg-4 order-lg-1">
        <div class="p-4 rounded shadow-sm bg-white mb-4">
            <h3 class="h6 text-uppercase text-muted mb-3">{{ __('help.quick_links_title') }}</h3>
            <div class="list-group list-group-flush">
                @forelse($quickLinks as $link)
                    <a href="{{ route('help.index', ['categoria' => $link['category'], 'articulo' => $link['article']]) }}"
                       class="list-group-item list-group-item-action">
                        <div class="d-flex align-items-start">
                            <div class="me-3">
                                <span class="badge bg-primary-subtle text-primary rounded-pill">●</span>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ $link['label'] }}</div>
                                <div class="small text-muted">{{ $link['description'] }}</div>
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="text-muted mb-0">Pronto agregaremos atajos populares.</p>
                @endforelse
            </div>
        </div>

        <div class="p-4 rounded shadow-sm bg-white">
            <h3 class="h6 text-uppercase text-muted mb-3">Categorías</h3>
            <div class="nav nav-pills flex-column" id="helpCategories">
                @forelse($topics as $topicSlug => $topic)
                    <button class="nav-link text-start {{ $topicSlug === $selectedCategorySlug ? 'active' : '' }}"
                            data-category="{{ $topicSlug }}">
                        <div class="fw-semibold">{{ $topic['title'] ?? $topicSlug }}</div>
                        <small class="text-muted">{{ $topic['summary'] ?? '' }}</small>
                    </button>
                @empty
                    <span class="text-muted">No hay categorías de ayuda configuradas.</span>
                @endforelse
            </div>
        </div>

        <div class="p-4 rounded shadow-sm bg-white mt-4">
            <h3 class="h6 text-uppercase text-muted mb-3">Artículos</h3>
            <div class="list-group list-group-flush" id="helpArticles">
                @if($selectedCategory && !empty($selectedCategory['articles']))
                    @foreach($selectedCategory['articles'] as $articleKey => $article)
                        <a href="{{ route('help.index', ['categoria' => $selectedCategorySlug, 'articulo' => $articleKey]) }}"
                           class="list-group-item list-group-item-action {{ $articleKey === $selectedArticleSlug ? 'active' : '' }}"
                           data-article="{{ $articleKey }}">
                            <div class="fw-semibold">{{ $article['title'] ?? $articleKey }}</div>
                            <div class="small text-muted">{{ $article['summary'] ?? '' }}</div>
                        </a>
                    @endforeach
                @else
                    <span class="text-muted">Selecciona una categoría para ver artículos.</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    #helpCenterLayout {
        --help-bg: #f8f9fb;
    }
    body {
        background-color: var(--help-bg);
    }
    .help-article-content h2,
    .help-article-content h3,
    .help-article-content h4 {
        margin-top: 1.5rem;
    }
    .help-article-content ul,
    .help-article-content ol {
        padding-left: 1.5rem;
    }
    .help-article-content li + li {
        margin-top: 0.35rem;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const layout = document.getElementById('helpCenterLayout');
        if (!layout) {
            return;
        }

        const topicsJson = layout.dataset.topics ? JSON.parse(layout.dataset.topics) : {};
        const categoriesNav = document.getElementById('helpCategories');
        const articlesList = document.getElementById('helpArticles');

        if (!categoriesNav) return;

        categoriesNav.addEventListener('click', (event) => {
            const btn = event.target.closest('[data-category]');
            if (!btn) return;

            const category = btn.dataset.category;
            const url = new URL(window.location.href);
            url.searchParams.set('categoria', category);
            url.searchParams.delete('articulo');
            url.searchParams.delete('buscar');
            window.location.href = url.toString();
        });

        if (articlesList) {
            articlesList.addEventListener('click', (event) => {
                const link = event.target.closest('[data-article]');
                if (!link) return;

                const article = link.dataset.article;
                const url = new URL(window.location.href);
                url.searchParams.set('articulo', article);
                url.searchParams.delete('buscar');
                window.location.href = url.toString();
            });
        }
    });
</script>
@endpush
