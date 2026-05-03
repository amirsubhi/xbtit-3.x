<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title>{{ config('app.name') }} — News</title>
    <link>{{ url('/news') }}</link>
    <description>Latest news from {{ config('app.name') }}</description>
    <language>en</language>
    <atom:link href="{{ url('/rss/news') }}" rel="self" type="application/rss+xml"/>
    @foreach($articles as $article)
    <item>
        <title>{{ htmlspecialchars($article->title, ENT_XML1) }}</title>
        <link>{{ route('news.show', $article->id) }}</link>
        <guid isPermaLink="true">{{ route('news.show', $article->id) }}</guid>
        <pubDate>{{ $article->created_at->toRssString() }}</pubDate>
        @if($article->author)
        <author>{{ htmlspecialchars($article->author->email . ' (' . $article->author->username . ')', ENT_XML1) }}</author>
        @endif
        <description><![CDATA[{{ Str::limit(strip_tags($article->body), 300) }}]]></description>
    </item>
    @endforeach
</channel>
</rss>
