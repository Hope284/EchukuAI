<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Page;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = collect([
            $this->urlEntry(url('/'), now(), 'daily', '1.0'),
            $this->urlEntry(route('blog.index'), now(), 'daily', '0.8'),
            $this->urlEntry(route('privacy'), now(), 'monthly', '0.3'),
            $this->urlEntry(route('terms'), now(), 'monthly', '0.3'),
        ]);

        Page::query()
            ->where('status', 1)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->get()
            ->each(function (Page $page) use ($urls): void {
                $urls->push($this->urlEntry(
                    route('pageContent', $page->slug),
                    $page->updated_at,
                    'weekly',
                    '0.6'
                ));
            });

        Blog::query()
            ->where('status', 1)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->get()
            ->each(function (Blog $post) use ($urls): void {
                $urls->push($this->urlEntry(
                    route('blog.post', $post->slug),
                    $post->updated_at,
                    'weekly',
                    '0.7'
                ));
            });

        $xml = view('sitemap.index', ['urls' => $urls])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /**
     * @return array{loc: string, lastmod: string, changefreq: string, priority: string}
     */
    private function urlEntry(string $loc, Carbon|string|null $lastmod, string $changefreq, string $priority): array
    {
        return [
            'loc'        => $loc,
            'lastmod'    => ($lastmod instanceof Carbon ? $lastmod : now())->toAtomString(),
            'changefreq' => $changefreq,
            'priority'   => $priority,
        ];
    }
}
