<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title>{{ config('app.name') }} — Latest Torrents</title>
    <link>{{ url('/torrents') }}</link>
    <description>Latest torrents on {{ config('app.name') }}</description>
    <language>en</language>
    <atom:link href="{{ url('/rss/torrents') }}" rel="self" type="application/rss+xml"/>
    @foreach($torrents as $torrent)
    @php
        $cat   = $torrent->category?->name ?? '';
        $title = ($cat ? "[{$cat}] " : '') . $torrent->filename
               . ' [S(' . $torrent->seeds . ')/L(' . $torrent->leechers . ')]';
        $downloadUrl = route('torrents.download', $torrent->info_hash);
    @endphp
    <item>
        <title>{{ htmlspecialchars($title, ENT_XML1) }}</title>
        <link>{{ route('torrents.show', $torrent->info_hash) }}</link>
        <guid isPermaLink="true">{{ route('torrents.show', $torrent->info_hash) }}</guid>
        <pubDate>{{ $torrent->created_at->toRssString() }}</pubDate>
        @if($torrent->category)
        <category>{{ htmlspecialchars($cat, ENT_XML1) }}</category>
        @endif
        <enclosure url="{{ $downloadUrl }}" length="{{ $torrent->size ?? 0 }}" type="application/x-bittorrent"/>
        <description><![CDATA[
            Size: {{ $torrent->size ? number_format($torrent->size / 1048576, 1) . ' MB' : 'unknown' }}<br>
            Seeds: {{ $torrent->seeds }} &bull; Leechers: {{ $torrent->leechers }}<br>
            Category: {{ $cat ?: 'Uncategorized' }}
        ]]></description>
    </item>
    @endforeach
</channel>
</rss>
