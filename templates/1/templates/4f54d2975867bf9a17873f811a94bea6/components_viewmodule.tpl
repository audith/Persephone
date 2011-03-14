<samp id="system_console" class="full_size"></samp>
<ul class="data_container full_size">
	<li id="components__ddl__alter_add" class="ondemand">
		<h2 class="full_size">Data Definition Management (for Master-unit)
			<span class="description"></span>
		</h2>
		<form id="forms__components__ddl__alter_add" action="" method="post" class="js__go_ajax">
			<fieldset class="name onload">
				<label title="Field Name" for="name"><strong>Field Name:</strong><em>Alphanumeric + underscore chars only, in format: ^[a-z][a-z0-9_]*$ meaning first letter can't be numeric.</em></label>
				<input type="text" class="text required _701" id="name" name="name" value="" maxlength="64" style="text-transform: lowercase;" />
			</fieldset>
			<fieldset class="label onload">
				<label title="Field Label" for="label"><strong>Field Label:</strong><em>A form-label [e.g. &quot;Article heading&quot;, &quot;Hotel name&quot; etc]. It will be used in the front-end content management interface. <b>Alphanumeric + underscore chars only!</b></em></label>
				<input type="text" class="text required _702" id="label" name="label" value="" maxlength="64" />
			</fieldset>
			<fieldset class="type">
				<label title="Data-Type" for="type"><strong>Data-Type:</strong><em>Select one from the list.</em></label>
				<select class="required js__trigger_on_change _703" id="type" name="type">
					<option value="alphanumeric">Alphanumeric</option>
					<option value="file">File</option>
					<option value="link">Link with other module</option>
				</select>
			</fieldset>
			<fieldset class="links_with ondemand">
				<label title="Link with ..." for="links_with"><strong>Link with ...</strong><em>Links this module with some other module.</em></label>
				<select name="links_with" id="links_with" class="js__trigger_on_change _704">
					<option value="">-- select a module --</option>
					{{foreach from=$CONTENT.others item=MODULE}}
					{{if $MODULE.m_type neq 'built-in'}}
					<option value="{{$MODULE.m_unique_id_clean}}" {{if ! $MODULE.m_title_column}}disabled="disabled"{{/if}}>/{{$MODULE.m_name}}{{if ! $MODULE.m_title_column}} (Disabled: No Title-field found!){{/if}}</option>
					{{/if}}
					{{/foreach}}
				</select>
			</fieldset>
			<fieldset class="links_with__e_data_definition ondemand">
				<label title="Link via ..." for="links_with__e_data_definition"><strong>Link via ...</strong><em>Using CTRL (CMD on Mac) key, select one or more data-fields to fetch a data from.</em></label>
				<select class="required _705" id="links_with__e_data_definition" name="links_with__e_data_definition[]" multiple="multiple" size="5">
					<option></option>
				</select>
			</fieldset>
			<fieldset class="subtype ondemand">
				<label title="Data-Subtype" for="subtype"><strong>Data-Subtype:</strong><em>Select one from the list.</em></label>
				<select class="required js__trigger_on_change _706" id="subtype" name="subtype">
					<option></option>
				</select>
			</fieldset>
			<fieldset class="allowed_filetypes ondemand">
				<label title="Allowed File-types" for="allowed_filetypes"><strong>Allowed File-types:</strong><em>The list of file-types allowed for file-uploads.</em></label>
				<select class="required _707" id="allowed_filetypes" name="allowed_filetypes[]" multiple="multiple" size="5">
					<option></option>
				</select>
			</fieldset>
			<fieldset class="default_options ondemand">
				<label title="Default Options" for="default_options"><strong>Default Options:</strong><em>'<b><i>key</i></b>'='<b><i>value</i></b>' pairs, one per line; e.g.:<br />'m=Male<br />f=Female<br />u=Not Telling'<br />'<i><b>key</b></i>'s can only contain alphanumeric, underscore (_) and dash (-).</em></label>
				<textarea class="required _708" name="default_options" id="default_options" style="height: 80px" cols="" rows=""></textarea>
			</fieldset>
			<fieldset class="maxlength ondemand">
				<label title="" for="maxlength"><strong></strong><em></em></label>
				<input type="text" class="text _709" id="maxlength" name="maxlength" value="" />
			</fieldset>
			<fieldset class="default_value ondemand">
				<label title="Default Value" for="default_value"><strong>Default Value:</strong><em>Default value(s) (more than one for '<b><i>Multiple Select</i></b>'). Separate multiple values with comma or space. Leave empty for no defaults.</em></label>
				<input type="text" class="text _710" id="default_value" name="default_value" value="" />
			</fieldset>
			<fieldset class="connector_enabled ondemand">
				<label title="Enable Connector?"><strong>Enable Connector?</strong><em>Whether to enable Connector feature (multiple entries for this field, per row), or not.</em></label>
				<span class="input ui-buttonset">
					<input type="radio" class="js__trigger_on_change" name="connector_enabled" id="yes_for__connector_enabled" value="1" />
					<input type="radio" class="js__trigger_on_change" name="connector_enabled" id="no_for__connector_enabled" value="0" />
					<label for="yes_for__connector_enabled" class="js__trigger_on_change" title="Yes">Yes</label>
					<label for="no_for__connector_enabled" class="js__trigger_on_change" title="No">No</label>
				</span>
			</fieldset>
			<fieldset class="connector_length_cap ondemand">
				<label title="Maximum Number of Items" for="connector_length_cap"><strong>Maximum Number of Items:</strong><em>Maximum number of items allowed. Enter &quot;0&quot; to disable this restriction.</em></label>
				<input type="text" class="text" id="connector_length_cap" name="connector_length_cap" value="0" />
			</fieldset>
			<fieldset class="is_html_allowed ondemand">
				<label title="Allow HTML Input?"><strong>Allow HTML Input?</strong><em>Whether to allow HTML input in this field or not.<br />Works for <i>String</i> sub-types and only when Max-Length&gt;255.</em></label>
				<span class="input ui-buttonset">
					<input type="radio" name="is_html_allowed" id="yes_for__is_html_allowed" value="1" />
					<input type="radio" name="is_html_allowed" id="no_for__is_html_allowed" value="0" />
					<label for="yes_for__is_html_allowed" title="Yes">Yes</label>
					<label for="no_for__is_html_allowed" title="No">No</label>
				</span>
			</fieldset>
			<fieldset class="is_required ondemand">
				<label title="Is a Required Field?"><strong>Is a Required Field?</strong><em>Whether the field can or cannot be left empty.</em></label>
				<span class="input ui-buttonset">
					<input type="radio" name="is_required" id="yes_for__is_required" value="1" />
					<input type="radio" name="is_required" id="no_for__is_required" value="0" />
					<label for="yes_for__is_required" title="Yes">Yes</label>
					<label for="no_for__is_required" title="No">No</label>
				</span>
			</fieldset>
			<fieldset class="is_unique ondemand">
				<label title="Is Unique?"><strong>Is Unique?</strong><em>Whether the data this field holds is unique or not. <i>Use with caution</i> since you won't be able to insert duplicate (repeating) data!</em></label>
				<span class="input ui-buttonset">
					<input type="radio" name="is_unique" id="yes_for__is_unique" value="1" />
					<input type="radio" name="is_unique" id="no_for__is_unique" value="0" />
					<label for="yes_for__is_unique" title="Yes">Yes</label>
					<label for="no_for__is_unique" title="No">No</label>
				</span>
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

	<li id="components__sr__create" class="ondemand">
		<h2 class="full_size">Create a Subroutine
			<span class="description"></span>
		</h2>

		<form id="forms__components__sr__create" action="" method="post">
		<fieldset>
			<label title="Service Mode" for="create_subroutine__service_mode"><strong>Service Mode:</strong><em>The functionality of the subroutine.</em></label>
			<select id="create_subroutine__service_mode" name="s_service_mode">
				<option value="read-only" selected="selected">Read-Only</option>
				<option value="write-only">Write-Only</option>
				<option value="read-write">Read and Write</option>
			</select>

			<label title="Data Sources"><strong>Data Sources:</strong><em>Db columns to fetch...</em></label>
			<select name="s_data_definition[]" multiple="multiple" size="5" class="required">
				<optgroup label="Select one or more:">
					<option></option>
					{{foreach from=$CONTENT.me.m_data_definition item=FIELD}}
						{{if $FIELD.connector_enabled}}
							{{if $FIELD.connector_linked and count( $FIELD.c_data_definition )}}
								{{foreach from=$FIELD.c_data_definition item=C_FIELD}}
								<option value="{{$FIELD.name}}.{{$C_FIELD.name}}">{{$C_FIELD.label}} [{{$FIELD.name|truncate:32:"...":TRUE}}.{{$C_FIELD.name|truncate:32:"...":TRUE}}]</option>
								{{/foreach}}
							{{else}}
								<option value="{{$FIELD.name}}" disabled="disabled" class="not_linked">{{$FIELD.label}} [{{$FIELD.name|truncate:32:"...":TRUE}} - not linked]</option>
							{{/if}}
						{{else}}
						<option value="{{$FIELD.name}}">{{$FIELD.label}} [{{$FIELD.name|truncate:32:"...":TRUE}}]</option>
						{{/if}}
					{{/foreach}}
				</optgroup>
				<optgroup label="Also automatically are selected:">
					<option value="id" disabled="disabled">Id [id]</option>
					<option value="tags" disabled="disabled">Tags [tags]</option>
					<option value="timestamp" disabled="disabled">Timestamp [timestamp]</option>
					<option value="submitted_by" disabled="disabled">Submitted By: [submitted_by]</option>
					<option value="status_published" disabled="disabled">Is Published? [status_published]</option>
					<option value="status_archived" disabled="disabled">Is Archived? [status_archived]</option>
					<option value="status_locked" disabled="disabled">Is Locked? [status_locked]</option>
				</optgroup>
			</select>

			<label title="Subroutine Unix Name" for="create_subroutine__name"><strong>Subroutine Unix Name:</strong><em>Lowcase alphanumeric [ASCII codebase] + underscore chars only.</em></label>
			<input type="text" class="text required" id="create_subroutine__name" name="s_name" value="" maxlength="32" style="text-transform:lowercase;" />

			<span id="create_subroutine__request_method__pathinfo">
				<label class="full_size" title="Path-Info URI-Schema" for="create_subroutine__request_method__pathinfo__uri_schema"><strong>Path-Info URI-Schema:</strong><em>URL-Schema assigned to this subroutine (required when Request-Mode = Path-Info). E.g.: '<i>/id-{id}/{timestamp}</i>'</em></label>
				<span class="input full_size">
					<span style="font-weight:bold; float:left;">{{$CONTENT.me.m_url_prefix}}/</span><input type="text" class="text required" id="create_subroutine__request_method__pathinfo__uri_schema" name="s_pathinfo_uri_schema" style="width:55%;" value="" maxlength="255" />
				</span>
			</span>

			<span id="create_subroutine__fetch_criteria">
				<label class="full_size" title="Fetch Criteria - Queries &amp; Query Groups (Policies)" for="create_subroutine__fetch_criteria__all_or_selected">
					<strong>Fetch Criteria - Queries &amp; Query Groups (Policies):</strong>
					<em>What to fetch? Determine rules by adding one or more queries. Then group those queries. If more than one groups provided, using <i>UNION DISTINCT</i> logic, each fetched data collection will be merged together.</em>
				</label>
				<span class="input full_size">
					<select id="create_subroutine__fetch_criteria__all_or_selected" name="s_fetch_criteria[do_fetch_all_or_selected]">
						<option value="all">-- fetch all records --</option>
						<option value="selected">-- fetch selected records --</option>
					</select>
					<a href="javascript:void(0);" onclick="javascript:create_subroutine__request_criteria__add_policy(this);" id="create_subroutine__fetch_criteria__add_policy_button" title="add a new group-policy (implements UNION rule)">add a new group-policy (implements UNION rule)</a>

					<span class="create_subroutine__fetch_criteria__policies">
						<textarea class="text required" name="s_fetch_criteria[policies][0]" id="create_subroutine__fetch_criteria__policies__0" style="width: 662px; height: 70px; text-transform: uppercase; clear: left;" cols="" rows="">1</textarea>
						<span class="create_subroutine__fetch_criteria__arrow_turn_left"></span>
						<i class="create_subroutine__fetch_criteria__group_heading">Group Policy 1</i>
					</span>
					<span class="pre_extra">
						<select name="s_fetch_criteria[rules][0][field_name]">
							{{if count( $CONTENT.me.m_data_definition )}}
							<option value="id">Id [id]</option>
							<option value="tags">Tags [tags]</option>
							<option value="timestamp">Timestamp [timestamp]</option>
							<option value="submitted_by">Submitted By: [submitted_by]</option>
							<option value="status_published">Is Published? [status_published]</option>
							<option value="status_archived">Is Archived? [status_archived]</option>
							<option value="status_locked">Is Locked? [status_locked]</option>
							{{/if}}

							{{foreach from=$CONTENT.me.m_data_definition item=FIELD}}
								{{if $FIELD.connector_enabled}}
									{{if $FIELD.connector_linked and count( $FIELD.c_data_definition )}}
										{{foreach from=$FIELD.c_data_definition item=C_FIELD}}
										<option value="{{$FIELD.name}}.{{$C_FIELD.name}}">{{$C_FIELD.label}} [{{$FIELD.name|truncate:32:"...":TRUE}}.{{$C_FIELD.name|truncate:32:"...":TRUE}}]</option>
										{{/foreach}}
									{{else}}
										<option value="{{$FIELD.name}}" disabled="disabled" class="not_linked">{{$FIELD.label}} [{{$FIELD.name|truncate:32:"...":TRUE}} - not linked]</option>
									{{/if}}
								{{else}}
								<option value="{{$FIELD.name}}">{{$FIELD.label}} [{{$FIELD.name|truncate:32:"...":TRUE}}]</option>
								{{/if}}
							{{/foreach}}
						</select>
						<select name="s_fetch_criteria[rules][0][math_operator]">
							<option value="&gt;">&gt;</option>
							<option value="&gt;=">&gt;=</option>
							<option value="&lt;">&lt;</option>
							<option value="&lt;=">&lt;=</option>
							<option value="=">=</option>
							<option value="!=">!=</option>
							<option value="LIKE">LIKE</option>
							<option value="NOT LIKE">NOT LIKE</option>
							<option value="IS NULL">IS NULL</option>
							<option value="IS NOT NULL">IS NOT NULL</option>
						</select>
						<select name="s_fetch_criteria[rules][0][type_of_expr_in_value]">
							<option value="generic">Generic Value [String]</option>
							<option value="math">Mathematical Value</option>
							<option value="zend_db_expr">Zend_Db_Expr</option>
						</select>
						<input type="text" class="text required" name="s_fetch_criteria[rules][0][value]" value="" style="width:200px;" />
						<span class="create_subroutine__fetch_criteria__policy_shortcut">Shortcut: <i>1</i></span>
						<a href="javascript:void(0);" onclick="javascript:create_subroutine__request_criteria__add_query(this);" class="create_subroutine__fetch_criteria__add_query_button" title="add a query-instance"></a>
					</span>

					{{* Sample for cloning , never used itself , thus name="" attribs are empty and are set by JScript after cloning *}}
					<span class="extra" style="display: none;">
						<select name="">
							{{if count( $CONTENT.me.m_data_definition)}}
							<option value="id">Id [id]</option>
							<option value="tags">Tags [tags]</option>
							<option value="timestamp">Timestamp [timestamp]</option>
							<option value="submitted_by">Submitted By: [submitted_by]</option>
							<option value="status_published">Is Published? [status_published]</option>
							<option value="status_archived">Is Archived? [status_archived]</option>
							<option value="status_locked">Is Locked? [status_locked]</option>
							{{/if}}
							{{foreach from=$CONTENT.me.m_data_definition item=FIELD}}
								{{if $FIELD.connector_enabled}}
									{{if $FIELD.connector_linked and count( $FIELD.c_data_definition )}}
										{{foreach from=$FIELD.c_data_definition item=C_FIELD}}
										<option value="{{$FIELD.name}}.{{$C_FIELD.name}}">{{$C_FIELD.label}} [{{$FIELD.name|truncate:32:"...":TRUE}}.{{$C_FIELD.name|truncate:32:"...":TRUE}}]</option>
										{{/foreach}}
									{{else}}
										<option value="{{$FIELD.name}}" disabled="disabled" class="not_linked">{{$FIELD.label}} [{{$FIELD.name|truncate:32:"...":TRUE}} - not linked]</option>
									{{/if}}
								{{else}}
								<option value="{{$FIELD.name}}">{{$FIELD.label}} [{{$FIELD.name|truncate:32:"...":TRUE}}]</option>
								{{/if}}
							{{/foreach}}
						</select>
						<select name="">
							<option value="&gt;">&gt;</option>
							<option value="&gt;=">&gt;=</option>
							<option value="&lt;">&lt;</option>
							<option value="&lt;=">&lt;=</option>
							<option value="=">=</option>
							<option value="!=">!=</option>
							<option value="LIKE">LIKE</option>
							<option value="NOT LIKE">NOT LIKE</option>
							<option value="IS NULL">IS NULL</option>
							<option value="IS NOT NULL">IS NOT NULL</option>
						</select>
						<select name="">
							<option value="generic">Generic Value [String]</option>
							<option value="math">Mathematical Value</option>
							<option value="zend_db_expr">Zend_Db_Expr</option>
						</select>
						<input type="text" class="text required" name="" value="" style="width:200px;" />
						<span class="create_subroutine__fetch_criteria__policy_shortcut">Shortcut: <i>2</i></span>
						<a href="javascript: void(0);" onclick="javascript:create_subroutine__request_criteria__remove_query(this);" class="create_subroutine__fetch_criteria__remove_query_button" title="remove"></a>
					</span>
				</span>
			</span>

			<label title="Fetch Criteria - Limit" for="create_subroutine__fetch_criteria__limit"><strong>Fetch Criteria -  Limit:</strong><em>Total number of fetched rows. Leave empty to fetch everything that matches the request criteria.</em></label>
			<input type="text" name="s_fetch_criteria[limit]" id="create_subroutine__fetch_criteria__limit" class="text" />

			<label title="Fetch Criteria - Pagination" for="create_subroutine__fetch_criteria__pagination"><strong>Fetch Criteria - Pagination:</strong><em>Number of results per page. Leave empty for no pagination.</em></label>
			<input type="text" name="s_fetch_criteria[pagination]" id="create_subroutine__fetch_criteria__pagination" class="text" maxlength="3" />

			<span id="create_subroutine__fetch_criteria__order">
				<label class="full_size" title="Fetch Criteria - Sorting" for="create_subroutine__fetch_criteria__do_perform_sorting">
					<strong>Fetch Criteria - Sorting:</strong><em>Sort (order) fetched data by one or more columns in certain direction(s) (ascending or descending, sequence-sensitive).</em>
				</label>
				<span class="input full_size">
					<select id="create_subroutine__fetch_criteria__do_perform_sorting" name="s_fetch_criteria[do_sort]">
						<option value="0" selected="selected">-- disable sorting --</option>
						<option value="1">-- enable sorting --</option>
					</select>
					<a href="javascript:void(0);" onclick="javascript:create_subroutine__request_criteria__add_sorting(this);" id="create_subroutine__fetch_criteria__add_sorting_button" title="add a new sorting-rule">add a new sorting-rule</a>
					<span class="create_subroutine__sort_by">
						<select name="s_fetch_criteria[sort_by][0][field_name]">
							{{if count( $CONTENT.me.m_data_definition)}}
							<option value="id">Id [id]</option>
							<option value="tags">Tags [tags]</option>
							<option value="timestamp">Timestamp [timestamp]</option>
							<option value="submitted_by">Submitted By: [submitted_by]</option>
							<option value="status_published">Is Published? [status_published]</option>
							<option value="status_archived">Is Archived? [status_archived]</option>
							<option value="status_locked">Is Locked? [status_locked]</option>
							{{/if}}
							{{foreach from=$CONTENT.me.m_data_definition item=FIELD}}
								{{if $FIELD.connector_enabled}}
									{{if $FIELD.connector_linked and count( $FIELD.c_data_definition )}}
										{{foreach from=$FIELD.c_data_definition item=C_FIELD}}
										<option value="{{$FIELD.name}}.{{$C_FIELD.name}}">{{$C_FIELD.label}} [{{$FIELD.name|truncate:32:"...":TRUE}}.{{$C_FIELD.name|truncate:32:"...":TRUE}}]</option>
										{{/foreach}}
									{{else}}
										<option value="{{$FIELD.name}}" disabled="disabled" class="not_linked">{{$FIELD.label}} [{{$FIELD.name|truncate:32:"...":TRUE}} - not linked]</option>
									{{/if}}
								{{else}}
								<option value="{{$FIELD.name}}">{{$FIELD.label}} [{{$FIELD.name|truncate:32:"...":TRUE}}]</option>
								{{/if}}
							{{/foreach}}
						</select>
						<select name="s_fetch_criteria[sort_by][0][dir]">
							<option value="ASC">Ascending</option>
							<option value="DESC">Descending</option>
						</select>
					</span>
				</span>
			</span>
		</fieldset>

		<div class="system_console"></div>

		<fieldset class="buttons">
			<input type="hidden" name="do" value="" />
			<input type="hidden" name="m_unique_id" value="{{$CONTENT.me.m_unique_id}}" />
			<input type="submit" value="Create Subroutine" />
			<input type="reset" value="Clear Form" />
			<input type="button" value="Cancel &amp; Close" />
		</fieldset>
		</form>
	</li>

	<li id="components__ddl__list">
		<h2 class="full_size">Data Definition Management (for Master-unit) - Actual for: <em>/{{$CONTENT.me.m_name}}</em>
			<span class="description">
			... create and manage data-fields of your module
			</span>
		</h2>

		<form id="forms__components__ddl__list" class="js__go_ajax" method="post" action="">
		<table class="full_size{{if count($CONTENT.me.m_data_definition)}} tablesorter {sortlist: [[0,0]]}{{/if}}" id="tables__components__ddl__list">
			<thead>
				<tr>
					<th style="width:7%; white-space:nowrap;">Pos.</th>
					<th style="width:25%; white-space:nowrap;">Field Name</th>
					<th style="width:25%; white-space:nowrap;">Data Type</th>
					<th style="width:43%; white-space:nowrap;"{{if count($CONTENT.me.m_data_definition)}} class="{sorter: false}"{{/if}}>Used In ...</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="4">
					<div class="system_console"></div>
					<fieldset class="buttons">
						<input type="button" value="Create New" />
						<input type="hidden" name="do" value="" />
						<input type="hidden" name="ddl_checklist" value="" />
						<input type="hidden" name="m_unique_id" value="{{$CONTENT.me.m_unique_id}}" />
						<input type="hidden" name="do_backup_dropped_field" value="1" />
					</fieldset></td>
				</tr>
			</tfoot>
			<tbody{{if count($CONTENT.me.m_data_definition)}} class="js__sortable"{{/if}}>
				{{foreach from=$CONTENT.me.m_data_definition item=FIELD}}
				<tr id="position_{{$FIELD.name}}">
					<td align="center">{{$FIELD.position}}</td>
					<td>
						<span class="name {{if $CONTENT.me.m_title_column eq $FIELD.name}}is_title{{else}}is_not_title{{/if}}" title="{{$FIELD.label}}">{{$FIELD.name}}</span>
						<ul class="actions">
							{{if $CONTENT.me.m_title_column neq $FIELD.name}}
								<li class="ui-icon ui-icon-flag"><a class="ddl_alter__set_title_column" href="?{{$FIELD.name}}" title="Define as Title">Define as Title</a></li>
							{{/if}}
							<li class="ui-icon ui-icon-pencil"><a class="ddl_alter__edit" href="?{{$FIELD.name}}" title="Edit">Edit</a></li>
							<li class="ui-icon ui-icon-closethick"><a class="ddl_alter__drop" href="?{{$FIELD.name}}" title="Drop">Drop</a></li>
							{{if $FIELD.connector_enabled}}
								{{if $FIELD.connector_linked}}
									<li class="ui-icon ui-icon-seek-next"><a href="{{$MODULE_URL}}/components/viewconnector-{{$CONTENT.me.m_unique_id_clean}}-{{$FIELD.name}}" title="Manage Connector-unit">Manage Connector-unit</a></li>
								{{else}}
									<li class="ui-icon ui-icon-transferthick-e-w"><a class="ddl_alter__link_to_connector_unit" href="?{{$FIELD.name}}" title="Link to Connector-unit">Link to Connector-unit</a></li>
								{{/if}}
							{{/if}}
						</ul>
					</td>
					<td>{{$FIELD.type}}{{if $FIELD.subtype neq ''}}/{{$FIELD.subtype}}{{/if}}{{if $FIELD.maxlength neq ''}} [{{$FIELD.maxlength|truncate:30:"...":TRUE}}]{{/if}}</td>
					<td align="left">
					{{if isset( $FIELD.used_in ) && count( $FIELD.used_in )}}
						<ul class="dependency_list">{{foreach from=$FIELD.used_in item=S_NAME}}<li>{{$S_NAME}}</li>{{/foreach}}</ul>
					{{else}}--{{/if}}
					</td>
				</tr>
				{{foreachelse}}
				<tr>
					<td colspan="4" class="no_data">No data-fields found!</td>
				</tr>
				{{/foreach}}
			</tbody>
		</table>
		</form>
	</li>

	{{if count($CONTENT.me.m_data_definition_bak)}}
	<li id="components__ddl__list_bak">
		<h2 class="full_size">Data Definition Management (for Master-unit) - Backup for: <em>/{{$CONTENT.me.m_name}}</em>
			<span class="description">
			... restore data-fields of your module from backups
			</span>
		</h2>

		<form id="forms__components__ddl__list_bak" class="js__go_ajax" method="post" action="">
		<table class="full_size tablesorter" id="tables__components__ddl__list_bak">
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
						<input type="hidden" name="m_unique_id" value="{{$CONTENT.me.m_unique_id}}" />
					</fieldset>
					</td>
				</tr>
			</tfoot>
			<tbody>
				{{foreach from=$CONTENT.me.m_data_definition_bak item=FIELD }}
				<tr>
					<td><span class="name"><a href="#" title="{{$FIELD.label}}" onclick="javascript: void(0);">{{$FIELD.name|truncate:16:"...":TRUE}}</a></span></td>
					<td>{{$FIELD.type}}{{if $FIELD.subtype neq ''}}/{{$FIELD.subtype}}{{/if}}{{if $FIELD.maxlength neq ''}} [{{$FIELD.maxlength|truncate:64:"...":TRUE}}]{{/if}}</td>
					<td align="center"><input type="checkbox" name="ddl_checklist[]" value="{{$FIELD.name}}" /></td>
				</tr>
				{{/foreach}}
			</tbody>
		</table>
		</form>
	</li>
	{{/if}}

	<li id="components__sr__list">
		<h2 class="full_size">Subroutine Management for: <em>/{{$CONTENT.me.m_name}}</em>
			<span class="description"></span>
		</h2>

		<form id="forms__components__sr__list" method="post" action="">
		<table class="full_size tablesorter {sortlist: [[0,0]]}" id="tables__components__sr__list">
			<thead>
				<tr>
					<th style="width: 50%; white-space: nowrap;">Subroutine Name</th>
					<th style="width: 45%; white-space: nowrap;" class="{sorter: false}">Data Repository</th>
					<th style="width: 5%;" class="{sorter: false}"></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="3">
					<div class="system_console"></div>
					<fieldset class="buttons">
						<input type="button" value="Create New" {{if count($CONTENT.me.m_data_definition) eq 0}}disabled="disabled"{{/if}} />
						<input type="button" value="Permanently Remove Selected" />
						<input type="hidden" name="do" value="" />
						<input type="hidden" name="m_unique_id" value="{{$CONTENT.me.m_unique_id}}" />
					</fieldset>
					</td>
				</tr>
			</tfoot>
			<tbody>
				{{foreach from=$CONTENT.me.m_subroutines item=SR}}
				<tr>
					<td><span class="name {{if $SR.s_service_mode eq 'read-only'}}is_ro{{elseif $SR.s_service_mode eq 'write-only'}}is_wo{{elseif $SR.s_service_mode eq 'read-write'}}is_rw{{/if}}"><a href="#" onclick="javascript: void(0);">{{$SR.s_name|truncate:20:"...":TRUE}}</a></span></td>
					<td>
					{{if count( $SR.s_data_definition )}}
						<ul class="dependency_list">{{foreach from=$SR.s_data_definition item=DDF}}<li>{{$DDF.name|truncate:32:"...":TRUE}}</li>{{/foreach}}</ul>
					{{else}}-- all --{{/if}}
					</td>
					<td align="center"><input type="radio" name="s_name" value="{{$SR.s_name}}" /></td>
				</tr>
				{{foreachelse}}
				<tr>
					<td colspan="3"><span class="system_message_error">No Subroutines Found!<br />
					{{if count($CONTENT.me.m_data_definition) eq 0}}You cannot create new one, until you create at least one data-field!{{/if}}</span></td>
				</tr>
				{{/foreach}}
			</tbody>
		</table>
		</form>
	</li>
</ul>