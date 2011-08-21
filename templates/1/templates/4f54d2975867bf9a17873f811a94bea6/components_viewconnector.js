function ddl_add__select_dft_subtype ( dft_type )
{
	switch ( dft_type )
	{
		case 'alphanumeric':
			toggle_form_container( "dft_links_with", "off" );
			toggle_form_container( "dft_links_with__fields_to_fetch", "off" );
			toggle_form_container( "dft__non_link_stuff", "on" );
			state = $("SPAN[id^='dft_subtype_options_for__']").hide();
			state.filter("[id='dft_subtype_options_for__alphanumeric']").show();
			$("SELECT[name='dft_subtype__alphanumeric']").val("string");
			break;
		case 'file':
			toggle_form_container( "dft_links_with", "off" );
			toggle_form_container( "dft_links_with__fields_to_fetch", "off" );
			toggle_form_container( "dft__non_link_stuff", "on" );
			state = $("SPAN[id^='dft_subtype_options_for__']").hide();
			state.filter("[id='dft_subtype_options_for__file']").show();
			$("SELECT[name='dft_subtype__file']").val("image");
			break;
		case 'link':
			toggle_form_container( "dft_is_unique", "on" );
			toggle_form_container( "dft_links_with", "on" );
			// toggle_form_container( "dft_links_with__fields_to_fetch", "on" );
			toggle_form_container( "dft__non_link_stuff", "off" );
			$("SELECT[name='dft_links_with']").val("");
			$("SELECT#register__dft_links_with").change();
			return 1;
			break;
	}

	return ddl_add__select_dft_maxlength( dft_type, $("FORM#forms__components__ddl__alter_add SELECT[name='dft_subtype__" + dft_type + "']").val() );
}

function ddl_add__select_dft_maxlength ( dft_type, dft_subtype )
{
	scrollToConsole();

	$("SAMP#system_console").html("Required fields (in red) must be filled-in!").removeClass("error success").addClass("notice").show();
	toggle_form_container( "dft_links_with", "off" );
	toggle_form_container( "dft_links_with__fields_to_fetch", "off" );
	$("SPAN[id^='dft_maxlength_options_for__']").hide();
	toggle_form_container( "dft_default_options", "off" );
	toggle_form_container( "dft_default_value", "on" );
	$("SPAN#dft_html_allowed").hide();
	// toggle_form_container( "dft_is_required", "on" );
	ddl_add__try_to_toggle_visibility_of_uniqueness("on");
	toggle_form_container( "dft_allowed_filetypes", "off" );
	$("SELECT#register__dft_allowed_filetypes").hide();
	toggle_form_container( "dft_connectors_enabled", "on" );

	switch ( dft_joint = dft_type + "__" + dft_subtype )
	{
		/***************************
		 * ALPHANUMERIC sub-types
		 ***************************/

		case 'alphanumeric__string':
			toggle_form_container( "dft_maxlength_options_for__alphanumeric__string", "on" );
			toggle_form_container( "dft_html_allowed", "on" );
			break;
		case 'alphanumeric__integer_signed_8':
			$("FORM#forms__components__ddl__alter_add INPUT[name='dft_maxlength__alphanumeric__integer']").val("4");
			toggle_form_container( "dft_maxlength_options_for__alphanumeric__integer", "on" );
			break;
		case 'alphanumeric__integer_unsigned_8':
			$("FORM#forms__components__ddl__alter_add INPUT[name='dft_maxlength__alphanumeric__integer']").val("3");
			toggle_form_container( "dft_maxlength_options_for__alphanumeric__integer", "on" );
			break;
		case 'alphanumeric__integer_signed_16':
			$("FORM#forms__components__ddl__alter_add INPUT[name='dft_maxlength__alphanumeric__integer']").val("6");
			toggle_form_container( "dft_maxlength_options_for__alphanumeric__integer", "on" );
			break;
		case 'alphanumeric__integer_unsigned_16':
			$("FORM#forms__components__ddl__alter_add INPUT[name='dft_maxlength__alphanumeric__integer']").val("5");
			toggle_form_container( "dft_maxlength_options_for__alphanumeric__integer", "on" );
			break;
		case 'alphanumeric__integer_signed_24':
		case 'alphanumeric__integer_unsigned_24':
			$("FORM#forms__components__ddl__alter_add INPUT[name='dft_maxlength__alphanumeric__integer']").val("8");
			toggle_form_container( "dft_maxlength_options_for__alphanumeric__integer", "on" );
			break;
		case 'alphanumeric__integer_signed_32':
			$("FORM#forms__components__ddl__alter_add INPUT[name='dft_maxlength__alphanumeric__integer']").val("11");
			toggle_form_container( "dft_maxlength_options_for__alphanumeric__integer", "on" );
			break;
		case 'alphanumeric__integer_unsigned_32':
			$("FORM#forms__components__ddl__alter_add INPUT[name='dft_maxlength__alphanumeric__integer']").val("10");
			toggle_form_container( "dft_maxlength_options_for__alphanumeric__integer", "on" );
			break;
		case 'alphanumeric__integer_signed_64':
		case 'alphanumeric__integer_unsigned_64':
			$("FORM#forms__components__ddl__alter_add INPUT[name='dft_maxlength__alphanumeric__integer']").val("20");
			toggle_form_container( "dft_maxlength_options_for__alphanumeric__integer", "on" );
			break;
		case 'alphanumeric__decimal_signed':
		case 'alphanumeric__decimal_unsigned':
			toggle_form_container( "dft_maxlength_options_for__alphanumeric__decimal", "on" );
			break;
		case 'alphanumeric__dropdown':
		case 'alphanumeric__multiple':
			toggle_form_container( "dft_default_options", "on" );
			ddl_add__enable_connectors( false );
			toggle_form_container( "dft_connectors_enabled", "off" );
			$("SAMP#system_console").html("<b>Notice:</b> Switching to " + ( ( dft_subtype == 'dropdown' ) ? "Single Select" : "Multiple Select" ) + " sub-type made following changes to the form:<ul><li>'Is Unique?' setting was disabled,</li><li>Connector feature was disabled.</ul>").removeClass("error success").addClass("notice").show();
			break;

		/********************
		 * FILE sub-types
		 ********************/

		case 'file__any':
		case 'file__image':
		case 'file__video':
		case 'file__audio':
			toggle_form_container( "dft_maxlength_options_for__file", "on" );
			toggle_form_container( "dft_allowed_filetypes", "on" );
			toggle_form_container( "dft_default_value", "off" );
			ddl_add__enable_connectors( true );
			$("SAMP#system_console").html("<b>Notice:</b> Switching to File type made following changes to the form:<ul><li>'Is Unique?' setting was disabled,</li><li>Connector feature was enabled (default for this type).</li></ul>").removeClass("error success").addClass("notice").show();

			/*********************
			 * Fetch MIME list
			 *********************/

			$("SPAN#dft_allowed_filetypes SPAN.input").html("Loading ...").show();
			$("SPAN#dft_allowed_filetypes SELECT#register__dft_allowed_filetypes").hide();

			$_url = window.location.href + "?do=ddl_alter__add__mimelist__do_fetch&output=json";
			$.ajax({
				type:"POST",
				url:$_url,
				dataType:"json",
				data:"mimetype=" + dft_subtype,
				cache:true,
				timeout:15000,
				error:function( xhr, text_status, error ){
					$("SAMP#system_console").html("ERROR! MIME list could not be fetched!").removeClass("notice success").addClass("error");
					return 0;
				},
				success:function( data )
				{
					if ( dLen = data.length )
					{
						$("SPAN#dft_allowed_filetypes SPAN.input").hide();
						$("SPAN#dft_allowed_filetypes SELECT#register__dft_allowed_filetypes").show();
						$mimelist__nr_of_options = $("SELECT#register__dft_allowed_filetypes").children().length;
						for ( i = 0; i < $mimelist__nr_of_options; i++ )
						{
							// Weird huh? :) JScript shifts all keys upwards, once something from top of the stack is deleted.
							$( "SELECT#register__dft_allowed_filetypes OPTION:eq(0)" ).remove();
						}

						for ( i = 0; i < dLen; i++ )
						{
							$("SELECT#register__dft_allowed_filetypes")
								.append("<option value=\"" + data[i].type_extension + "\">" + data[i].type_extension + " - " + data[i].type_description + "</option>");
						}

						return 1;
					}
					else
					{
						$("SAMP#system_console").html( "No MIME-types could be found in our records!" ).removeClass("notice success").addClass("error").show();
						$("SPAN#dft_allowed_filetypes SPAN.input").html("Error! Check console for details...").show();
						$("SPAN#dft_allowed_filetypes SELECT#register__dft_allowed_filetypes").hide();
					}
				}
			});
			break;
	}
	return 1;
}

function ddl_add__enable_connectors ( do_enable )
{
	if ( do_enable )
	{
		$("SPAN#dft_connectors_enabled INPUT[name='dft_connectors_enabled'][value='1']").click();
		ddl_add__try_to_toggle_visibility_of_uniqueness("off");
		toggle_form_container( "dft_max_nr_of_items" , "on" );
	}
	else
	{
		$("SPAN#dft_connectors_enabled INPUT[name='dft_connectors_enabled'][value='0']").click();
		ddl_add__try_to_toggle_visibility_of_uniqueness("on");
		toggle_form_container( "dft_max_nr_of_items" , "off" );
	}
	return;
}

function ddl_add__try_to_toggle_visibility_of_uniqueness ( turn )
{
	if ( (turn == 'on') || (parseInt( turn ) == 1) )
	{
		if ( $("SELECT[name='dft_type']").val() == 'alphanumeric' )
		{
			switch ( $("SELECT[name='dft_subtype__alphanumeric']").val() )
			{
				case 'dropdown':
				case 'multiple':
					toggle_form_container( "dft_is_unique" , "off" );
					return;
					break;
			}
		}
		else if ( $("SELECT[name='dft_type']").val() == 'file' )
		{
			toggle_form_container( "dft_is_unique" , "off" );
			return;
		}
		toggle_form_container( "dft_is_unique" , "on" );
		return;
	}
	else if ( (turn == 'off') || (parseInt( turn ) == 0) )
	{
		toggle_form_container( "dft_is_unique" , "off" );
	}
	return;
}

function create_subroutine__request_criteria__add_sorting ( obj )
{
	htmlToCopy  = $("SPAN.create_subroutine__sort_by:eq(0)").html();
	htmlToCopy += "<a href=\"javascript: void(0);\" onclick=\"javascript:create_subroutine__request_criteria__remove_sorting(this);\" class=\"create_subroutine__fetch_criteria__remove_sorting_button\" title=\"remove\"></a>";

	$("<SPAN>")
		.addClass("create_subroutine__sort_by")
		.html( htmlToCopy )
		.insertAfter( $(obj).siblings("SPAN.create_subroutine__sort_by:last") )
		.show();
	nr_of_visible_rules = $(obj).siblings("SPAN.create_subroutine__sort_by").length;
	// No need to touch the first one
	for ( i = 1; i < nr_of_visible_rules; i++ )
	{
		$(obj).siblings("SPAN.create_subroutine__sort_by:eq(" + i + ") SELECT:eq(0)")
			.attr("name","s_fetch_criteria[sort_by][" + i + "][field_name]");
		$(obj).siblings("SPAN.create_subroutine__sort_by:eq(" + i + ") SELECT:eq(1)")
			.attr("name","s_fetch_criteria[sort_by][" + i + "][dir]");
	}
}

function create_subroutine__request_criteria__remove_sorting ( obj )
{
	siblings = $(obj).parent().siblings();
	$(obj).parent().remove();
	nr_of_visible_rules = siblings.filter("SPAN.create_subroutine__sort_by").length;
	// No need to touch the first one
	for ( i = 1; i < nr_of_visible_rules; i++ )
	{
		siblings.filter("SPAN.create_subroutine__sort_by:eq(" + i + ") SELECT:eq(0)")
			.attr("name","s_fetch_criteria[sort_by][" + i + "][field_name]");
		siblings.filter("SPAN.create_subroutine__sort_by:eq(" + i + ") SELECT:eq(1)")
			.attr("name","s_fetch_criteria[sort_by][" + i + "][dir]");
	}
}

function create_subroutine__request_criteria__add_policy ( obj )
{
	htmlToCopy  = $("SPAN.create_subroutine__fetch_criteria__policies:eq(0)").html();
	htmlToCopy += "<a href=\"javascript: void(0);\" onclick=\"javascript:create_subroutine__request_criteria__remove_policy(this);\" class=\"create_subroutine__fetch_criteria__remove_policy_button\" title=\"remove\"></a>";

	$("<SPAN>")
		.addClass("create_subroutine__fetch_criteria__policies")
		.html( htmlToCopy )
		.insertAfter( $(obj).siblings("SPAN.create_subroutine__fetch_criteria__policies:last") )
		.show();
	$("SPAN.create_subroutine__fetch_criteria__policies:last TEXTAREA:eq(0)").val("1");
	nr_of_visible_policies = $(obj).siblings("SPAN.create_subroutine__fetch_criteria__policies").length;
	// No need to touch the first one
	for ( i = 1; i < nr_of_visible_policies; i++ )
	{
		$(obj).siblings("SPAN.create_subroutine__fetch_criteria__policies:eq(" + i + ") TEXTAREA:eq(0)")
			.attr("name","s_fetch_criteria[policies][" + i + "]")
			.attr("id","create_subroutine__fetch_criteria__policies__" + i);
		/*
		editAreaLoader.init({
			id:"create_subroutine__fetch_criteria__policies__" + i,
			syntax:"sql",
			font_family: "verdana, monospace",
			font_size:"9",
			allow_toggle:false,
			allow_resize:"no",
			start_highlight:true,
			toolbar:"search,go_to_line,fullscreen,|,undo,redo,|,select_font,|,highlight,word_wrap",
			begin_toolbar:"",
			end_toolbar:"",
			min_width:"520",
			min_height:"100",
			word_wrap:true,
			language:"en",
			show_line_colors:true,
			browsers:"known"
		});
		*/
		$(obj).siblings("SPAN.create_subroutine__fetch_criteria__policies:eq(" + i + ") I:eq(0)")
			.html("Group Policy " + ( i + 1 ) );
	}
}

function create_subroutine__request_criteria__remove_policy ( obj )
{
	siblings = $(obj).parent().siblings();
	$(obj).parent().remove();
	nr_of_visible_policies = siblings.filter("SPAN.create_subroutine__fetch_criteria__policies").length;
	// No need to touch the first one
	for ( i = 1; i < nr_of_visible_policies; i++ )
	{
		siblings.filter("SPAN.create_subroutine__fetch_criteria__policies:eq(" + i + ") TEXTAREA:eq(0)")
			.attr("name","s_fetch_criteria[policies][" + i + "]");
		siblings.filter("SPAN.create_subroutine__fetch_criteria__policies:eq(" + i + ") I:eq(0)")
			.html("Group Policy " + ( i + 1 ) );
	}
}

function create_subroutine__request_criteria__add_query ( obj )
{
	htmlToCopy = $(obj).parent().siblings("SPAN.extra:first").html();
	$("<SPAN>")
		.addClass("extra")
		.html(htmlToCopy)
		.insertAfter( $(obj).parent().siblings("SPAN.extra:last") )
		.show();
	nr_of_visible_extras = $(obj).parent().siblings("SPAN.extra:visible").length;
	for ( i = 1; i <= nr_of_visible_extras; i++ )
	{
		$(obj).parent().siblings("SPAN.extra:eq(" + i + "):visible SELECT:eq(0)").attr("name","s_fetch_criteria[rules][" + i + "][field_name]");
		$(obj).parent().siblings("SPAN.extra:eq(" + i + "):visible SELECT:eq(1)").attr("name","s_fetch_criteria[rules][" + i + "][math_operator]");
		$(obj).parent().siblings("SPAN.extra:eq(" + i + "):visible SELECT:eq(2)").attr("name","s_fetch_criteria[rules][" + i + "][type_of_expr_in_value]");
		$(obj).parent().siblings("SPAN.extra:eq(" + i + "):visible INPUT:eq(0)").attr("name","s_fetch_criteria[rules][" + i + "][value]");

		$(obj).parent().siblings("SPAN.extra:eq(" + i + "):visible SPAN:last").html("Shortcut: <i>" + ( i + 1 ) + "</i>");
	}
}

function create_subroutine__request_criteria__remove_query ( obj )
{
	siblings = $(obj).parent().siblings();
	$(obj).parent().remove();
	nr_of_visible_extras = siblings.filter("SPAN.extra:visible").length;
	for ( i = 1; i <= nr_of_visible_extras; i++ )
	{
		siblings.filter("SPAN.extra:eq(" + i + "):visible SELECT:eq(0)").attr("name","s_fetch_criteria[rules][" + i + "][field_name]");
		siblings.filter("SPAN.extra:eq(" + i + "):visible SELECT:eq(1)").attr("name","s_fetch_criteria[rules][" + i + "][math_operator]");
		siblings.filter("SPAN.extra:eq(" + i + "):visible SELECT:eq(2)").attr("name","s_fetch_criteria[rules][" + i + "][type_of_expr_in_value]");
		siblings.filter("SPAN.extra:eq(" + i + "):visible INPUT:eq(0)").attr("name","s_fetch_criteria[rules][" + i + "][value]");

		siblings.filter("SPAN.extra:eq(" + i + "):visible SPAN:last").html("Shortcut: <i>" + ( i + 1 ) + "</i>");
	}
}

var modules__connector_unit__ddl__do_sort = $(function() {
	$("#tables__components__ddl__list TBODY.js__sortable").bind("sortupdate",function(e,tr){
		var $_current_console = $("#system_console");
		var $_post_serialized_for__ddl_sorted__with_action = $(this).sortable("serialize") + '&do=ddl_alter__sort';
		$("#system_console").html("Processing... Please wait!").removeClass("error success").addClass("notice");
		scrollToConsole();
		$.ajax({
			type:     "POST",
			url:      window.location.href,
			dataType: "json",
			data:     $_post_serialized_for__ddl_sorted__with_action,
			cache:    false,
			timeout:  15000,
			error:    function( xhr, text_status, error )
			{
				$_current_console.html("Unspecified error occured! Possible causes are <i>connection problems</i> or <i>invalid response from server</i>.").removeClass("notice success").addClass("error").show();
				return 0;
			},
			success:  function( data )
			{
				if ( data.responseCode == '1' )
				{
					$_current_console.html( data.responseMessage ).removeClass("notice error").addClass("success").show();
					setTimeout( "window.location = window.location.href", 2000 );
					return 1;
				}
				else
				{
					$_current_console.html( data.faultMessage ).removeClass("notice success").addClass("error").show();
					return 0;
				}
			}
		});
	});
});

$( document ).ready(
	function() {
		$.modules__connector_unit__ddl__do_sort();

		// Fetch DDL list for a module we are linking with

		$("FORM#forms__components__ddl__alter_add SELECT#register__dft_links_with").change(
			function(event){
				if ( $(this).val() == '' )
				{
					toggle_form_container( "dft_links_with__fields_to_fetch", "off" );
					return;
				}

				toggle_form_container( "dft_links_with__fields_to_fetch", "on" );
				$("SPAN#dft_links_with__field_list SPAN.input").html("Loading ...").show();
				$("SPAN#dft_links_with__field_list SELECT#register__dft_links_with__fields_to_fetch").hide();

				$.ajax({
					type: "GET",
					url: "{{$MODULE_URL}}/components/viewmodule-" + $(this).val() + "?output=json",
					dataType: "json",
					cache: true,
					timeout: 15000,
					error: function( xhr, text_status, error ){
						$("SAMP#system_console").html("ERROR! FIELD list could not be fetched!").removeClass("notice success").addClass("error");
						return 0;
					},
					success: function( data )
					{
						if ( data && data.me.m_data_definition_count )
						{
							$("SPAN#dft_links_with__fields_to_fetch SPAN.input").hide();
							$("SPAN#dft_links_with__fields_to_fetch SELECT#register__dft_links_with__fields_to_fetch").show();

							// Delete previous OPTIONs
							$_nr_of_options = $("SELECT#register__dft_links_with__fields_to_fetch").children().length;
							for ( i = 0; i < $_nr_of_options; i++ )
							{
								// Weird huh? :) JScript shifts all keys upwards, once something from top of the stack is deleted.
								$( "SELECT#register__dft_links_with__fields_to_fetch OPTION:eq(0)" ).remove();
							}

							// Build the list of new OPTIONs
							$_list_of_option_keys   = [];
							$_list_of_option_values = [];
							for ( var m in data.me.m_data_definition )
							{
								if ( data.me.m_data_definition[m].type == 'link' )
								{
									continue;
								}
								else if ( data.me.m_data_definition[m].connectors_enabled )
								{
									for ( var c in data.me.m_data_definition[m].c_data_definition )
									{
										$_list_of_option_keys.push( data.me.m_data_definition[m].name + "." + data.me.m_data_definition[m].c_data_definition[c].name );
										$_list_of_option_values.push( data.me.m_data_definition[m].c_data_definition[c].label );
									}
								}
								else
								{
									$_list_of_option_keys.push( data.me.m_data_definition[m].name );
									$_list_of_option_values.push( data.me.m_data_definition[m].label );
								}
							}
							$_nr_of_items_in_options = $_list_of_option_keys.length;

							// Apply OPTIONs
							for ( i = 0; i < $_nr_of_items_in_options; i++ )
							{
								$("SELECT#register__dft_links_with__fields_to_fetch")
									.append("<option value=\"" + $_list_of_option_keys[i] + "\">" + $_list_of_option_values[i] + " [" + $_list_of_option_keys[i] + "]</option>");
							}

							return 1;
						}

						$("SAMP#system_console").html( "No Data-fields could be found in our records!" ).removeClass("notice success").addClass("error").show();
						$("SPAN#dft_links_with__fields_to_fetch SPAN.input").html("Error! Check console for details...").show();
						$("SPAN#dft_links_with__fields_to_fetch SELECT#register__dft_links_with__fields_to_fetch").hide();

						return 0;
					}
				});

			}
		);

		// Open "DDL-Create" form

		$("FORM#forms__components__ddl__list INPUT[type='button']:eq(0)").click(
			function(event){
				// Resetting form
				$("FORM#forms__components__ddl__alter_add INPUT[type='reset']").click();

				// Opening form
				if ( $("LI#components__ddl__alter_add").is(":hidden") )
				{
					$("LI#sec_content_modules__rename").slideUp();
					$("LI#components__sr__create").slideUp();

					$("LI#components__ddl__alter_add").slideDown(500);
				}

				scrollToConsole();
			}
		);

		// Reset "DDL-Create" form

		$("FORM#forms__components__ddl__alter_add INPUT[type='reset']").click(
			function(event){
				// Resetting errors
				$("FORM#forms__components__ddl__alter_add *").removeClass("error");
				// Consoles
				closeConsoles();
				$_current_console = $("FORM#forms__components__ddl__alter_add .system_console");
				$_current_console.html("Required fields (in red) must be filled-in!").removeClass("error success").addClass("notice").show();

				ddl_add__select_dft_subtype("alphanumeric");
			}
		);

		// Close "DDL-Create" form

		$("FORM#forms__components__ddl__alter_add INPUT[type='button']:eq(0)").click(
			function(event){
				closeConsoles();
				$("FORM#forms__components__ddl__alter_add INPUT[type='text']").val("");
				$("LI#components__ddl__alter_add").slideUp(750);
			}
		);

		// Submit "DDL-Create" form

		$("FORM#forms__components__ddl__alter_add").submit(
			function(event){
				event.preventDefault();
				$("FORM#forms__components__ddl__alter_add *").removeClass("error");
				// Consoles
				closeConsoles();
				$_current_console = $("FORM#forms__components__ddl__alter_add .system_console");
				$_current_console.html("Processing... Please wait!").removeClass("error success").addClass("notice").show();
				// Action
				$_url = window.location.href + "?output=json";
				$.ajax({
					type:"POST",
					url:$_url,
					dataType:"json",
					data:$("FORM#forms__components__ddl__alter_add").serialize(),
					cache:false,
					timeout:15000,
					error:function( xhr, text_status, error )
					{
						$_current_console.html("Unspecified error occured! Possible causes are <i>connection problems</i> or <i>invalid response from server</i>.").removeClass("notice success").addClass("error").show();
						return 0;
					},
					success:function( data )
					{
						if ( data.responseCode == '1' )
						{
							$_current_console.html( data.responseMessage ).removeClass("notice error").addClass("success").show();
							setTimeout( "window.location = window.location.href", 2000 );
							return 1;
						}
						else
						{
							faultMessageList = [];
							for ( i = 0 ; i < data.length ; i++ )
							{
								switch ( data[ i ].faultCode )
								{
									case 701:
										$("FORM#forms__components__ddl__alter_add INPUT[name='dft_name']").addClass("error");
										break;
									case 702:
										$("FORM#forms__components__ddl__alter_add INPUT[name='dft_label']").addClass("error");
										break;
									case 703:
										$("FORM#forms__components__ddl__alter_add SELECT[name='dft_type']").addClass("error");
										break;
									case 704:
										$("FORM#forms__components__ddl__alter_add TEXTAREA[name='dft_default_options']").addClass("error");
										break;
									case 708:
										if ( data[ i ].faultExtra )
										{
											$("FORM#forms__components__ddl__alter_add INPUT[name='dft_maxlength__alphanumeric__integer']").val( data[ i ].faultExtra );
										}
										$("FORM#forms__components__ddl__alter_add INPUT[name='dft_maxlength__alphanumeric__integer']").addClass("error");
										break;
									case 709:
										if ( data[ i ].faultExtra )
										{
											$("FORM#forms__components__ddl__alter_add INPUT[name='dft_maxlength__alphanumeric__decimal']").val( data[ i ].faultExtra );
										}
										$("FORM#forms__components__ddl__alter_add INPUT[name='dft_maxlength__alphanumeric__decimal']").addClass("error");
										break;
									case 710:
										$("FORM#forms__components__ddl__alter_add INPUT[name='dft_maxlength__file']").addClass("error");
										break;
									case 720:
										$("FORM#forms__components__ddl__alter_add INPUT[name='dft_default_value']").addClass("error");
										break;
									case 721:
										$("FORM#forms__components__ddl__alter_add SELECT[name^='dft_allowed_filetypes']").addClass("error");
										break;
								}
								faultMessageList.push( "<li>" + data[ i ].faultMessage + "</li>" );
							}
							faultMessageParsed = "One or more error(s) occured! Correct them and resubmit the form to continue:<ul>" + faultMessageList.join("") + "</ul>";
							$_current_console.html( faultMessageParsed ).removeClass("notice success").addClass("error").show();
							return 0;
						}
					}
				});
			}
		);

		// "DDL - Drop"

		$("FORM#forms__components__ddl__list INPUT[type='button']:eq(1)").click(
			function(event){
				if ( confirm( "Would you like to BACK-UP the structure and the data of the dropped field?\n\nSTRONGLY RECOMMENDED!!!\n\nIMPORTANT NOTICE: 'Required' fields will have a data inconsistency as you add more content later on. Future restoration of this field from backups might become problematic!!!" ) )
				{
					$("FORM#forms__components__ddl__list INPUT[name='do_backup_dropped_field']").val("1");
				}
				else
				{
					$("FORM#forms__components__ddl__list INPUT[name='do_backup_dropped_field']").val("0");
				}
				if ( prompt( "You are about to DROP a data-field!\n\nPlease type 'yEs' (case-sensitive) into the field below to confirm the operation:\n\nACTION IS IRREVERSIBLE!!!" ) != 'yEs' )
				{
					return 0;
				}
				m_unique_id = $("FORM#forms__components__ddl__list INPUT[name='m_unique_id']").val();
				// Consoles
				closeConsoles();
				$_current_console = $("FORM#forms__components__ddl__list .system_console");
				$_current_console.html("Processing... Please wait!").removeClass("error success").addClass("notice").show();
				// Action
				$("FORM#forms__components__ddl__list INPUT[name='do']").val("ddl_alter__drop");
				$_url = window.location.href + "?output=json";
				$.ajax({
					type:"POST",
					url:$_url,
					dataType:"json",
					data:$("FORM#forms__components__ddl__list").serialize(),
					cache:false,
					timeout:15000,
					error:function( xhr, text_status, error )
					{
						$_current_console.html("Unspecified error occured! Possible causes are <i>connection problems</i> or <i>invalid response from server</i>.").removeClass("notice success").addClass("error").show();
						return 0;
					},
					success:function( data )
					{
						if ( data.responseCode == '1' )
						{
							$_current_console.html( data.responseMessage ).removeClass("notice error").addClass("success").show();
							setTimeout( "window.location = window.location.href", 2000 );
							return 1;
						}
						else
						{
							$_current_console.html( data.faultMessage ).removeClass("notice success").addClass("error").show();
							return 0;
						}
					}
				});
			}
		);

		// "DDL - Restore Backup"

		$("FORM#forms__components__ddl__list_bak INPUT[type='button']:eq(0)").click(
			function(event){
				if ( prompt( "You are about to RESTORE a data-field!\n\nPlease type 'yEs' (case-sensitive) into the field below to confirm the operation:\n\nACTION IS IRREVERSIBLE!!!" ) != 'yEs' )
				{
					return 0;
				}
				m_unique_id = $("FORM#forms__components__ddl__list_bak INPUT[name='m_unique_id']").val();
				// Consoles
				closeConsoles();
				$_current_console = $("FORM#forms__components__ddl__list_bak .system_console");
				$_current_console.html("Processing... Please wait!").removeClass("error success").addClass("notice").show();
				// Action
				$("FORM#forms__components__ddl__list_bak INPUT[name='do']").val("ddl_alter__restore_backup");
				$_url = window.location.href + "?output=json";
				$.ajax({
					type:"POST",
					url:$_url,
					dataType:"json",
					data:$("FORM#forms__components__ddl__list_bak").serialize(),
					cache:false,
					timeout:15000,
					error:function( xhr, text_status, error )
					{
						$_current_console.html("Unspecified error occured! Possible causes are <i>connection problems</i> or <i>invalid response from server</i>.").removeClass("notice success").addClass("error").show();
						return 0;
					},
					success:function( data )
					{
						if ( data.responseCode == '1' )
						{
							$_current_console.html( data.responseMessage ).removeClass("notice error").addClass("success").show();
							setTimeout( "window.location = window.location.href", 2000 );
							return 1;
						}
						else
						{
							$_current_console.html( data.faultMessage ).removeClass("notice success").addClass("error").show();
							return 0;
						}
					}
				});
			}
		);

		// "DDL - Purge Backup"

		$("FORM#forms__components__ddl__list_bak INPUT[type='button']:eq(1)").click(
			function(event){
				if ( prompt( "Please type 'yEs' (case-sensitive) into the field below to confirm the operation:\n\nACTION IS IRREVERSIBLE!!!" ) != 'yEs' )
				{
					return 0;
				}
				m_unique_id = $("FORM#forms__components__ddl__list_bak INPUT[name='m_unique_id']").val();
				// Consoles
				closeConsoles();
				$_current_console = $("FORM#forms__components__ddl__list_bak .system_console");
				$_current_console.html("Processing... Please wait!").removeClass("error success").addClass("notice").show();
				// Action
				$("FORM#forms__components__ddl__list_bak INPUT[name='do']").val("ddl_alter__purge_backup");
				$_url = window.location.href + "?output=json";
				$.ajax({
					type:"POST",
					url:$_url,
					dataType:"json",
					data:$("FORM#forms__components__ddl__list_bak").serialize(),
					cache:false,
					timeout:15000,
					error:function( xhr, text_status, error )
					{
						$_current_console.html("Unspecified error occured! Possible causes are <i>connection problems</i> or <i>invalid response from server</i>.").removeClass("notice success").addClass("error").show();
						return 0;
					},
					success:function( data )
					{
						if ( data.responseCode == '1' )
						{
							$_current_console.html( data.responseMessage ).removeClass("notice error").addClass("success");
							setTimeout( "window.location = window.location.href", 2000 );
							return 1;
						}
						else
						{
							$_current_console.html( data.faultMessage ).removeClass("notice success").addClass("error");
							return 0;
						}
					}
				});
			}
		);

		// "DDL - Define a Title"

		$("FORM#forms__components__ddl__list INPUT[type='button']:eq(2)").click(
			function(event){
				// Consoles
				closeConsoles();
				$_current_console = $("TABLE#tables__components__ddl__list .system_console");
				$_current_console.html("Processing... Please wait!").removeClass("error success").addClass("notice").show();
				// Action
				$("FORM#forms__components__ddl__list INPUT[name='do']").val("ddl_alter__set_title_column");
				$_url = window.location.href + "?output=json";
				$.ajax({
					type:"POST",
					url:$_url,
					dataType:"json",
					data:$("FORM#forms__components__ddl__list").serialize(),
					cache:false,
					timeout:15000,
					error:function( xhr, text_status, error )
					{
						$_current_console.html("Unspecified error occured! Possible causes are <i>connection problems</i> or <i>invalid response from server</i>.").removeClass("notice success").addClass("error").show();
						return 0;
					},
					success:function( data )
					{
						if ( data.responseCode == '1' )
						{
							$_current_console.html( data.responseMessage ).removeClass("notice error").addClass("success");
							setTimeout( "window.location = window.location.href", 2000 );
							return 1;
						}
						else
						{
							$_current_console.html( data.faultMessage ).removeClass("notice success").addClass("error");
							return 0;
						}
					}
				});
			}
		);

		// Open "Create Subroutine" form

		$("FORM#forms__components__sr__list INPUT[type='button']:eq(0)").click(
			function(event){
				// Resetting errors
				$("FORM#forms__components__sr__create *").removeClass("error");
				// Hiding other forms
				$("LI#components__ddl__alter_add").hide();
				$("LI#sec_content_modules__rename").hide();
				// Resetting form
				$("FORM#forms__components__sr__create INPUT[type='reset']").click();
				// Opening form
				if ( $("LI#components__sr__create").is(":hidden") )
				{
					$("LI#components__sr__create").slideDown(750);
				}
				closeConsoles();
				$("SAMP#system_console").html( "Required fields (in red) must be filled-in!" ).removeClass("error success").addClass("notice").show();
				scrollToConsole();
			}
		);

		// Close "Create Subroutine" form

		$("FORM#forms__components__sr__create INPUT[type='button']:eq(0)").click(
			function(event){
				$("FORM#forms__components__sr__create INPUT[type='reset']").click();
				closeConsoles();
				$("LI#components__sr__create").slideUp(1000);
			}
		);

		// Submit "Create Subroutine" form

		$("FORM#forms__components__sr__create").submit(
			function(event){
				event.preventDefault();
				// IF limit==1 , we don't need sorting
				if ( $("INPUT#create_subroutine__fetch_criteria__limit").val() == '1' )
				{
					if ( $("SPAN.create_subroutine__sort_by:eq(0)").is(":visible") )
					{
						$("SPAN.create_subroutine__sort_by:eq(0)").hide();
					}
					$("SPAN.create_subroutine__sort_by:visible").remove();
					$("A#create_subroutine__fetch_criteria__add_sorting_button").hide();
					$("SELECT#create_subroutine__fetch_criteria__do_perform_sorting").val("0");
				}
				// Resetting errors
				$("FORM#forms__components__sr__create *").removeClass("error");
				// Consoles
				closeConsoles();
				$_current_console = $("FORM#forms__components__sr__create .system_console");
				$_current_console.html("Processing... Please wait!").removeClass("error success").addClass("notice").show();
				// Action
				$("FORM#forms__components__sr__create INPUT[name='do']").val("create_subroutine");
				$_url = window.location.href + "?output=json";
				$.ajax({
					type:"POST",
					url:$_url,
					dataType:"json",
					data:$("FORM#forms__components__sr__create").serialize() + "&" + $("FORM#forms__components__sr__create INPUT[name='s_ddl_information']").val(),
					cache:false,
					timeout:15000,
					error:function( xhr, text_status, error )
					{
						$_current_console.html("Unspecified error occured! Possible causes are <i>connection problems</i> or <i>invalid response from server</i>.").removeClass("notice success").addClass("error").show();
						return 0;
					},
					success:function( data )
					{
						if ( data.responseCode == '1' )
						{
							$_current_console.html( data.responseMessage ).removeClass("notice error").addClass("success").show();
							setTimeout( "window.location = window.location.href", 2000 );
							return 1;
						}
						else
						{
							faultMessageList = [];
							for ( i = 0 ; i < data.length ; i++ )
							{
								switch ( data[ i ].faultCode )
								{
									case 700:
										$("FORM#forms__components__sr__create SELECT[name='s_data_definition[]']").addClass("error");
										break;
									case 701:
										$("FORM#forms__components__sr__create INPUT[name='s_name']").addClass("error");
										break;
									case 702:
										$("FORM#forms__components__sr__create INPUT[name='s_pathinfo_uri_schema']").addClass("error");
										break;
									case 704:
										$("FORM#forms__components__sr__create TEXTAREA[name='s_fetch_criteria[policies][" + data[ i ].faultExtra + "]']").addClass("error");
										break;
									case 709:
										$("FORM#forms__components__sr__create SELECT[name='s_fetch_criteria[rules][" + data[ i ].faultExtra + "][type_of_expr_in_value]']").addClass("error");
										break;
									case 705:
										$("FORM#forms__components__sr__create INPUT[name='s_fetch_criteria[rules][" + data[ i ].faultExtra + "][value]']").addClass("error");
										break;
									case 706:
										$("FORM#forms__components__sr__create INPUT[name='s_fetch_criteria[limit]']").addClass("error");
										break;
									case 707:
										$("FORM#forms__components__sr__create INPUT[name='s_fetch_criteria[pagination]']").addClass("error");
										break;
									case 708:
										$("FORM#forms__components__sr__create SELECT[name='s_fetch_criteria[sort_by][" + data[ i ].faultExtra + "][field_name]']").addClass("error");
										break;
								}
								faultMessageList.push( "<li>" + data[ i ].faultMessage + "</li>" );
							}
							faultMessageParsed = "One or more error(s) occured! Correct them and resubmit the form to continue:<ul>" + faultMessageList.join("") + "</ul>";
							$_current_console.html( faultMessageParsed ).removeClass("notice success").addClass("error").show();
							return 0;
						}
					}
				});
			}
		);

		// Reset "Create Subroutine" form

		$("FORM#forms__components__sr__create INPUT[type='reset']").click(
			function(event){
				// Hiding stuff that should to be hidden normally
				$("SPAN.create_subroutine__fetch_criteria__policies:eq(0)").hide();
				$("A#create_subroutine__fetch_criteria__add_policy_button").hide();
				$("SPAN.pre_extra").hide();
				$("SPAN.create_subroutine__sort_by:eq(0)").hide();
				$("A#create_subroutine__fetch_criteria__add_sorting_button").hide();
				// Removing excessive stuff
				$("SPAN.create_subroutine__fetch_criteria__policies:eq(0)").siblings("SPAN.create_subroutine__fetch_criteria__policies").remove();
				$("SPAN.extra:eq(0)").siblings("SPAN.extra").remove();
				$("SPAN.create_subroutine__sort_by:eq(0)").siblings("SPAN.create_subroutine__sort_by").remove();
				// Resetting errors
				$("FORM#forms__components__sr__create *").removeClass("error");
				// Disabling service mode
				$("FORM#forms__components__sr__create SELECT[name='s_service_mode']").attr("disabled","disabled");
				// Consoles
				closeConsoles();
				$_current_console = $("FORM#forms__components__sr__create .system_console");
				$_current_console.html("Required fields (in red) must be filled-in!").removeClass("error success").addClass("notice").show();
			}
		);

		$("SELECT#create_subroutine__fetch_criteria__all_or_selected").change(
			function(event){
				if ( $(this).val() == 'all' )
				{
					$("SPAN.extra:visible").remove();
					$("SPAN.create_subroutine__fetch_criteria__policies:eq(0)").hide();
					$("SPAN.create_subroutine__fetch_criteria__policies:visible").remove();
					$("SPAN.pre_extra").hide();
					$("A#create_subroutine__fetch_criteria__add_policy_button").hide();
				}
				else
				{
					$("SPAN.create_subroutine__fetch_criteria__policies:eq(0)").show();
					$("SPAN.pre_extra").show();
					$("A#create_subroutine__fetch_criteria__add_policy_button").show();
				}
				return 1;
			}
		);

		$("SELECT#create_subroutine__fetch_criteria__do_perform_sorting").change(
			function(event){
				if ( $(this).val() == '0' )
				{
					$("SPAN.create_subroutine__sort_by:eq(0)").hide();
					$("SPAN.create_subroutine__sort_by:visible").remove();
					$("A#create_subroutine__fetch_criteria__add_sorting_button").hide();
				}
				else
				{
					$("SPAN.create_subroutine__sort_by:eq(0)").show();
					$("A#create_subroutine__fetch_criteria__add_sorting_button").show();
				}
				return 1;
			}
		);

		// "Subroutines - Removal"

		$("FORM#forms__components__sr__list INPUT[type='button']:eq(1)").click(
			function(event){
				if ( prompt( "Please type 'yEs' (case-sensitive) into the field below to confirm the operation:\n\nACTION IS IRREVERSIBLE!!!" ) != 'yEs' )
				{
					return 0;
				}
				// Consoles
				closeConsoles();
				$_current_console = $("TABLE#tables__components__sr__list TFOOT .system_console");
				$_current_console.html("Processing... Please wait!").removeClass("error success").addClass("notice").show();
				// Action
				$("FORM#forms__components__sr__list INPUT[name='do']").val("remove_subroutine");
				$_url = window.location.href + "?output=json";
				$.ajax({
					type:"POST",
					url:$_url,
					dataType:"json",
					data:$("FORM#forms__components__sr__list").serialize(),
					cache:false,
					timeout:15000,
					error:function( xhr, text_status, error )
					{
						$_current_console.html("Unspecified error occured! Possible causes are <i>connection problems</i> or <i>invalid response from server</i>.").removeClass("notice success").addClass("error").show();
						return 0;
					},
					success:function( data )
					{
						if ( data.responseCode == '1' )
						{
							$_current_console.html( data.responseMessage ).removeClass("notice error").addClass("success").show();
							setTimeout( "window.location = window.location.href", 2000 );
							return 1;
						}
						else
						{
							$_current_console.html( data.faultMessage ).removeClass("notice success").addClass("error").show();
							return 0;
						}
					}
				});
			}
		);

		// "Link to Connector-Unit" button execution

		$("FORM#forms__components__ddl__list INPUT[type='button']:eq(3)").click(
			function ( event )
			{
				// Consoles
				closeConsoles();
				$_current_console = $("TABLE#tables__components__ddl__list .system_console");
				$_current_console.html("Processing... Please wait!").removeClass("error success").addClass("notice").show();
				// Action
				$("FORM#forms__components__ddl__list INPUT[name='do']").val("register__module__connector_unit");
				$_url = window.location.href + "?output=json";
				$.ajax({
					type:"POST",
					url:$_url,
					dataType:"json",
					data:$("FORM#forms__components__ddl__list").serialize(),
					cache:false,
					timeout:15000,
					error:function( xhr, text_status, error )
					{
						$_current_console.html("Unspecified error occured! Possible causes are <i>connection problems</i> or <i>invalid response from server</i>.").removeClass("notice success").addClass("error").show();
						return 0;
					},
					success:function( data )
					{
						if ( data.responseCode == '1' )
						{
							$_current_console.html( data.responseMessage ).removeClass("notice error").addClass("success").show();
							setTimeout( "window.location = window.location.href", 2000 );
							return 1;
						}
						else
						{
							$_current_console.html( data.faultMessage ).removeClass("notice success").addClass("error").show();
							return 0;
						}
					}
				});
			}
		);

		// "DDL Management for Connector" action

		$("FORM#forms__components__ddl__list INPUT[type='button']:eq(4)").click(
			function(event){
				c_name = $("FORM#forms__components__ddl__list INPUT[name='ddl_checklist[]']:checked").val();
				m_unique_id = $("FORM#forms__components__ddl__list INPUT[name='m_unique_id']").val();
				if ( ! c_name )
				{
					$("SAMP#system_console").html("No connector-enabled data-field was selected! Please select one...").removeClass("notice success").addClass("error").slideDown();
					return 0;
				}
				m_unique_id_clean = m_unique_id.replace( /[^a-z0-9]/gi , "" ).toLowerCase();
				window.location = "{{$MODULE_URL}}/components/viewconnector-" + m_unique_id_clean + "-" + c_name;
			}
		);

		$(document).unbind("mousemove");

		$("LI#components__sr__list INPUT[type='radio']").mousedown
		(
			function(event)
			{
				var obj = $(this);
				$(document).bind
				(
					"mousemove",
					{'o':obj},
					function(event)
					{
						// var deltaY = event.pageY - $(this).offset().top;
						// var deltaX = event.pageX - $(this).offset().left;

						if ( (event.pageY > 0) && (event.pageX > 0) && (event.pageX < $(document).width()) && (event.pageY < $(document).height()) )
						{
							$("LI#components__sr__list")
								.css( "z-index","999999" )
								.css( "position","absolute" )
								.css( "top",event.pageY-event.data.o.offset().top - event.data.o.height()/2 + $("LI#components__sr__list").offset().top )
								.css( "left",event.pageX-event.data.o.offset().left - event.data.o.width()/2 + $("LI#components__sr__list").offset().left );
						}
					}
				);
			}
		);

		$(document).mouseup
		(
			function (event)
			{
				$(document).unbind("mousemove");
			}
		);



		// BLOODY WORK-AROUNDS

		$("#tables__components__ddl__list COLGROUP").remove();



	}
);