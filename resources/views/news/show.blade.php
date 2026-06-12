@extends('layouts.app')
@section('page-title', Str::limit($news->title, 60))
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
            max-width: 800px;
            margin: 0 auto;
            padding: 20px 16px 60px;
        }

        /* NAV */
        .nav {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 24px 0 32px;
        }

        .nav-chip {
            padding: 6px 14px;
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

        /* ARTICLE */
        .article-header {
            margin-bottom: 28px;
        }

        .article-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }

        .source-badge {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #a855f7;
            padding: 3px 10px;
            border-radius: 999px;
            border: 1px solid rgba(168,85,247,0.4);
            background: rgba(168,85,247,0.1);
        }

        .article-date {
            font-size: 13px;
            color: #64748b;
        }

        .article-creator {
            font-size: 13px;
            color: #94a3b8;
        }

        .article-title {
            font-size: 26px;
            font-weight: 700;
            line-height: 1.35;
            color: #f1f5f9;
            margin-bottom: 16px;
        }

        .article-coins {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 20px;
        }

        .coin-badge {
            padding: 4px 12px;
            border-radius: 999px;
            background: rgba(168,85,247,0.15);
            border: 1px solid rgba(168,85,247,0.35);
            font-size: 12px;
            font-weight: 600;
            color: #c4b5fd;
            text-decoration: none;
            transition: all 0.2s;
        }

        .coin-badge:hover {
            background: rgba(168,85,247,0.3);
            color: #e9d5ff;
        }

        .keyword-badge {
            padding: 3px 10px;
            border-radius: 999px;
            background: rgba(30,41,59,0.8);
            border: 1px solid rgba(100,116,139,0.4);
            font-size: 11px;
            color: #94a3b8;
        }

        .article-image {
            width: 100%;
            max-height: 360px;
            object-fit: cover;
            border-radius: 16px;
            margin-bottom: 24px;
            border: 1px solid rgba(168,85,247,0.2);
        }

        .article-description {
            font-size: 16px;
            color: #cbd5e1;
            line-height: 1.7;
            margin-bottom: 20px;
            padding: 16px 20px;
            background: rgba(30,41,59,0.5);
            border-left: 3px solid #a855f7;
            border-radius: 0 12px 12px 0;
        }

        .article-content {
            font-size: 14px;
            color: #94a3b8;
            line-height: 1.8;
            margin-bottom: 28px;
            white-space: pre-wrap;
        }

        .external-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            border-radius: 999px;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.25s;
            margin-bottom: 40px;
        }

        .external-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(168,85,247,0.4);
        }

        /* RELATED */
        .related-section {
            border-top: 1px solid rgba(168,85,247,0.2);
            padding-top: 32px;
        }

        .related-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 18px;
            background: linear-gradient(to right, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 14px;
        }

        .related-card {
            background: rgba(15,23,42,0.7);
            border: 1px solid rgba(168,85,247,0.2);
            border-radius: 14px;
            padding: 14px 16px;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            gap: 8px;
            transition: all 0.25s;
        }

        .related-card:hover {
            border-color: rgba(168,85,247,0.5);
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(168,85,247,0.15);
        }

        .related-card-meta {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .related-source {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #a855f7;
        }

        .related-date {
            font-size: 10px;
            color: #64748b;
        }

        .related-card-title {
            font-size: 13px;
            font-weight: 600;
            color: #e2e8f0;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .related-coins {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: auto;
        }

        .related-coin-badge {
            padding: 2px 7px;
            border-radius: 999px;
            background: rgba(168,85,247,0.12);
            border: 1px solid rgba(168,85,247,0.25);
            font-size: 10px;
            color: #c4b5fd;
            font-weight: 600;
        }

        .no-related {
            color: #64748b;
            font-size: 14px;
            padding: 20px 0;
        }
    </style>
@endpush
@section('content')
<div class="container" style="max-width:800px;padding:0">
    <div style="margin-bottom:20px">
        <a href="{{ route('news.index') }}" class="nav-chip">← Все новости</a>
    </div>

    {{-- Статья --}}
    <article>
        <div class="article-header">
            <div class="article-meta">
                @if($news->source_name)
                    <span class="source-badge">{{ $news->source_name }}</span>
                @endif
                @if($news->pub_date)
                    <span class="article-date">{{ $news->pub_date->format('d.m.Y H:i') }}</span>
                @endif
                @if($news->creator)
                    <span class="article-creator">{{ implode(', ', $news->creator) }}</span>
                @endif
            </div>

            <h1 class="article-title">{{ $news->title }}</h1>

            @if($news->coin)
                <div class="article-coins">
                    @foreach($news->coin as $c)
                        <a href="{{ route('news.index', ['coin' => $c]) }}" class="coin-badge">{{ $c }}</a>
                    @endforeach
                    @if($news->keywords)
                        @foreach(array_slice($news->keywords, 0, 4) as $kw)
                            <span class="keyword-badge">{{ $kw }}</span>
                        @endforeach
                    @endif
                </div>
            @endif
        </div>

        @if($news->image_url)
            <img src="{{ $news->image_url }}" alt="{{ $news->title }}" class="article-image"
                 onerror="this.style.display='none'">
        @endif

        @if($news->description)
            <div class="article-description">{{ $news->description }}</div>
        @endif

        @if($news->content)
            <div class="article-content">{{ $news->content }}</div>
        @endif

        <a href="{{ $news->link }}" target="_blank" rel="noopener noreferrer" class="external-link">
            Читать оригинал →
        </a>
    </article>

    {{-- Связанные новости --}}
    <section class="related-section">
        <h2 class="related-title">Связанные новости</h2>

        @if($related->isEmpty())
            <p class="no-related">Связанных новостей не найдено.</p>
        @else
            <div class="related-grid">
                @foreach($related as $item)
                    <a href="{{ route('news.show', $item) }}" class="related-card">
                        <div class="related-card-meta">
                            @if($item->source_name)
                                <span class="related-source">{{ $item->source_name }}</span>
                                <span style="color:#334155">·</span>
                            @endif
                            <span class="related-date">
                                {{ $item->pub_date ? $item->pub_date->diffForHumans() : '—' }}
                            </span>
                        </div>

                        <div class="related-card-title">{{ $item->title }}</div>

                        @if($item->coin)
                            <div class="related-coins">
                                @foreach(array_slice($item->coin, 0, 3) as $c)
                                    <span class="related-coin-badge">{{ $c }}</span>
                                @endforeach
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </section>

</div>
@endsection
