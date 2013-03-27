<div class="aside full_size" id="system_console"></div>
<ul class="section full_size">
	<li id="components__ddl__alter_add" class="ondemand">
		<h2>Data Definition Management (for Master-unit)</h2>
		<form id="forms__components__ddl__alter_add" action="" method="post" class="js__go_ajax">
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

	<li id="components__sr__create" class="ondemand">
		<h2>Create a Subroutine
			<span class="description"></span>
		</h2>

		<form id="forms__components__sr__create" class="js__go_ajax" action="" method="post">
			<fieldset class="s_name">
				<label title="Subroutine Unix Name" for="sr__s_name"><strong>Subroutine Unix Name:</strong></label>
				<input type="text" class="text required _701" id="sr__s_name" name="s_name" value="" maxlength="32" style="text-transform: lowercase;" />
				<em class="ui-tooltip">Lowercase alphanumeric [ASCII codebase] + underscore characters only!</em>
			</fieldset>
			<fieldset class="s_pathinfo_uri_schema">
				<label title="Path-Info URI-Schema" for="sr__s_pathinfo_uri_schema"><strong>Path-Info URI-Schema:</strong></label>
				<span class="input">
					<span style="font-weight:bold; float:left;">{{$CONTENT.me.m_url_prefix}}/</span>
					<input type="text" class="text required _702" id="sr__s_pathinfo_uri_schema" name="s_pathinfo_uri_schema" style="width: 300px;" value="" maxlength="255" />
				</span>
				<em class="ui-tooltip">URL-Schema assigned to this subroutine (required when Request-Mode = Path-Info). E.g.: '<i>/id-{id}/{timestamp}</i>'</em>
			</fieldset>

			<fieldset class="ui-tabs {tabs:{cookie:null,disabled:[1,2,3]}} sr_alter_add__s_data_flow_config">
				<ul>
					<li><a href="#tabs__sr_alter_add__s_help">Data-flow Configuration</a></li>
					<li><a href="#tabs__sr_alter_add__s_data_source">Data-source</a></li>
					<li><a href="#tabs__sr_alter_add__s_data_processing">Data-processing</a></li>
					<li><a href="#tabs__sr_alter_add__s_data_target">Data-target</a></li>
				</ul>
				<fieldset id="tabs__sr_alter_add__s_help">
					<fieldset class="s_data_source">
						<label title="Data-source" for="sr__s_data_source"><strong>Data-source:</strong></label>
						<select id="sr__s_data_source" name="s_data_source" class="js__trigger_on_change">
							<option value="no-fetch">-- no content fetching --</option>
							<option value="rdbms">Module RDBMS data-repository</option>

							{{if in_array("dom", $CONFIG.runtime.loaded_extensions)}}
							<option value="dom">External, DOM-based document (HTML, XML etc) source [over HTTP]</option>
							{{else}}
							<option value="dom" disabled="disabled">External, DOM-based document (HTML, XML etc) source [over HTTP] - Disabled: PHP-DOM extension not loaded!</option>
							{{/if}}

							<option value="json">External,JSON/P-based source [over HTTP]</option>
						</select>
						<em class="ui-tooltip">Where to fetch the content from?</em>
					</fieldset>
					<fieldset class="s_data_target">
						<label title="Data-target" for="sr__s_data_target"><strong>Data-target:</strong></label>
						<select id="sr__s_data_target" name="s_data_target">
							<option value="tpl">Template engine</option>
							<option value="rdbms">Module RDBMS data-repository</option>
						</select>
						<em class="ui-tooltip">Where to redirect the processed content?</em>
					</fieldset>
				</fieldset>
				<fieldset id="tabs__sr_alter_add__s_data_source">
					<fieldset class="s_data_definition ondemand">
						<label title="Columns to Fetch" for="sr__s_data_definition"><strong>Columns to Fetch:</strong></label>
						<select id="sr__s_data_definition" name="s_data_definition[]" multiple="multiple" size="5" class="required _700">
							<optgroup label="Select one or more:">
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
							<optgroup label="Following are automatically selected:">
								<option value="id" disabled="disabled">Id [id]</option>
								<option value="tags" disabled="disabled">Tags [tags]</option>
								<option value="timestamp" disabled="disabled">Timestamp [timestamp]</option>
								<option value="submitted_by" disabled="disabled">Submitted By: [submitted_by]</option>
								<option value="status_published" disabled="disabled">Is Published? [status_published]</option>
								<option value="status_archived" disabled="disabled">Is Archived? [status_archived]</option>
								<option value="status_locked" disabled="disabled">Is Locked? [status_locked]</option>
							</optgroup>
						</select>
						<em class="ui-tooltip">Columns/fields to fetch.</em>
					</fieldset>

					<fieldset class="s_fetch_criteria__all_or_selected ondemand">
						<label title="Fetch Criteria - Queries &amp; Policies" for="s_fetch_criteria__all_or_selected"><strong>Fetch Criteria - Queries &amp; Policies:</strong></label>
						<select id="s_fetch_criteria__all_or_selected" class="js__trigger_on_change" name="s_fetch_criteria__all_or_selected">
							<option value="all">-- fetch all records --</option>
							<option value="selected">-- fetch selected records --</option>
						</select>
						<em class="ui-tooltip full_size">What to fetch? Determine rules by adding one or more queries. Then group those queries. If more than one groups provided, using <i>UNION DISTINCT</i> logic, each fetched data collection will be merged together.</em>
						<span class="input s_fetch_criteria__all_or_selected full_size">
							<span class="s_fetch_criteria__policies ondemand">
								<label for="s_fetch_criteria__policies__0">Query Policy 1</label>
								<textarea class="text required _704-0" name="s_fetch_criteria[policies][0]" id="s_fetch_criteria__policies__0" style="width: 662px; height: 70px; text-transform: uppercase; clear: left;" cols="" rows="">1</textarea>
								<button type="button" class="buttons__s_fetch_criteria__add_policy">add a new group-policy (implements UNION rule)</button>
							</span>
							<span class="s_fetch_criteria__query ondemand">
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
								<select name="s_fetch_criteria[rules][0][type_of_expr_in_value]" class="_709-0">
									<option value="generic">Generic Value [String]</option>
									<option value="math">Mathematical Value</option>
									<option value="zend_db_expr">Zend_Db_Expr</option>
								</select>
								<input type="text" class="text required _705-0" name="s_fetch_criteria[rules][0][value]" value="" style="width:200px;" />
								<span class="s_fetch_criteria__policy_shortcut">Shortcut: <i>1</i></span>
								<button type="button" class="buttons__s_fetch_criteria__add_query">add a query</button>
							</span>
						</span>
					</fieldset>

					<fieldset class="s_fetch_criteria__limit ondemand">
						<label title="Fetch Criteria - Limit" for="s_fetch_criteria__limit"><strong>Fetch Criteria -  Limit:</strong></label>
						<input type="text" name="s_fetch_criteria[limit]" id="s_fetch_criteria__limit" class="text _706" />
						<em class="ui-tooltip">Total number of fetched rows. Leave empty to fetch everything that matches the request criteria.</em>
					</fieldset>

					<fieldset class="s_fetch_criteria__pagination ondemand">
						<label title="Fetch Criteria - Pagination" for="s_fetch_criteria__pagination"><strong>Fetch Criteria - Pagination:</strong></label>
						<input type="text" name="s_fetch_criteria[pagination]" id="s_fetch_criteria__pagination" class="text _707" maxlength="3" />
						<em class="ui-tooltip">Number of fetched rows per page. Leave empty for no pagination.</em>
					</fieldset>

					<fieldset class="s_fetch_criteria__do_perform_sorting ondemand">
						<label title="Fetch Criteria - Sorting" for="s_fetch_criteria__do_perform_sorting"><strong>Fetch Criteria - Sorting:</strong></label>
						<select id="s_fetch_criteria__do_perform_sorting" name="s_fetch_criteria__do_perform_sorting" class="js__trigger_on_change">
							<option value="0">-- disable sorting --</option>
							<option value="1">-- enable sorting --</option>
						</select>
						<em class="ui-tooltip full_size">Sort (order) fetched data by one or more columns in certain direction(s) (ascending or descending, sequence-sensitive).</em>
						<span class="input s_fetch_criteria__do_perform_sorting full_size">
							<span class="sr__sort_by ondemand">
								<select name="s_fetch_criteria[sort_by][0][field_name]" class="_708-0">
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
								<select name="s_fetch_criteria[sort_by][0][dir]" class="_708-0">
									<option value="ASC">Ascending</option>
									<option value="DESC">Descending</option>
								</select>
								<button type="button" class="buttons__s_fetch_criteria__add_sorting">add a new sorting-rule</button>
							</span>
						</span>
					</fieldset>
				</fieldset>
				<fieldset id="tabs__sr_alter_add__s_data_processing">tabs__sr_alter_add__s_data_processing
				</fieldset>
				<fieldset id="tabs__sr_alter_add__s_data_target">
					<label title="Data compatibility" for="sr__s_data_compatibility"><strong>Data Compatibility:</strong><em>Additional processing/conversions, to perform on the processed data.</em></label>
					<select id="sr__s_data_compatibility" name="s_data_compatibility">
						<option value="">-- none --</option>
						<option value="xml-cdata-compatible">XML- or (X)HTML-compatible (e.g. encode special characters etc)</option>
					</select>
				</fieldset>
			</fieldset>

			<div class="system_console"></div>

			<fieldset class="buttons">
				<input type="hidden" name="do" value="sr_alter__add" />
				<input type="hidden" name="m_unique_id" value="{{$CONTENT.me.m_unique_id}}" />
				<input type="submit" value="Create New Subroutine" />
				<input type="reset" value="Clear Form" />
				<input type="button" value="Cancel &amp; Close" />
			</fieldset>
		</form>
	</li>

	<li id="components__ddl__list">
		<h2>Data Definition Management (for Master-unit) - Actual for: <em>/{{$CONTENT.me.m_name}}</em></h2>

		<form id="forms__components__ddl__list" class="js__go_ajax" method="post" action="">
		<table class="full_size{{if count($CONTENT.me.m_data_definition)}} tablesorter {sortlist: [[0,0]]}{{/if}}" id="tables__components__ddl__list">
			<thead>
				<tr>
					<th style="width: 7%; white-space: nowrap; text-align: center;">#</th>
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
		<h2>Data Definition Management (for Master-unit) - Backup for: <em>/{{$CONTENT.me.m_name}}</em>
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
					<th style="width: 5%;" class="{sorter: false}"></th>
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
		<h2>Subroutine Management for: <em>/{{$CONTENT.me.m_name}}</em></h2>

		<form id="forms__components__sr__list" class="js__go_ajax" method="post" action="">
		<table class="full_size{{if count($CONTENT.me.m_subroutines)}} tablesorter {sortlist: [[0,0]]}{{/if}}" id="tables__components__sr__list">
			<thead>
				<tr>
					<th style="width: 35%; white-space: nowrap;">Subroutine Name</th>
					<th style="width: 65%; white-space: nowrap;" class="{sorter: false}">Data Repository</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="2">
					<div class="system_console"></div>
					<fieldset class="buttons">
						<input type="button" value="Create New" {{if count($CONTENT.me.m_data_definition) eq 0}}disabled="disabled"{{/if}} />
						<input type="hidden" name="do" value="" />
						<input type="hidden" name="s_name" value="" />
						<input type="hidden" name="m_unique_id" value="{{$CONTENT.me.m_unique_id}}" />
					</fieldset>
					</td>
				</tr>
			</tfoot>
			<tbody>
				{{foreach from=$CONTENT.me.m_subroutines item=SR}}
				<tr>
					<td><span class="name"><a href="#" onclick="javascript: void(0);">{{$SR.s_name|truncate:20:"...":TRUE}}</a></span>
						<ul class="actions">
							<li class="ui-icon ui-icon-pencil"><a class="sr_alter__edit" href="?{{$SR.s_name}}" title="Edit">Edit</a></li>
							{{if $SR.s_can_remove}}
								<li class="ui-icon ui-icon-closethick"><a class="sr_alter__drop" href="?{{$SR.s_name}}" title="Drop">Drop</a></li>
							{{/if}}
						</ul>
					</td>
					<td>
					{{if count( $SR.s_data_definition )}}
						<ul class="dependency_list">{{foreach from=$SR.s_data_definition item=DDF}}<li>{{$DDF.name|truncate:32:"...":TRUE}}</li>{{/foreach}}</ul>
					{{else}}-- all --{{/if}}
					</td>
				</tr>
				{{foreachelse}}
				<tr>
					<td colspan="2" class="no_data">No Subroutines Found!
					{{if !count($CONTENT.me.m_data_definition)}}You cannot create new one, until you create at least one data-field!{{/if}}</td>
				</tr>
				{{/foreach}}
			</tbody>
		</table>
		</form>
	</li>
</ul>
