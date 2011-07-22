<div class="aside full_size" id="system_console"></div>
<ul class="section full_size">
	<li id="sec_content_modules__list">
		<h2 class="full_size">Content Management - Modules
			<span class="description"></span>
		</h2>

		<form id="forms__modules__list" method="post" action="">
		<table class="full_size tablesorter" id="tables__module_list" cellpadding="0" cellspacing="0">
			<thead>
				<tr>
					<th style="width:40%; white-space:nowrap">Module Info</th>
					<th style="width:40%; white-space:nowrap; text-align:center">Module Unique-Id</th>
					<th style="width:15%; white-space:nowrap; text-align:center">(De)Activate?</th>
					<th style="width:5%; text-align:center"></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="4">
					<span class="buttons">
					<input type="button" value="Content Management" />
					</span>
					</td>
				</tr>
			</tfoot>
			<tbody>
				{{foreach from=$CONTENT item=MODULES}}
				{{if $MODULES.m_type neq 'built-in'}}
				<tr>
					<td><strong>/{{$MODULES.m_name|truncate:32}}</strong><em>{{$MODULES.m_description|truncate:48}}</em></td>
					<td style="text-align:center">{{$MODULES.m_unique_id}}</td>
					<td style="text-align:center">{{if $MODULES.m_is_enabled eq '1'}}<img src="{{$STYLE_IMAGES_URL}}/button_ok_1_16.png" alt="Yes" class="absmiddle" />
					{{else}}<img src="{{$STYLE_IMAGES_URL}}/button_no_1_16.png" alt="No" class="absmiddle" />
					{{/if}}
					</td>
					<td style="text-align:center">{{if $MODULES.m_can_remove eq '1' and $MODULES.m_is_enabled eq '0'}}<input type="radio" name="m_unique_id" value="{{$MODULES.m_unique_id}}" class="radio" />{{/if}}</td>
				</tr>
				{{/if}}
				{{foreachelse}}
				<tr>
					<td colspan="4"><span class="system_message_error">NO MODULES FOUND!</span></td>
				</tr>
				{{/foreach}}
			</tbody>
		</table>
		</form>
	</li>

	<li id="media_library">
		<h2 class="full_size">Media Library
			<span class="description"></span>
		</h2>

		<form id="forms__media_library" method="post" action="">
		<div id="media_library_sneak_peek"></div>
		<table class="full_size tablesorter" id="tables__media_library" cellpadding="0" cellspacing="0">
			<thead>
				<tr>
					<th style="width:7%; text-align:right;">Id</th>
					<th style="width:25%; white-space:nowrap;">Checksum</th>
					<th style="width:10%; white-space:nowrap;">Type</th>
					<th style="width:10%; white-space:nowrap;">Size</th>
					<th style="width:15%; text-align:center; white-space:nowrap;">Date Modified</th>
					<th style="width:33%; white-space:nowrap;">Actions</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="6">
					<div class="pager">
					{{pager total_nr_of_items=$CONTENT.media_library__total_nr_of_items nr_of_items_per_page="20"}}
					</div>
					<span class="buttons">
					<input type="hidden" name="current_page" value="1" />
					<input type="button" value="Delete Selected" />
					</span>
					</td>
				</tr>
			</tfoot>
			<tbody>
				{{foreach from=$CONTENT.media_library__file_list item=FILE}}
				<tr id="{{$FILE.f_id}}">
					<td style="text-align:right;" class="w_preview_hover">{{$FILE.f_id}}</td>
					<td class="w_preview_hover">{{$FILE.f_hash}}</td>
					<td style="white-space:nowrap;">{{$FILE.f_extension}}</td>
					<td style="white-space:nowrap;">{{$FILE.f_size|filesize_h}}B</td>
					<td style="white-space:nowrap; text-align:center;">{{$FILE.f_timestamp|date_format:"%d-%m-%Y %R"}}</td>
					<td style="white-space:nowrap;">
					<ul class="horizontal">
					{{if $FILE.f_mime|filetype eq 'image'}}
						<li>thumb +</li>
						<li>watermark +</li>
					{{elseif $FILE.f_mime|filetype eq 'audio'}}
					Audio
					{{elseif $FILE.f_mime|filetype eq 'video'}}
					Video
					{{else}}
					Other
					{{/if}}
						<li>delete -</li>
					</ul>
					</td>
				</tr>
				{{foreachelse}}
				<tr>
					<td colspan="6"><span class="system_message_error">No Files Found!</span></td>
				</tr>
				{{/foreach}}
			</tbody>
		</table>
		</form>
	</li>
</ul>