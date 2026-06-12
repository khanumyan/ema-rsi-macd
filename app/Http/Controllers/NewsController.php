<?php

namespace App\Http\Controllers;

use App\Models\CryptoNews;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $coin = $request->query('coin');
        $search = $request->query('search');

        $query = CryptoNews::latest('pub_date');

        if ($coin) {
            $query->where('coin', 'like', "%\"{$coin}\"%");
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $news = $query->paginate(20)->withQueryString();

        $popularCoins = CryptoNews::selectRaw('coin')
            ->whereNotNull('coin')
            ->where('coin', '!=', '[]')
            ->get()
            ->flatMap(fn($n) => $n->coin ?? [])
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(10)
            ->keys()
            ->toArray();

        return view('news.index', compact('news', 'coin', 'search', 'popularCoins'));
    }

    public function show(CryptoNews $news)
    {
        $coins    = $news->coin ?? [];
        $keywords = $news->keywords ?? [];

        $related = CryptoNews::where('id', '!=', $news->id)
            ->where(function ($q) use ($coins, $keywords) {
                foreach ($coins as $coin) {
                    $q->orWhere('coin', 'like', "%\"{$coin}\"%");
                }
                foreach ($keywords as $kw) {
                    $q->orWhere('keywords', 'like', "%\"{$kw}\"%");
                }
            })
            ->latest('pub_date')
            ->limit(6)
            ->get();

        return view('news.show', compact('news', 'related'));
    }
}
