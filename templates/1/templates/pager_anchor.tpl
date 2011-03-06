<ul>
{{foreach from=$PAGER item=COMPONENT}}
	{{if $COMPONENT._is_current eq TRUE}}
	<li class="_is_current"><a href='?{{http_build_query key="_page[$instance]" value=$COMPONENT.value action="alter_add"}}'>{{$COMPONENT.value}}</a></li>
	{{elseif $COMPONENT._is_first eq TRUE}}
	<li class="_is_first"><a href='?{{http_build_query key="_page[$instance]" value=$COMPONENT.value action="alter_add"}}'>{{$COMPONENT.value}}</a></li>
	{{elseif $COMPONENT._is_dump eq TRUE}}
	<li class="_is_dump">{{$COMPONENT.value}}</li>
	{{elseif $COMPONENT._is_last eq TRUE}}
	<li class="_is_last"><a href='?{{http_build_query key="_page[$instance]" value=$COMPONENT.value action="alter_add"}}'>{{$COMPONENT.value}}</a></li>
	{{else}}
	<li><a href='?{{http_build_query key="_page[$instance]" value=$COMPONENT.value action="alter_add"}}'>{{$COMPONENT.value}}</a></li>
	{{/if}}
{{/foreach}}
</ul>