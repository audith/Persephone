<div class="aside full_size" id="system_console"></div>
<ul class="section full_size">
	<li id="connectors__ddl__alter_add" class="ondemand">
		<h2>Data Definition Management (for Connector-unit)</h2>
        <form id="forms__connectors__ddl__alter_add" action="" method="post" class="js__go_ajax">
            <fieldset class="name onload">
                <label title="Field Name" for="name"><strong>Field Name:</strong></label>
                <input type="text" class="text required _701" id="name" name="name" value="" maxlength="64" style="text-transform: lowercase;" />
                <em class="ui-tooltip">Alphanumeric + underscore chars only, in format: ^[a-z][a-z0-9_]*$ meaning first letter can't be numeric.</em>
            </fieldset>
            <fieldset class="label onload">
                <label title="Field Label" for="label"><strong>Field Label:</strong></label>
                <input type="text" class="text required _702" id="label" name="label" value="" maxlength="64" />
                <em class="ui-tooltip">A form-label [e.g. &quot;Article heading&quot;, &quot;Hotel name&quot; etc]. It will be used in the front-end content management interface. <b>Alphanumeric + underscore chars only!</b></em>
            </fieldset>
            <fieldset class="type">
                <label title="Data-Type" for="type"><strong>Data-Type:</strong></label>
                <select class="required js__trigger_on_change _703" id="type" name="type">
                    <option value="alphanumeric">Alphanumeric</option>
                    <option value="file">File</option>
                    <option value="link">Link with other module</option>
                </select>
                <em class="ui-tooltip">Select one from the list.</em>
            </fieldset>
            <fieldset class="links_with ondemand">
                <label title="Link with ..." for="links_with"><strong>Link with ...</strong></label>
                <select name="links_with" id="links_with" class="js__trigger_on_change _704">
                    <option value="">-- select a module --</option>
				{{foreach from=$CONTENT.others item=MODULE}}
					{{if $MODULE.m_type neq 'built-in'}}
                        <option value="{{$MODULE.m_unique_id_clean}}" {{if ! $MODULE.m_title_column}}disabled="disabled"{{/if}}>/{{$MODULE.m_name}}{{if ! $MODULE.m_title_column}} (Disabled: No Title-field found!){{/if}}</option>
					{{/if}}
				{{/foreach}}
                </select>
                <em class="ui-tooltip">Links this module with some other module.</em>
            </fieldset>
            <fieldset class="links_with__e_data_definition ondemand">
                <label title="Link via ..." for="links_with__e_data_definition"><strong>Link via ...</strong></label>
                <select class="required _705" id="links_with__e_data_definition" name="links_with__e_data_definition[]" multiple="multiple" size="5">
                    <option></option>
                </select>
                <em class="ui-tooltip">Using CTRL (CMD on Mac) key, select one or more data-fields to fetch a data from.</em>
            </fieldset>
            <fieldset class="subtype ondemand">
                <label title="Data-Subtype" for="subtype"><strong>Data-Subtype:</strong></label>
                <select class="required js__trigger_on_change _706" id="subtype" name="subtype">
                    <option></option>
                </select>
                <em class="ui-tooltip">Select one from the list.</em>
            </fieldset>
            <fieldset class="allowed_filetypes ondemand">
                <label title="Allowed File-types" for="allowed_filetypes"><strong>Allowed File-types:</strong></label>
                <select class="required _707" id="allowed_filetypes" name="allowed_filetypes[]" multiple="multiple" size="5">
                    <option></option>
                </select>
                <em class="ui-tooltip">The list of file-types allowed for file-uploads.</em>
            </fieldset>
            <fieldset class="default_options ondemand">
                <label title="Default Options" for="default_options"><strong>Default Options:</strong></label>
                <textarea class="required _708" name="default_options" id="default_options" style="height: 80px" cols="" rows=""></textarea>
                <em class="ui-tooltip">'<b><i>key</i></b>'='<b><i>value</i></b>' pairs, one per line; e.g.:<br />'m=Male<br />f=Female<br />u=Not Telling'<br />'<i><b>key</b></i>'s can only contain alphanumeric, underscore (_) and dash (-).</em>
            </fieldset>
            <fieldset class="maxlength ondemand">
                <label title="" for="maxlength"><strong></strong></label>
                <input type="text" class="text _709" id="maxlength" name="maxlength" value="" />
                <em class="ui-tooltip"></em>
            </fieldset>
            <fieldset class="default_value ondemand">
                <label title="Default Value" for="default_value"><strong>Default Value:</strong></label>
                <input type="text" class="text _710" id="default_value" name="default_value" value="" />
                <em class="ui-tooltip">Default value(s) (more than one for '<b><i>Multiple Select</i></b>'). Separate multiple values with comma or space. Leave empty for no defaults.</em>
            </fieldset>
            <fieldset class="connector_enabled ondemand">
                <label title="Enable Connector?"><strong>Enable Connector?</strong></label>
				<span class="input ui-buttonset">
					<input type="radio" class="js__trigger_on_change" name="connector_enabled" id="yes_for__connector_enabled" value="1" />
					<input type="radio" class="js__trigger_on_change" name="connector_enabled" id="no_for__connector_enabled" value="0" />
					<label for="yes_for__connector_enabled" class="js__trigger_on_change" title="Yes">Yes</label>
					<label for="no_for__connector_enabled" class="js__trigger_on_change" title="No">No</label>
				</span>
                <em class="ui-tooltip">Whether to enable Connector feature (multiple entries for this field, per row), or not.</em>
            </fieldset>
            <fieldset class="connector_length_cap ondemand">
                <label title="Maximum Number of Items" for="connector_length_cap"><strong>Maximum Number of Items:</strong></label>
                <input type="text" class="text" id="connector_length_cap" name="connector_length_cap" value="0" />
                <em class="ui-tooltip">Maximum number of items allowed. Enter &quot;0&quot; to disable this restriction.</em>
            </fieldset>
            <fieldset class="is_html_allowed ondemand">
                <label title="Allow HTML Input?"><strong>Allow HTML Input?</strong></label>
				<span class="input ui-buttonset">
					<input type="radio" name="is_html_allowed" id="yes_for__is_html_allowed" value="1" />
					<input type="radio" name="is_html_allowed" id="no_for__is_html_allowed" value="0" />
					<label for="yes_for__is_html_allowed" title="Yes">Yes</label>
					<label for="no_for__is_html_allowed" title="No">No</label>
				</span>
                <em class="ui-tooltip">Whether to allow HTML input in this field or not.<br />Works for <i>String</i> sub-types and only when Max-Length&gt;255.</em>
            </fieldset>
            <fieldset class="is_required ondemand">
                <label title="Is a Required Field?"><strong>Is a Required Field?</strong></label>
				<span class="input ui-buttonset">
					<input type="radio" name="is_required" id="yes_for__is_required" value="1" />
					<input type="radio" name="is_required" id="no_for__is_required" value="0" />
					<label for="yes_for__is_required" title="Yes">Yes</label>
					<label for="no_for__is_required" title="No">No</label>
				</span>
                <em class="ui-tooltip">Whether the field can or cannot be left empty.</em>
            </fieldset>
            <fieldset class="is_unique ondemand">
                <label title="Is Unique?"><strong>Is Unique?</strong></label>
				<span class="input ui-buttonset">
					<input type="radio" name="is_unique" id="yes_for__is_unique" value="1" />
					<input type="radio" name="is_unique" id="no_for__is_unique" value="0" />
					<label for="yes_for__is_unique" title="Yes">Yes</label>
					<label for="no_for__is_unique" title="No">No</label>
				</span>
                <em class="ui-tooltip">Whether the data this field holds is unique or not. <i>Use with caution</i> since you won't be able to insert duplicate (repeating) data!</em>
            </fieldset>

            <div class="system_console"></div>
            <fieldset class="buttons">
                <input type="hidden" name="do" value="ddl_alter__add" />
                <input type="hidden" name="name__old" value="" />
                <input type="hidden" name="m_unique_id" value="{{$CONTENT.me.m_unique_id}}" />
                <input type="submit" value="Register New Data-field" />
                <input type="reset" value="Clear Form" />
                <input type="button" value="Cancel &amp; Close Form" />
            </fieldset>
        </form>
	</li>

	<li id="connectors__ddl__list">
		<h2 class="full_size">Data Definition Management - Actual for Connector: <em>/{{$CONTENT._request.c_name}}</em> of Module: <em>/{{$CONTENT.m_data__me.m_name}}</em></h2>

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
