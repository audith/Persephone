<samp id="system_console" class="full_size"></samp>
<ul class="data_container full_size">
	<li id="connectors__ddl__alter_add" style="display: none;">
		<h2 class="full_size">Register a Data-Field
			<span class="description"></span>
		</h2>

		<form id="forms__connectors__ddl__alter_add" action="" method="post">
		<fieldset>
			<label title="Field Name" for="register__dft_name"><strong>Field Name:</strong><em>Alphanumeric + underscore chars only, in format: ^[a-z][a-z0-9_]*$ meaning first letter can't be numeric.</em></label>
			<input type="text" class="text required" id="register__dft_name" name="dft_name" value="" maxlength="64" style="text-transform:lowercase;" />

			<label title="Field Label" for="register__dft_label"><strong>Field Label:</strong><em>A form-label [e.g. &quot;Article heading&quot;, &quot;Hotel name&quot; etc]. It will be used in the front-end content management interface. <b>Alphanumeric + underscore chars only!</b></em></label>
			<input type="text" class="text required" id="register__dft_label" name="dft_label" value="" maxlength="64" />

			<label title="Data-Type" for="register__dft_type"><strong>Data-Type:</strong><em>Select one from the list.</em></label>
			<select class="required" id="register__dft_type" name="dft_type" onchange="javascript:ddl_add__select_dft_subtype( this.value );">
				<option value="alphanumeric">Alphanumeric</option>
				<option value="file">File</option>
				<option value="link">Link with other module</option>
			</select>

			<fieldset id="dft__link_stuff">
				<span id="dft_links_with">
					<label title="Link with ..." for="register__dft_links_with"><strong>Link with ...</strong><em>Links this module with some other module.</em></label>
					<select name="dft_links_with" id="register__dft_links_with">
						<option value="">-- no linking --</option>
						{{foreach from=$CONTENT.m_data__others item=MODULE}}
						{{if $MODULE.m_type neq 'built-in'}}
						<option value="{{$MODULE.m_unique_id_clean}}" {{if ! $MODULE.m_title_column}}disabled="disabled"{{/if}}>/{{$MODULE.m_name}}{{if ! $MODULE.m_title_column}} (Disabled: No Title-field found!){{/if}}</option>
						{{/if}}
						{{/foreach}}
					</select>
				</span>

				<span id="dft_links_with__fields_to_fetch">
					<label title="Link via ..." for="register__dft_links_with__fields_to_fetch"><strong>Link via ...</strong><em>Using CTRL (CMD on Mac) key, select one or more data-fields to fetch a data from.</em></label>
					<select class="required" id="register__dft_links_with__fields_to_fetch" name="dft_links_with__fields_to_fetch[]" multiple="multiple" size="5"></select>
					<span class="input">
						Loading...
					</span>
				</span>
			</fieldset>

			<fieldset id="dft__non_link_stuff">
				<span id="dft_subtype_options_for__alphanumeric">
					<label title="Data-Subtype" for="register__dft_subtype_options_for__alphanumeric"><strong>Data-Subtype:</strong><em>Select one from the list.</em></label>
					<select class="required" id="register__dft_subtype_options_for__alphanumeric" name="dft_subtype__alphanumeric" onchange="javascript: ddl_add__select_dft_maxlength( 'alphanumeric', this.value );">
						<option value="string">General String</option>
						<option value="integer_signed_8">Numeric: Signed 8-bit-Integer (-128/127)</option>
						<option value="integer_unsigned_8">Numeric: Unsigned 8-bit-Integer (0/255)</option>
						<option value="integer_signed_16">Numeric: Signed 16-bit-Integer (-32768/32767)</option>
						<option value="integer_unsigned_16">Numeric: Unsigned 16-bit-Integer (0/65535)</option>
						<option value="integer_signed_24">Numeric: Signed 24-bit-Integer (-8388608/8388607)</option>
						<option value="integer_unsigned_24">Numeric: Unsigned 24-bit-Integer (0/16777215)</option>
						<option value="integer_signed_32">Numeric: Signed 32-bit-Integer (-2147483648/2147483647)</option>
						<option value="integer_unsigned_32">Numeric: Unsigned 32-bit-Integer (0/4294967295)</option>
						<option value="integer_signed_64">Numeric: Signed 64-bit-Integer (-9223372036854775808/9223372036854775807)</option>
						<option value="integer_unsigned_64">Numeric: Unsigned 64-bit-Integer (0/18446744073709551615)</option>
						<option value="decimal_signed">Numeric: Signed Decimal (Fixed-Point)</option>
						<option value="decimal_unsigned">Numeric: Unsigned Decimal (Fixed-Point)</option>
						<option value="dropdown">Single Select (Dropdowns/Radios)</option>
						<option value="multiple">Multiple Select (incl. Checkboxes)</option>
					</select>
				</span>

				<span id="dft_subtype_options_for__file">
					<label title="Field Data-Subtype" for="register__dft_subtype_options_for__file"><strong>Field Data-Subtype:</strong><em>Choose one from the list.</em></label>
					<select class="required" id="register__dft_subtype_options_for__file" name="dft_subtype__file" onchange="javascript:ddl_add__select_dft_maxlength( 'file', this.value );">
						<option value="image">Image files</option>
						<option value="audio">Audio files</option>
						<option value="video">Video files</option>
						<option value="any">Any type of files</option>
					</select>
				</span>

				<span id="dft_allowed_filetypes">
					<label title="Allowed File-types" for="register__dft_allowed_filetypes"><strong>Allowed File-types:</strong><em>The list of file-types allowed for file-uploads.</em></label>
					<select class="required" id="register__dft_allowed_filetypes" name="dft_allowed_filetypes[]" multiple="multiple" size="5"></select>
					<span class="input">
						Loading...
					</span>
				</span>

				<span id="dft_default_options">
					<label title="Default Options" for="register__dft_default_options"><strong>Default Options:</strong><em>'<b><i>key</i></b>'='<b><i>value</i></b>' pairs, in sets, one set per line; e.g.:<br />'m=Male<br />f=Female<br />u=Not Telling'<br />'<i><b>key</b></i>'s can only contain alphanumeric, underscore (_) and dash (-).</em></label>
					<textarea class="required" name="dft_default_options" id="register__dft_default_options" style="height: 80px" cols="" rows=""></textarea>
				</span>

				<span id="dft_maxlength_options_for__alphanumeric__string">
					<label title="Field Max-Length (in bytes)" for="register__dft_maxlength_options_for__alphanumeric__string"><strong>Field Max-Length (in bytes):</strong><em>Maximum length in bytes. Leave empty to default to &quot;255&quot;. <b>Be aware of multi-byte characters!</b></em></label>
					<input type="text" class="text" id="register__dft_maxlength_options_for__alphanumeric__string" name="dft_maxlength__alphanumeric__string" value="" />
				</span>

				<span id="dft_maxlength_options_for__alphanumeric__integer">
					<label title="# of Digits" for="register__dft_maxlength_options_for__alphanumeric__integer"><strong>Max # of Digits:</strong><em>Maximum number of digits, including sign (+/-) character. The value is automatically set and is shown here for your reference.</em></label>
					<input type="text" class="text" id="register__dft_maxlength_options_for__alphanumeric__integer" name="dft_maxlength__alphanumeric__integer" value="" disabled="disabled" />
				</span>

				<span id="dft_maxlength_options_for__alphanumeric__decimal">
					<label title="Precision and Scale" for="register__dft_maxlength_options_for__alphanumeric__decimal"><strong>Precision &amp; Scale:</strong><em>In &quot;<i><b>p,s</b></i>&quot; format; e.g.: &quot;5,2&quot; means total 5 digits, 2 of which follows decimal point, as in &quot;999.99&quot;. Leave empty to default to &quot;10,0&quot;.</em></label>
					<input type="text" class="text" id="register__dft_maxlength_options_for__alphanumeric__decimal" name="dft_maxlength__alphanumeric__decimal" value="" />
				</span>

				<span id="dft_maxlength_options_for__file">
					<label title="Maximum Filesize" for="register__dft_maxlength_options_for__file"><strong>Maximum Filesize:</strong><em>Maximum filesize, suffice supported: &quot;1K&quot;, &quot;2M&quot;. Enter &quot;0&quot; to disable this restriction.</em></label>
					<input type="text" class="text" id="register__dft_maxlength_options_for__file" name="dft_maxlength__file" value="" />
				</span>

				<span id="dft_default_value">
					<label title="Default Value" for="register__dft_default_value"><strong>Default Value:</strong><em>Default value(s) (more than one for '<b><i>Multiple Select</i></b>'). Separate multiple values with comma or space. Leave empty for no defaults.</em></label>
					<input type="text" class="text" id="register__dft_default_value" name="dft_default_value" value="" />
				</span>

				<span id="dft_html_allowed">
					<label title="Allow HTML Input?"><strong>Allow HTML Input?</strong><em>Whether to allow HTML input in this field or not.<br />Works for <i>String</i> sub-types and only when Max-Length&gt;255.</em></label>
					<span class="input">Yes &nbsp; <input type="radio" value="1" name="dft_html_allowed" />&nbsp;&nbsp;&nbsp;<input type="radio" value="0" checked="checked" name="dft_html_allowed" /> &nbsp; No</span>
				</span>
			</fieldset>

			<span id="dft_is_required">
				<label title="Is a Required Field?"><strong>Is a Required Field?</strong><em>Whether the field can or not be left empty.</em></label>
				<span class="input">Yes &nbsp; <input type="radio" value="1" name="dft_is_required" checked="checked" />&nbsp;&nbsp;&nbsp;<input type="radio" value="0" name="dft_is_required" /> &nbsp; No</span>
			</span>

			<span id="dft_is_unique">
				<label title="Is Unique?"><strong>Is Unique?</strong><em>Whether the data this field holds is unique or not. <i>Use with caution</i> since you won't be able to insert duplicate (repeating) data!</em></label>
				<span class="input">Yes &nbsp; <input type="radio" value="1" name="dft_is_unique" />&nbsp;&nbsp;&nbsp;<input type="radio" value="0" name="dft_is_unique" checked="checked" /> &nbsp; No</span>
			</span>

		</fieldset>

		<div class="system_console"></div>

		<fieldset class="buttons">
			<input type="hidden" name="do" value="ddl_alter__add" />
			<input type="hidden" name="connectors_linked" value="{{$CONTENT.c_data.m_unique_id}}" />
			<input type="hidden" name="connected_field" value="{{$CONTENT._request.c_name}}" />
			<input type="hidden" name="m_unique_id" value="{{$CONTENT.m_data__me.m_unique_id}}" />
			<input type="submit" value="Register a Data-Field" />
			<input type="reset" value="Clear Form" />
			<input type="button" value="Cancel &amp; Close Form" />
		</fieldset>
		</form>
	</li>

	<li id="connectors__ddl__list">
		<h2 class="full_size">Data Definition Management - Actual for Connector: <em>/{{$CONTENT._request.c_name}}</em> of Module: <em>/{{$CONTENT.m_data__me.m_name}}</em>
			<span class="description"></span>
		</h2>

		<form id="forms__connectors__ddl__list" method="post" action="">
		<table class="full_size tablesorter {sortlist: [[0,0]]}" id="tables__connectors__ddl__list">
			<thead>
				<tr>
					<th style="width: 5%; white-space: nowrap;">Pos.</th>
					<th style="width: 50%; white-space: nowrap;">Field Name</th>
					<th style="width: 40%; white-space: nowrap;" class="{sorter: false}">Data Type</th>
					<th style="width: 5%;" class="{sorter: false}">&nbsp;</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="4">
					<div class="system_console"></div>
					<fieldset class="buttons">
						<input type="button" value="Create New" />
						<input type="button" value="Drop Selected" />
						<input type="hidden" name="do" value="" />
						<input type="hidden" name="connectors_linked" value="{{$CONTENT.c_data.m_unique_id}}" />
						<input type="hidden" name="connected_field" value="{{$CONTENT._request.c_name}}" />
						<input type="hidden" name="m_unique_id" value="{{$CONTENT.m_data__me.m_unique_id}}" />
						<input type="hidden" name="do_backup_dropped_field" value="1" />
					</fieldset></td>
				</tr>
			</tfoot>
			<tbody {{if count($CONTENT.c_data.m_data_definition)}}class="js__sortable"{{/if}}>
				{{foreach from=$CONTENT.c_data.m_data_definition item=FIELD}}
				<tr id="position_{{$FIELD.name}}">
					<td align="center">{{$FIELD.position}}</td>
					<td><span class="name">{{$FIELD.name|truncate:45:"...":TRUE}}</span></td>
					<td>{{$FIELD.type}}{{if $FIELD.subtype neq ''}}/{{$FIELD.subtype}}{{/if}}{{if $FIELD.maxlength neq ''}} [{{$FIELD.maxlength|truncate:60:"...":TRUE}}]{{/if}}</td>
					<td align="center"><input type="radio" name="ddl_checklist[]" value="{{$FIELD.name}}" /></td>
				</tr>
				{{foreachelse}}
				<tr>
					<td colspan="4"><span class="system_message_error">No such connector-enabled data-field found! Or maybe you haven't linked it yet. <a href="javascript: history.back(-1);">Click to go back and fix it</a>.</span></td>
				</tr>
				{{/foreach}}
			</tbody>
		</table>
		</form>
	</li>

	{{if count( $CONTENT.c_data.m_data_definition_bak )}}
	<li id="connectors__ddl__list_bak">
		<h2 class="full_size">Data Definition Management - Backup for Connector: <em>/{{$CONTENT._request.c_name}}</em> of Module: <em>/{{$CONTENT.m_data__me.m_name}}</em>
			<span class="description"></span>
		</h2>

		<form id="forms__connectors__ddl__list_bak" method="post" action="">
		<table class="full_size tablesorter" id="tables__connectors__ddl__list_bak">
			<thead>
				<tr>
					<th style="width: 30%; white-space: nowrap;">Field Name</th>
					<th style="width: 65%; white-space: nowrap;">Data Type</th>
					<th style="width: 5%;"></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="3">
					<div class="system_console"></div>
					<fieldset class="buttons">
						<input type="button" value="Restore Selected" />
						<input type="button" value="Permanently Drop Selected" />
						<input type="hidden" name="do" value="" />
						<input type="hidden" name="connectors_linked" value="{{$CONTENT.c_data.m_unique_id}}" />
						<input type="hidden" name="connected_field" value="{{$CONTENT._request.c_name}}" />
						<input type="hidden" name="m_unique_id" value="{{$CONTENT.m_data__me.m_unique_id}}" />
					</fieldset>
					</td>
				</tr>
			</tfoot>
			<tbody>
				{{foreach from=$CONTENT.c_data.m_data_definition_bak item=FIELD}}
				<tr>
					<td><span class="name"><a href="#" title="{{$FIELD.label}}" onclick="javascript: void(0);">{{$FIELD.name|truncate:45:"...":TRUE}}</a></span></td>
					<td>{{$FIELD.type}}{{if $FIELD.subtype neq ''}}/{{$FIELD.subtype}}{{/if}}{{if $FIELD.maxlength neq ''}} [{{$FIELD.maxlength|truncate:60:"...":TRUE}}]{{/if}}</td>
					<td align="center"><input type="checkbox" name="ddl_checklist[]" value="{{$FIELD.name}}" /></td>
				</tr>
				{{/foreach}}
			</tbody>
		</table>
		</form>
	</li>
	{{/if}}
</ul>