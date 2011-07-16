	<samp id="system_console" class="full_size"></samp>

	<ul class="data_container full_size ui-accordion">
		{{foreach from=$CONTENT item=GROUP}}
		<li>
			<h2><a href="?{{$GROUP.conf_group_keyword}}">{{$GROUP.conf_group_title}}</a></h2>

			<form method="post" action="?{{$GROUP.conf_group_keyword}}" id="forms__settings__edit js__go_ajax">
			{{foreach from=$GROUP._items item=SETTINGS}}
				{{if is_array( $SETTINGS )}}
					{{if $SETTINGS.conf_start_group neq ''}}
					<h3 class="start_group">{{$SETTINGS.conf_start_group}}</h3>
					<fieldset class="deco_1">
					{{/if}}

						<label title="{{$SETTINGS.conf_title}}" {{if $SETTINGS.conf_type eq 'yes_no'}}for=""{{else}}for="settings_{{$SETTINGS.conf_key}}"{{/if}}>
							<strong>{{$SETTINGS.conf_title}}</strong>
							<em>{{$SETTINGS.conf_description}}</em>
						</label>

						{{if $SETTINGS.conf_type eq 'yes_no'}}
						<span class="input ui-buttonset">
							<input type="radio" class="radio" name="conf_key[{{$SETTINGS.conf_key}}]" id="yes_for__{{$SETTINGS.conf_key}}" value="1" {{if $SETTINGS.conf_real_value eq 1}}checked="checked"{{/if}} />
							<label for="yes_for__{{$SETTINGS.conf_key}}" title="Yes">Yes</label>
							<label for="no_for__{{$SETTINGS.conf_key}}" title="No">No</label>
							<input type="radio" class="radio" name="conf_key[{{$SETTINGS.conf_key}}]" id="no_for__{{$SETTINGS.conf_key}}" value="0" {{if $SETTINGS.conf_real_value eq 0}}checked="checked"{{/if}} />
						</span>
						{{elseif $SETTINGS.conf_type eq 'input'}}
						<input type="text" class="text" id="settings_{{$SETTINGS.conf_key}}" name="conf_key[{{$SETTINGS.conf_key}}]" value="{{$SETTINGS.conf_real_value}}" />
						{{elseif $SETTINGS.conf_type eq 'textarea'}}
						<textarea name="conf_key[{{$SETTINGS.conf_key}}]" id="settings_{{$SETTINGS.conf_key}}">{{$SETTINGS.conf_real_value}}</textarea>
						{{elseif $SETTINGS.conf_type eq 'dropdown'}}
							{{if is_array( $SETTINGS.conf_extra )}}
							<select name="conf_key[{{$SETTINGS.conf_key}}]" id="settings_{{$SETTINGS.conf_key}}">
								{{foreach from=$SETTINGS.conf_extra key=K item=V}}
								<option value="{{$K}}" {{if $SETTINGS.conf_real_value eq $K}}selected="selected"{{/if}}>{{$V}}</option>
								{{/foreach}}
							</select>
							{{else}}
							Error - empty dropdown extraz! Skipping...
							{{/if}}
						{{elseif $SETTINGS.conf_type eq 'multi'}}
							{{if is_array( $SETTINGS.conf_extra )}}
							<select name="conf_key[{{$SETTINGS.conf_key}}][]" id="settings_{{$SETTINGS.conf_key}}" multiple="multiple">
								{{foreach from=$SETTINGS.conf_extra key=K item=V}}
								<option value="{{$K}}" {{if in_array( $K, $SETTINGS.conf_real_value )}}selected="selected"{{/if}}>{{$V}}</option>
								{{/foreach}}
							</select>
							{{else}}
							Error - empty dropdown extraz! Skipping...
							{{/if}}
						{{/if}}
						{{if $SETTINGS.conf_value !== $SETTINGS.conf_default and !is_null( $SETTINGS.conf_value ) and $SETTINGS.conf_value != ''}}
						<input type="button" class="revert" title="Revert to default value" id="revert_{{$SETTINGS.conf_id}}" />
						{{/if}}

					{{if $SETTINGS.conf_end_group neq ''}}
					</fieldset>
					<div class="closing"></div>{{/if}}

				{{/if}}
			{{/foreach}}
			<div class="buttons">
			<input type="hidden" name="conf_group_id" value="{{$GROUP.conf_group_id}}" />
			<input type="hidden" name="do" value="edit" />
			<input type="submit" value="Save Settings" />
			<input type="reset" value="Reset Form" />
			</div>
			</form>
		</li>
		{{/foreach}}
	</ul>