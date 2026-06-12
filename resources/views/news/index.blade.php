@extends('layouts.app')
@section('page-title', 'Новости')
@push('styles')
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a0a2e 50%, #0a0a0a 100%);
            color: #ffffff;
            min-height: 100vh;
        }

        .container {
            max-width: 960px;
            margin: 0 auto;
            padding: 20px 16px 60px;
        }

        /* HEADER */
        .header {
            text-align: center;
            padding: 36px 16px 28px;
            position: relative;
        }

        .nav-left {
            position: absolute;
            top: 20px;
            left: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-chip {
            padding: 5px 12px;
            border-radius: 999px;
            border: 1px solid rgba(148,163,184,0.5);
            background: rgba(15,23,42,0.9);
            color: #e5e7eb;
            text-decoration: none;
            font-size: 12px;
            transition: all 0.2s;
        }

        .nav-chip:hover {
            border-color: #a855f7;
            color: #e9d5ff;
            box-shadow: 0 4px 12px rgba(168,85,247,0.3);
        }

        .logo-container {
            margin-bottom: 16px;
            display: flex;
            justify-content: center;
        }

        .logo-image {
            max-width: 120px;
            height: auto;
            filter: drop-shadow(0 4px 16px rgba(248,113,113,0.4));
        }

        .header-title {
            font-size: 28px;
            font-weight: bold;
            background: linear-gradient(to right, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 6px;
        }

        .header-subtitle {
            font-size: 14px;
            color: #94a3b8;
        }

        /* FILTERS */
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 24px;
        }

        .search-form {
            display: flex;
            gap: 8px;
            flex: 1;
            min-width: 200px;
        }

        .search-input {
            flex: 1;
            border-radius: 10px;
            border: 1px solid rgba(148,163,184,0.4);
            background: rgba(15,23,42,0.8);
            padding: 8px 12px;
            font-size: 13px;
            color: #e5e7eb;
            outline: none;
            transition: all 0.25s;
        }

        .search-input:focus {
            border-color: #a855f7;
            box-shadow: 0 0 0 1px rgba(168,85,247,0.5);
        }

        .search-btn {
            border: none;
            border-radius: 10px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 500;
            color: #f9fafb;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            cursor: pointer;
            transition: all 0.25s;
            white-space: nowrap;
        }

        .search-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(168,85,247,0.35);
        }

        .coin-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 20px;
        }

        .coin-tag {
            padding: 4px 12px;
            border-radius: 999px;
            border: 1px solid rgba(168,85,247,0.4);
            background: rgba(168,85,247,0.1);
            color: #c4b5fd;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
        }

        .coin-tag:hover, .coin-tag.active {
            background: rgba(168,85,247,0.25);
            border-color: #a855f7;
            color: #e9d5ff;
        }

        .coin-tag.active {
            font-weight: 600;
        }

        .clear-tag {
            padding: 4px 12px;
            border-radius: 999px;
            border: 1px solid rgba(239,68,68,0.4);
            background: rgba(239,68,68,0.1);
            color: #fca5a5;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .clear-tag:hover {
            background: rgba(239,68,68,0.2);
        }

        /* NEWS GRID */
        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 18px;
        }

        .news-card {
            background: rgba(15,23,42,0.7);
            border: 1px solid rgba(168,85,247,0.25);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s;
        }

        .news-card:hover {
            border-color: rgba(168,85,247,0.5);
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(168,85,247,0.2);
        }

        .news-thumb {
            width: 100%;
            height: 160px;
            object-fit: cover;
            background: rgba(30,41,59,0.8);
        }

        .news-thumb-placeholder {
            width: 100%;
            height: 160px;
            background: linear-gradient(135deg, rgba(30,41,59,0.9), rgba(88,28,135,0.3));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
        }

        .news-body {
            padding: 14px 16px 16px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .news-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .news-source {
            font-size: 11px;
            color: #a855f7;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .news-date {
            font-size: 11px;
            color: #64748b;
        }

        .news-title {
            font-size: 14px;
            font-weight: 600;
            color: #f1f5f9;
            line-height: 1.45;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .news-desc {
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex: 1;
        }

        .news-coins {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: auto;
        }

        .news-coin-badge {
            padding: 2px 8px;
            border-radius: 999px;
            background: rgba(168,85,247,0.15);
            border: 1px solid rgba(168,85,247,0.3);
            font-size: 10px;
            color: #c4b5fd;
            font-weight: 600;
        }

        /* PAGINATION */
        .pagination-wrap {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            margin-top: 36px;
            flex-wrap: wrap;
        }

        .page-btn {
            padding: 7px 14px;
            border-radius: 10px;
            border: 1px solid rgba(168,85,247,0.35);
            background: rgba(15,23,42,0.8);
            color: #c4b5fd;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .page-btn:hover {
            background: rgba(168,85,247,0.2);
            border-color: #a855f7;
        }

        .page-btn.active {
            background: linear-gradient(135deg, #a855f7, #ec4899);
            border-color: transparent;
            color: #fff;
            font-weight: 600;
        }

        .page-btn.disabled {
            opacity: 0.35;
            pointer-events: none;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }
    </style>
@endpush
@section('content')
<div class="container" style="max-width:100%;padding:0">
    <p style="font-size:13px;color:#94a3b8;margin-bottom:16px">{{ $news->total() }} новостей · Свежие аналитические материалы</p>

    {{-- Поиск --}}
    <form method="GET" action="{{ route('news.index') }}" class="filters">
        <div class="search-form">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Поиск по заголовку или описанию..."
                class="search-input"
            >
            @if($coin)
                <input type="hidden" name="coin" value="{{ $coin }}">
            @endif
            <button type="submit" class="search-btn">Найти</button>
        </div>
    </form>

    {{-- Популярные монеты --}}
    @if($popularCoins)
        <div class="coin-filters">
            @if($coin || $search)
                <a href="{{ route('news.index') }}" class="clear-tag">× Сбросить</a>
            @endif
            @foreach($popularCoins as $c)
                <a href="{{ route('news.index', array_filter(['coin' => $c, 'search' => $search])) }}"
                   class="coin-tag {{ $coin === $c ? 'active' : '' }}">
                    {{ $c }}
                </a>
            @endforeach
        </div>
    @endif

    {{-- Список новостей --}}
    @if($news->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">📭</div>
            <div>Новостей не найдено</div>
        </div>
    @else
        <div class="news-grid">
            @foreach($news as $item)
                <a href="{{ route('news.show', $item) }}" class="news-card">
                    @if($item->image_url)
                        <img src="{{ $item->image_url }}" alt="" class="news-thumb" loading="lazy"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                        <div class="news-thumb-placeholder" style="display:none">📰</div>
                    @else
                        <div class="news-thumb-placeholder">📰</div>
                    @endif

                    <div class="news-body">
                        <div class="news-meta">
                            @if($item->source_name)
                                <span class="news-source">{{ $item->source_name }}</span>
                                <span style="color:#334155">·</span>
                            @endif
                            <span class="news-date">
                                {{ $item->pub_date ? $item->pub_date->diffForHumans() : '—' }}
                            </span>
                        </div>

                        <div class="news-title">{{ $item->title }}</div>

                        @if($item->description)
                            <div class="news-desc">{{ $item->description }}</div>
                        @endif

                        @if($item->coin)
                            <div class="news-coins">
                                @foreach(array_slice($item->coin, 0, 4) as $c)
                                    <span class="news-coin-badge">{{ $c }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Пагинация --}}
        @if($news->lastPage() > 1)
            <div class="pagination-wrap">
                @if($news->onFirstPage())
                    <span class="page-btn disabled">← Назад</span>
                @else
                    <a href="{{ $news->previousPageUrl() }}" class="page-btn">← Назад</a>
                @endif

                @foreach($news->getUrlRange(max(1, $news->currentPage()-2), min($news->lastPage(), $news->currentPage()+2)) as $page => $url)
                    <a href="{{ $url }}" class="page-btn {{ $page === $news->currentPage() ? 'active' : '' }}">
                        {{ $page }}
                    </a>
                @endforeach

                @if($news->hasMorePages())
                    <a href="{{ $news->nextPageUrl() }}" class="page-btn">Вперёд →</a>
                @else
                    <span class="page-btn disabled">Вперёд →</span>
                @endif
            </div>
        @endif
    @endif

</div>
@endsection
