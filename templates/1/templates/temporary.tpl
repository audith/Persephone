<ul style="width:95%; clear:both; margin:0 auto; padding:2px; list-style:none;">
{{foreach from=$CONTENT item=ROW}}
	<li style="margin:10px 0 10px 0; float:left; clear:both; width:100%;">
	{{foreach from=$ROW key=FIELD item=DATA}}
		<span style="width:20%; clear:left; float:left; font-weight:bold; font-style:oblique;">
			{{$FIELD}}
		</span>
		<span style="width:75%; clear:right; float:left; font-weight:normal;">
			{{$DATA}}
		</span>
	{{/foreach}}
	</li>
{{/foreach}}
</ul>