{{foreach from=$RSS item=RSS_LINK}}
<link rel="alternate" type="application/rss+xml" title="{{$RSS_LINK.TITLE}}" href="{{$RSS_LINK.HREF}}" />
{{/foreach}}