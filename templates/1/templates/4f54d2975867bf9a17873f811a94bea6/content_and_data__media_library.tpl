<samp id="system_console" class="full_size"></samp>
<ul class="data_container full_size">
	<li id="media_library">
		<h2 class="full_size">Media Library
			<span class="description"></span>
		</h2>

		<form id="forms__media_library" method="post" action="">
		<div id="media_library_sneak_peek"></div>
		<div id="jumpLoaderApplet" class="modal">

			{{if $MEMBER.user_agent_key neq 'explorer'}}
			<!-- Firefox and others -->
			<object name="jumpLoaderApplet" classid="java:jmaster.jumploader.app.JumpLoaderApplet.class" style="width:70%; height:300px; float:left; clear:left;"
				type="application/x-java-applet"
				archive="/public/java/JumpLoader/jumploader_z.jar">
				{{if $MEMBER.user_agent_key eq 'konqueror'}}
				<!-- Konqueror browser needs the following param -->
				<param name="archive" value="/public/java/JumpLoader/jumploader_z.jar" />
				{{/if}}
			{{else}}
			<!-- MSIE (Microsoft Internet Explorer -->
			<object name="jumpLoaderApplet" classid="clsid:8AD9C840-044E-11D1-B3E9-00805F499D93" style="width:70%; height:300px; float:left; clear:left;"
				codebase="http://java.sun.com/products/plugin/autodl/jinstall">
				<param name="code" value="jmaster.jumploader.app.JumpLoaderApplet" />
				<param name="archive" value="/public/java/JumpLoader/jumploader_z.jar" />
			{{/if}}

				<param name="uc_uploadUrl" value="{{$SITE_URL}}/static/upload" />
				<param name="uc_resumeCheckUrl" value="{{$SITE_URL}}/static/upload?do=resume" />
				<param name="uc_partitionLength" value="{{math equation="chunk_size * 1024" chunk_size=$CONFIG.performance.upload_chunk_size}}" />
				<param name="uc_useMd5" value="true" />
				<param name="uc_usePartitionMd5" value="true" />
				<param name="uc_calculateHashOnUploadBegin" value="true" />

				<param name="ac_fireAppletInitialized" value="true" />

				<param name="vc_lookAndFeel" value="system" />
				<param name="vc_disableLocalFileSystem" value="false" />
				<param name="vc_uploadViewAutoscrollToUploadingFile" value="true" />
				<param name="vc_uploadViewStartActionVisible" value="false" />
				<param name="vc_uploadViewStartActionAlwaysVisible" value="false" />
				<param name="vc_uploadViewStopActionVisible" value="false" />
				<param name="vc_uploadViewStopActionAlwaysVisible" value="false" />
				<param name="vc_uploadViewRetryActionVisible" value="false" />
				<param name="vc_uploadViewListStatusVisible" value="false" />
				<param name="vc_uploadViewMenuBarVisible" value="false" />
				<param name="vc_uploadViewProgressPaneVisible" value="true" />
				<param name="vc_mainViewLogoEnabled" value="false" />

				<strong>
					This browser does not have a Java Plug-in.<br />
					<a href="http://java.sun.com/products/plugin/downloads/index.html">Get the latest Java Plug-in here.</a>
				</strong>

			</object>

			<!--
			<applet name="jumpLoaderApplet"
				code="jmaster.jumploader.app.JumpLoaderApplet.class"
				archive="/public/java/JumpLoader/jumploader_z.jar"
				style="width:70%; height:300px; float:left; clear:left;"
				mayscript>
				<param name="uc_uploadUrl" value="{{$SITE_URL}}/static/upload" />
				<param name="uc_resumeCheckUrl" value="{{$SITE_URL}}/static/upload?do=resume" />
				<param name="uc_partitionLength" value="{{math equation="chunk_size * 1024" chunk_size=$CONFIG.performance.upload_chunk_size}}" />
				<param name="uc_useMd5" value="true" />
				<param name="uc_usePartitionMd5" value="true" />
				<param name="uc_calculateHashOnUploadBegin" value="true" />

				<param name="ac_fireAppletInitialized" value="true" />

				<param name="vc_lookAndFeel" value="system" />
				<param name="vc_disableLocalFileSystem" value="false" />
				<param name="vc_uploadViewAutoscrollToUploadingFile" value="true" />
				<param name="vc_uploadViewStartActionVisible" value="false" />
				<param name="vc_uploadViewStartActionAlwaysVisible" value="false" />
				<param name="vc_uploadViewStopActionVisible" value="false" />
				<param name="vc_uploadViewStopActionAlwaysVisible" value="false" />
				<param name="vc_uploadViewRetryActionVisible" value="false" />
				<param name="vc_uploadViewListStatusVisible" value="false" />
				<param name="vc_uploadViewMenuBarVisible" value="false" />
				<param name="vc_uploadViewProgressPaneVisible" value="true" />
				<param name="vc_mainViewLogoEnabled" value="false" />
			</applet>
			-->
			<fieldset style="width:30%; height:300px; float:left; clear:right;" class="buttons">
				<input id="startButton" type="button" value="Start Upload" />
				<input id="stopButton" type="button" value="Stop Upload" />
				<input id="closeButton" type="button" value="Close" onclick="javascript: $('FORM#forms__media_library TABLE TFOOT INPUT:eq(1)').click();" />
			</fieldset>
		</div>
		<table class="full_size" id="tables__media_library">
			<thead>
				<tr>
					<th style="width: 5%; text-align: right;">Id</th>
					<th style="width: 25%; white-space: nowrap;">Checksum</th>
					<th style="width: 5%; white-space: nowrap; text-align: center;">Type</th>
					<th style="width: 10%; white-space: nowrap;">Size</th>
					<th style="width: 15%; text-align: center; white-space: nowrap;">Date Modified</th>
					<th style="width: 40%; white-space: nowrap;">Actions</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="6">
						<div class="pager">
							{{pager total_nr_of_items=$CONTENT.media_library__total_nr_of_items nr_of_items_per_page="20"}}
						</div>
						<fieldset class="buttons">
							<input type="hidden" name="current_page" value="1" />
							<input type="button" value="Upload File" />
							<input type="button" value="Delete Selected" />
						</fieldset>
					</td>
				</tr>
			</tfoot>
			<tbody>
				<tr id="file_description__clone_sample" class="hide file_description">
					<td></td>
					<td></td>
					<td colspan="4" style="text-align: left;">
						<strong></strong>
					</td>
				</tr>
				{{foreach from=$CONTENT.media_library__file_list item=FILE}}
				<tr>
					<td style="text-align: right;" class="w_preview_hover {{$FILE.f_mime|filetype}}">{{$FILE.f_id}}</td>
					<td class="w_preview_hover {{$FILE.f_mime|filetype}}">{{$FILE.f_hash}}</td>
					<td style="white-space: nowrap; text-align: center;">{{$FILE.f_extension}}</td>
					<td style="white-space: nowrap;">{{$FILE.f_size|filesize_h}}B</td>
					<td style="white-space: nowrap; text-align:center;">{{$FILE.f_timestamp|date_format:"%d-%m-%Y %R"}}</td>
					<td style="white-space: nowrap;">
						<ul class="horizontal">
						{{if $FILE.f_mime|filetype eq 'image'}}
							<li>thumb +</li>
							<li>watermark +</li>
						{{elseif $FILE.f_mime|filetype eq 'audio'}}
						{{elseif $FILE.f_mime|filetype eq 'video'}}
						{{else}}
						{{/if}}
							<li><a href="/static/delete-{{$FILE.f_id}}?output=json" class="js__go_ajax">delete -</a></li>
						</ul>
					</td>
				</tr>
				<tr id="file_description__{{$FILE.f_id}}" class="hide file_description" title="Close">
					<td></td>
					<td>
						{{if $FILE.f_mime|filetype eq 'image'}}
						<img src="/static/stream/s-{{$FILE.f_id}}" alt="" />
						{{/if}}
					</td>
					<td colspan="4" style="text-align: left;">
						<strong style="width: 100%;">Associated file-names:</strong>
						<ul style="list-style-position: inside;">
							{{foreach from=$FILE.f_name item=FILENAME}}
							<li><i>{{$FILENAME}}</i></li>
							{{/foreach}}
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