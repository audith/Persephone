<div class="aside full_size" id="system_console"></div>
<ul class="section full_size">
	<li id="components__mrp__uom__categories-alter_add" class="ondemand">
		<h2>Register a Module
			<span class="description"></span>
		</h2>
		<form id="forms-components__mrp__uom__categories-alter_add" class="js__go_ajax" action="" method="post">
			<fieldset class="m_name">
				<label title="Module Name" for="create_module__m_name"><strong>Module Name:</strong></label>
				<input type="text" class="text required _701" id="create_module__m_name" name="m_name" value="" maxlength="32" />
				<em class="ui-tooltip">Alphanumeric and underscore characters only [a-zA-Z0-9_].</em>
			</fieldset>
			<fieldset class="m_description">
				<label title="Module Description" for="create_module__m_description"><strong>Module Description:</strong></label>
				<input type="text" class="text required _702" id="create_module__m_description" name="m_description" value="" maxlength="255" />
				<em class="ui-tooltip">Brief description of module [its purpose, function etc].</em>
			</fieldset>
			<fieldset class="m_extras">
				<label title="Features to Embed" for="create_module__m_extras"><strong>Features to Embed:</strong></label>
				<select name="m_extras[]" multiple="multiple" size="3" id="create_module__m_extras">
					<option value="tags">Content Tagging &amp; Labeling</option>
					<option value="comments">User Comments</option>
				</select>
				<em class="ui-tooltip">Additional features to be embedded into the structure of this module.</em>
			</fieldset>
			<fieldset class="m_enforce_ssl">
				<label title="Enforce secure (SSL) connection?"><strong>Enforce secure (SSL) connection?</strong></label>
				<span class="input ui-buttonset">
					<input type="radio" name="m_enforce_ssl" id="yes_for__m_enforce_ssl" value="1" />
					<input type="radio" name="m_enforce_ssl" id="no_for__m_enforce_ssl" value="0" />
					<label for="yes_for__m_enforce_ssl" class="js__trigger_on_change" title="Yes">Yes</label>
					<label for="no_for__m_enforce_ssl" class="js__trigger_on_change" title="No">No</label>
				</span>
				<em class="ui-tooltip">Whether to enforce SSL connection for this module.</em>
			</fieldset>
			<fieldset class="m_enable_caching">
				<label title="Enable Page Caching?"><strong>Enable Page Caching?</strong></label>
				<span class="input ui-buttonset">
					<input type="radio" name="m_enable_caching" id="yes_for__m_enable_caching" value="1" />
					<input type="radio" name="m_enable_caching" id="no_for__m_enable_caching" value="0" />
					<label for="yes_for__m_enable_caching" class="js__trigger_on_change" title="Yes">Yes</label>
					<label for="no_for__m_enable_caching" class="js__trigger_on_change" title="No">No</label>
				</span>
				<em class="ui-tooltip">Whether to enable page caching for the module pages or not.</em>
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

	<li id="components__mrp__uom__categories-list">
		<h2>Units of Measure
			<span class="description"></span>
		</h2>

		<form id="forms-components__mrp__uom__categories-list" class="js__go_ajax" method="post" action="">
		<table class="full_size tablesorter" id="tables__modules__list">
			<thead>
				<tr>
					<th style="width:65%; white-space:nowrap">UOM Category</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td>
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
				{{foreach from=$CONTENT item=UOM_CATEG}}
				<tr>
					<td>
						<span class="name">
							<a href="{{$MODULE_URL}}/components/mrp/uom/browse-{{$UOM_CATEG.category_id}}"><strong>{{$UOM_CATEG.category_name|truncate:16}}</strong></a>
						</span>
						<ul class="actions">
							<li class="ui-icon ui-icon-pencil"><a class="edit" href="?xxx" title="Edit">Edit</a></li>
							<li class="ui-icon ui-icon-closethick"><a class="delete" href="?xxx" title="Delete">Delete</a></li>
							<li class="ui-icon ui-icon-seek-next"><a href="{{$MODULE_URL}}/components/viewmodule-xxx" title="Manage">Manage</a></li>
						</ul>
					</td>
				</tr>
				{{foreachelse}}
				<tr>
					<td><span class="system_message_error">NO MODULES FOUND!</span></td>
				</tr>
				{{/foreach}}
			</tbody>
		</table>
		</form>
	</li>
</ul>