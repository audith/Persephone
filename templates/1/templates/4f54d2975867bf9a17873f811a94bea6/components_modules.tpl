<samp id="system_console" class="full_size"></samp>
<ul class="data_container full_size">
	<li id="components__modules__alter_add" class="ondemand">
		<h2>Register a Module
			<span class="description"></span>
		</h2>
		<form id="forms__modules__alter_add" class="js__go_ajax" action="" method="post">
			<fieldset class="m_name">
				<label title="Module Name" for="create_module__m_name"><strong>Module Name:</strong><em>Alphanumeric and underscore characters only [a-zA-Z0-9_].</em></label>
				<input type="text" class="text required _701" id="create_module__m_name" name="m_name" value="" maxlength="32" />
			</fieldset>
			<fieldset class="m_description">
				<label title="Module Description" for="create_module__m_description"><strong>Module Description:</strong><em>Brief description of module [its purpose, function etc].</em></label>
				<input type="text" class="text required _702" id="create_module__m_description" name="m_description" value="" maxlength="255" />
			</fieldset>
			<fieldset class="m_extras">
				<label title="Features to Embed" for="create_module__m_extras"><strong>Features to Embed:</strong><em>Additional features to be embedded into the structure of this module.</em></label>
				<select name="m_extras[]" multiple="multiple" size="3" id="create_module__m_extras">
					<option value="tags">Content Tagging &amp; Labeling</option>
					<option value="comments">User Comments</option>
				</select>
			</fieldset>
			<fieldset class="m_enforce_ssl">
				<label title="Enforce secure (SSL) connection?"><strong>Enforce secure (SSL) connection?</strong><em>Whether to enforce SSL connection for this module.</em></label>
				<span class="input ui-buttonset">
					<input type="radio" name="m_enforce_ssl" id="yes_for__m_enforce_ssl" value="1" />
					<input type="radio" name="m_enforce_ssl" id="no_for__m_enforce_ssl" value="0" />
					<label for="yes_for__m_enforce_ssl" class="js__trigger_on_change" title="Yes">Yes</label>
					<label for="no_for__m_enforce_ssl" class="js__trigger_on_change" title="No">No</label>
				</span>
			</fieldset>
			<fieldset class="m_enable_caching">
				<label title="Enable Page Caching?"><strong>Enable Page Caching?</strong><em>Whether to enable page caching for the module pages or not.</em></label>
				<span class="input ui-buttonset">
					<input type="radio" name="m_enable_caching" id="yes_for__m_enable_caching" value="1" />
					<input type="radio" name="m_enable_caching" id="no_for__m_enable_caching" value="0" />
					<label for="yes_for__m_enable_caching" class="js__trigger_on_change" title="Yes">Yes</label>
					<label for="no_for__m_enable_caching" class="js__trigger_on_change" title="No">No</label>
				</span>
			</fieldset>

			<div class="system_console"></div>
			<fieldset class="buttons">
				<input type="hidden" name="do" value="" />
				<input type="hidden" name="m_unique_id" value="" />
				<input type="button" value="Submit" />
				<input type="reset" value="Clear Form" />
				<input type="button" value="Cancel &amp; Close" />
			</fieldset>
		</form>
	</li>

	<li id="components__modules__list">
		<h2>Components Management - Modules
			<span class="description"></span>
		</h2>

		<form id="forms__modules__list" class="js__go_ajax" method="post" action="">
		<table class="full_size tablesorter" id="tables__modules__list">
			<thead>
				<tr>
					<th style="width:65%; white-space:nowrap">Module Info</th>
					<th style="width:35%; white-space:nowrap; text-align:center;" class="{sorter: false}">Preliminary Diagnostics</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="2">
					<div class="system_console"></div>
					<fieldset class="buttons">
						<input type="hidden" value="" name="do" />
						<input type="hidden" value="" name="m_unique_id" />
						<input type="button" value="Create New" />
					</fieldset>
					</td>
				</tr>
			</tfoot>
			<tbody>
				{{foreach from=$CONTENT item=MODULE}}
				{{if $MODULE.m_type neq 'built-in'}}
				<tr>
					<td>
						<span class="name">
							<a href="{{$MODULE_URL}}/components/viewmodule-{{$MODULE.m_unique_id|m_unique_id_clean}}"><strong>/{{$MODULE.m_name|truncate:16}}</strong></a> - {{$MODULE.m_description|truncate:48}}
							<br />
							{{$MODULE.m_unique_id}}
						</span>
						<ul class="actions">
							<li class="ui-icon ui-icon-pencil"><a class="edit" href="?{{$MODULE.m_unique_id}}" title="Edit">Edit</a></li>
							<li class="ui-icon ui-icon-closethick"><a class="delete" href="?{{$MODULE.m_unique_id}}" title="Delete">Delete</a></li>
							<li class="ui-icon ui-icon-seek-next"><a href="{{$MODULE_URL}}/components/viewmodule-{{$MODULE.m_unique_id|m_unique_id_clean}}" title="Manage">Manage</a></li>
						</ul>
					</td>
					<td style="text-align:center;">COMING SOON!</td>
				</tr>
				{{/if}}
				{{foreachelse}}
				<tr>
					<td colspan="2"><span class="system_message_error">NO MODULES FOUND!</span></td>
				</tr>
				{{/foreach}}
			</tbody>
		</table>
		</form>
	</li>
</ul>