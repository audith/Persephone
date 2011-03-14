/**
 * Local Javascript object
 *
 * @returns object Object handler
 */
function /* Class */ Acp_ComponentsViewmodule ()
{
	return {
		constructor : Acp_ComponentsViewmodule,
		obj : null
	};
};

/* Extending with Persephone */
extend(Acp_ComponentsViewmodule, Persephone);

/* Instantiating Class handler */
var acp__components_viewmodule = Acp_ComponentsViewmodule.singleton();

/**
 * Fetches Mime-list to be populated into 'allowed_filetypes' field
 *
 * @param string
 *            Data-subtype
 * @return mixed NULL if no data, FALSE on error, TRUE otherwise
 */
Acp_ComponentsViewmodule.prototype.ddl__register__file__allowed_filetypes__do_fetch = function ( subtype )
{
	if ( subtype == '' || subtype == undefined || subtype == false )
	{
		return false;
	}
	var _response = jQuery.ajax({
		type : "GET",
		url : window.location.href + "?do=ddl_alter__add__mimelist__do_fetch",
		data : "mimetype=" + subtype,
		async : false
	});
	return jQuery.parseJSON(_response.responseText);
};

Acp_ComponentsViewmodule.prototype.registry = {
	'alphanumeric' : {
		'_label' : "Alphanumeric",
		'_subtypes' : {
			'string' : {
				'_label' : "General String",
				'options' : {
					'maxlength' : "",
					'default_value' : "",
					'@connector_enabled' : false,
					'is_html_allowed' : false,
					'is_required' : false,
					'is_unique' : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Maxlength (in bytes)'
						},
						'tip__for' : {
							'maxlength' : 'Maximum length in bytes. Leave empty to default to &quot;255&quot;. <b>Don&39;t forget to compensate for multi-byte characters!</b>'
						}
					}
				}
			},
			'integer_signed_8' : {
				'_label' : "Numeric: Signed 8-bit-Integer (-128/127)",
				'options' : {
					'maxlength' : "4",
					'default_value' : "",
					'@connector_enabled' : false,
					'is_required' : false,
					'is_unique' : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Max # of Digits'
						},
						'tip__for' : {
							'maxlength' : 'Maximum number of digits, including sign (+/-) character. The value is automatically set and is shown here for your reference.'
						}
					}
				}
			},
			'integer_unsigned_8' : {
				'_label' : "Numeric: Unsigned 8-bit-Integer (0/255)",
				'options' : {
					'maxlength' : "3",
					'default_value' : "",
					'@connector_enabled' : false,
					'is_required' : false,
					'is_unique' : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Max # of Digits'
						},
						'tip__for' : {
							'maxlength' : 'Maximum number of digits, including sign (+/-) character. The value is automatically set and is shown here for your reference.'
						}
					}
				}
			},
			'integer_signed_16' : {
				'_label' : "Numeric: Signed 16-bit-Integer (-32768/32767)",
				'options' : {
					'maxlength' : "6",
					'default_value' : "",
					'@connector_enabled' : false,
					'is_required' : false,
					'is_unique' : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Max # of Digits'
						},
						'tip__for' : {
							'maxlength' : 'Maximum number of digits, including sign (+/-) character. The value is automatically set and is shown here for your reference.'
						}
					}
				}
			},
			'integer_unsigned_16' : {
				'_label' : "Numeric: Unsigned 16-bit-Integer (0/65535)",
				'options' : {
					'maxlength' : "5",
					'default_value' : "",
					'@connector_enabled' : false,
					'is_required' : false,
					'is_unique' : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Max # of Digits'
						},
						'tip__for' : {
							'maxlength' : 'Maximum number of digits, including sign (+/-) character. The value is automatically set and is shown here for your reference.'
						}
					}
				}
			},
			'integer_signed_24' : {
				'_label' : "Numeric: Signed 24-bit-Integer (-8388608/8388607)",
				'options' : {
					'maxlength' : "8",
					'default_value' : "",
					'@connector_enabled' : false,
					'is_required' : false,
					'is_unique' : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Max # of Digits'
						},
						'tip__for' : {
							'maxlength' : 'Maximum number of digits, including sign (+/-) character. The value is automatically set and is shown here for your reference.'
						}
					}
				}
			},
			'integer_unsigned_24' : {
				'_label' : "Numeric: Unsigned 24-bit-Integer (0/16777215)",
				'options' : {
					'maxlength' : "8",
					'default_value' : "",
					'@connector_enabled' : false,
					'is_required' : false,
					'is_unique' : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Max # of Digits'
						},
						'tip__for' : {
							'maxlength' : 'Maximum number of digits, including sign (+/-) character. The value is automatically set and is shown here for your reference.'
						}
					}
				}
			},
			'integer_signed_32' : {
				'_label' : "Numeric: Signed 32-bit-Integer (-2147483648/2147483647)",
				'options' : {
					'maxlength' : "11",
					'default_value' : "",
					'@connector_enabled' : false,
					'is_required' : false,
					'is_unique' : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Max # of Digits'
						},
						'tip__for' : {
							'maxlength' : 'Maximum number of digits, including sign (+/-) character. The value is automatically set and is shown here for your reference.'
						}
					}
				}
			},
			'integer_unsigned_32' : {
				'_label' : "Numeric: Unsigned 32-bit-Integer (0/4294967295)",
				'options' : {
					'maxlength' : "10",
					'default_value' : "",
					'@connector_enabled' : false,
					'is_required' : false,
					'is_unique' : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Max # of Digits'
						},
						'tip__for' : {
							'maxlength' : 'Maximum number of digits, including sign (+/-) character. The value is automatically set and is shown here for your reference.'
						}
					}
				}
			},
			'integer_signed_64' : {
				'_label' : "Numeric: Signed 64-bit-Integer (-9223372036854775808/9223372036854775807)",
				'options' : {
					'maxlength' : "20",
					'default_value' : "",
					'@connector_enabled' : false,
					'is_required' : false,
					'is_unique' : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Max # of Digits'
						},
						'tip__for' : {
							'maxlength' : 'Maximum number of digits, including sign (+/-) character. The value is automatically set and is shown here for your reference.'
						}
					}
				}
			},
			'integer_unsigned_64' : {
				'_label' : "Numeric: Unsigned 64-bit-Integer (0/18446744073709551615)",
				'options' : {
					'maxlength' : "20",
					'default_value' : "",
					'@connector_enabled' : false,
					'is_required' : false,
					'is_unique' : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Max # of Digits'
						},
						'tip__for' : {
							'maxlength' : 'Maximum number of digits, including sign (+/-) character. The value is automatically set and is shown here for your reference.'
						}
					}
				}
			},
			'decimal_signed' : {
				'_label' : "Numeric: Signed Decimal (Fixed-Point)",
				'options' : {
					'maxlength' : "10,0",
					'default_value' : "0",
					'@connector_enabled' : false,
					'is_required' : false,
					'is_unique' : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Precision &amp; Scale'
						},
						'tip__for' : {
							'maxlength' : 'In &quot;<i><b>p,s</b></i>&quot; format; e.g.: &quot;5,2&quot; means total 5 digits, 2 of which follows decimal point, as in &quot;999.99&quot;. Leave empty to default to &quot;10,0&quot;.'
						}
					}
				}
			},
			'decimal_unsigned' : {
				'_label' : "Numeric: Unsigned Decimal (Fixed-Point)",
				'options' : {
					'maxlength' : "10,0",
					'default_value' : "0",
					'@connector_enabled' : false,
					'is_required' : false,
					'is_unique' : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Precision &amp; Scale'
						},
						'tip__for' : {
							'maxlength' : 'In &quot;<i><b>p,s</b></i>&quot; format; e.g.: &quot;5,2&quot; means total 5 digits, 2 of which follows decimal point, as in &quot;999.99&quot;. Leave empty to default to &quot;10,0&quot;.'
						}
					}
				}
			},
			'dropdown' : {
				'_label' : "Single Select (Dropdowns/Radios)",
				'options' : {
					'default_options' : "",
					'default_value' : "",
					'is_required' : true,
					'_additional_params' : {}
				}
			},
			'multiple' : {
				'_label' : "Multiple Select (incl. Checkboxes)",
				'options' : {
					'default_options' : "",
					'default_value' : "",
					'is_required' : true,
					'_additional_params' : {}
				}
			}
		},
		'value__for' : {}
	},
	'file' : {
		'_label' : "File",
		'_subtypes' : {
			'image' : {
				'_label' : "Image files",
				'options' : {
					'allowed_filetypes' : acp__components_viewmodule.ddl__register__file__allowed_filetypes__do_fetch('image'),
					'maxlength' : "",
					'@connector_enabled' : true,
					'is_required' : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Maximum Filesize'
						},
						'tip__for' : {
							'maxlength' : 'Maximum filesize (suffixes supported: &quot;1K&quot;, &quot;2M&quot;). Enter &quot;0&quot; to disable this restriction.'
						}
					}
				}
			},
			'audio' : {
				'_label' : "Audio files",
				'options' : {
					'allowed_filetypes' : acp__components_viewmodule.ddl__register__file__allowed_filetypes__do_fetch('audio'),
					'maxlength' : "",
					'@connector_enabled' : true,
					'is_required' : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Maximum Filesize'
						},
						'tip__for' : {
							'maxlength' : 'Maximum filesize (suffixes supported: &quot;1K&quot;, &quot;2M&quot;). Enter &quot;0&quot; to disable this restriction.'
						}
					}
				}
			},
			'video' : {
				'_label' : "Video files",
				'options' : {
					'allowed_filetypes' : acp__components_viewmodule.ddl__register__file__allowed_filetypes__do_fetch('video'),
					'maxlength' : "",
					'@connector_enabled' : true,
					'is_required' : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Maximum Filesize'
						},
						'tip__for' : {
							'maxlength' : 'Maximum filesize (suffixes supported: &quot;1K&quot;, &quot;2M&quot;). Enter &quot;0&quot; to disable this restriction.'
						}
					}
				}
			},
			'any' : {
				'_label' : "Any",
				'options' : {
					'allowed_filetypes' : acp__components_viewmodule.ddl__register__file__allowed_filetypes__do_fetch('any'),
					'maxlength' : "",
					'@connector_enabled' : true,
					'is_required' : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Maximum Filesize'
						},
						'tip__for' : {
							'maxlength' : 'Maximum filesize (suffixes supported: &quot;1K&quot;, &quot;2M&quot;). Enter &quot;0&quot; to disable this restriction.'
						}
					}
				}
			}
		},
		'value__for' : {}
	},
	'link' : {
		'_label' : "Link with other module",
		'_links' : {
			'options' : {
				'is_required' : false
			}
		},
		'value__for' : {}
	}

};

/**
 * @param object
 *            Object that triggers this method
 * @return boolean TRUE on success, FALSE otherwise
 * @todo Re-write code with reverse logic: open all fieldsets first, then close unnecessary ones and apply values afterwards; instead of, closing all down and opening necessary ones. This way we can have more control over the form and not write dupe code.
 */
Acp_ComponentsViewmodule.prototype.ddl__register__apply_registry = function ( obj /* , customRegistry , doNotRePopulateSubType */ )
{
	/* Working form element */
	acp__components_viewmodule.Form.self = jQuery("#forms__components__ddl__alter_add");

	/* Registry */
	var registry = ( arguments[1] ) ? arguments[1] : acp__components_viewmodule.registry;

	/* 'doNotRePopulateSubType' - Defaults to FALSE */
	var doNotRePopulateSubType = ( arguments[2] ) ? arguments[2] : false;

	/* Initializing this.obj */
	this.obj = obj;

	/* Make sure it's not a string, but (DOM or jQuery) object */
	if ( typeof this.obj == 'string' )
	{
		if ( this.obj.match(/^#/) )
		{
			this.obj = jQuery(this.obj);
		}
		else
		{
			this.debug("Error: Invalid HTML-tag-id received - leading # character missing!");
			return false;
		}
	}
	else if ( typeof this.obj == 'object' )
	{
		/* We need to convert our object to jQuery object, if it's not already so. */
		if ( !(this.obj instanceof jQuery) )
		{
			this.obj = jQuery(this.obj);
		}
	}

	/**
	 * @var object Node of the Registry to populate
	 */
	var node_to_populate = null;

	/* Retrieve the node of the Registry we are going to populate */
	if ( this.obj.attr("name") == 'type' )
	{
		acp__components_viewmodule.Form.resetOnDemandObjects();

		node_to_populate = registry[this.obj.val()];

		if ( '_subtypes' in node_to_populate )
		{
			if ( doNotRePopulateSubType === false )
			{
				/* Populate "subtype" */
				jQuery("#forms__components__ddl__alter_add [name='subtype']").empty();
				var _subtype_options = "";
				jQuery.each(node_to_populate['_subtypes'], function ( key , value )
				{
					_subtype_options += "<option value='" + key + "'>" + value['_label'] + "</option>";
				});
				jQuery("#forms__components__ddl__alter_add .subtype.ondemand > [name='subtype']").append( _subtype_options );
			}
			acp__components_viewmodule.Form.enableOnDemandElement("#forms__components__ddl__alter_add .subtype.ondemand");

			/* Populate the subtype-configuration, by recursively-calling this method */
			arguments.callee("#forms__components__ddl__alter_add [name='subtype']", registry);
		}
		else if ( '_links' in node_to_populate )
		{
			/* Reveal 'Links with ...' */
			acp__components_viewmodule.Form.enableOnDemandElement("#forms__components__ddl__alter_add .links_with.ondemand");

			/* Do we have any 'linkable' modules in list? */
			if ( !jQuery("#forms__components__ddl__alter_add [name='links_with']").children("OPTION").not(":disabled").length )
			{
				jQuery("#forms__components__ddl__alter_add [name='links_with']").prepend("<option value=\"\" disabled=\"disabled\">-- no modules found --</option>");
				return false;
			}
			else
			{
				/* Do we have a module-name which needs to be "selected"? */
				if ( 'links_with' in node_to_populate['_links'] && node_to_populate['_links']['links_with'] != '' )
				{
					jQuery("#forms__components__ddl__alter_add [name='links_with']").children("OPTION[value='" + node_to_populate['_links']['links_with'] + "']").not(":disabled").get(0).selected = true;
				}
			}

			/* Populate the link-configuration, by recursively-calling this method */
			arguments.callee("#forms__components__ddl__alter_add [name='links_with']", registry);
		}

		/* Populate values, if any */
		for ( var key in node_to_populate['value__for'] )
		{
			var field_obj = jQuery("#forms__components__ddl__alter_add [name^='" + key + "']");
			var field_value = node_to_populate['value__for'][ key ];

			if ( field_obj.is("INPUT[type='text'],TEXTAREA") )
			{
				field_obj.val(field_value);
			}
			else if ( field_obj.is("SELECT") )
			{
				if ( !( field_value instanceof Array ) )
				{
					field_value = field_value.split(",");
				}
				for ( var _key in field_value )
				{
					field_obj.val( field_value );
				}
			}
		}
	}
	else if ( this.obj.attr("name") == 'subtype' )
	{
		acp__components_viewmodule.Form.resetOnDemandObjects(".subtype");

		/* Is "subtype" populated and visible? Populate, if not. */
		if ( jQuery("#forms__components__ddl__alter_add .subtype.ondemand").is(":hidden") )
		{
			return arguments.callee("#forms__components__ddl__alter_add [name='type']");
		}

		var _what_is_type = jQuery("#forms__components__ddl__alter_add [name='type']").val();
		node_to_populate = registry[_what_is_type]['_subtypes'][this.obj.val()];
		jQuery.each(node_to_populate['options'], function ( key , value )
		{
			/**
			 * @var mixed Self-recursion flag/object-reference (type is boolean for the former, object for the
			 *      latter)
			 */
			var _self_recursion = false;

			/* Skip keys with leading underscore */
			if ( !node_to_populate['options'].hasOwnProperty(key) || (typeof key == 'string' && key.match(/^_/)) )
			{
				return;
			}
			/* and determine keys which require self-recursion */
			else if ( typeof key == 'string' && key.match(/^@/) )
			{
				_self_recursion = true;
				key = key.replace(/^@/, "");
			}

			/* Apply additional information first */
			var _label = "";
			var _tip = "";
			if ( node_to_populate['options']['_additional_params'] )
			{
				if ( node_to_populate['options']['_additional_params']['label__for'] )
				{
					if ( key in node_to_populate['options']['_additional_params']['label__for'] )
					{
						_label = node_to_populate['options']['_additional_params']['label__for'][key];
						jQuery("#forms__components__ddl__alter_add ." + key + ".ondemand > LABEL:eq(0) > STRONG").html(_label);
					}
				}
				if ( node_to_populate['options']['_additional_params']['tip__for'] )
				{
					if ( key in node_to_populate['options']['_additional_params']['tip__for'] )
					{
						_tip = node_to_populate['options']['_additional_params']['tip__for'][key];
						jQuery("#forms__components__ddl__alter_add ." + key + ".ondemand > LABEL:eq(0) > EM").html(_tip);
					}
				}
			}

			/* Reveal the element */
			acp__components_viewmodule.Form.enableOnDemandElement("#forms__components__ddl__alter_add ." + key + ".ondemand");

			/**
			 * @var object Form fields
			 */
			var input_fields = jQuery("#forms__components__ddl__alter_add ." + key + ".ondemand [name^='" + key + "']");

			if ( input_fields.length == 1 )
			{
				input_fields.val("");
				if ( value != null )
				{
					/* SELECTs... */
					if ( typeof value == 'object' || value instanceof Array )
					{
						var _options = "";
						jQuery.each(value, function ( _key , _value )
						{
							_options += "<option value=\"" + _key + "\">." + _key + " - " + _value + "</option>";
						});
						input_fields.empty().append(_options);
					}
					/* Everything else... */
					else if ( typeof value == 'string' )
					{
						input_fields.val(value);
					}
				}

				/* Self-recursion obj */
				if ( _self_recursion != false )
				{
					_self_recursion = input_fields;
				}
			}
			else if ( input_fields.length > 1 )
			{
				input_fields.each(function ( index )
				{
					if ( jQuery(this).is(":radio") )
					{
						/* We have RADIO buttons here */
						jQuery(this).get(0).checked = false; // @see 4-5 lines below
						if ( jQuery(this).val() == '1' && ( value == true || value == '1' ) || jQuery(this).val() == '0' && ( value == false || value == '0' ) )
						{
							// Not working when invoked from form "reset" event handler :(
							jQuery("LABEL[for='" + jQuery(this).attr("id") + "']").click();
							jQuery(this).get(0).checked = true; // Going old-school...

							/* Self-recursion obj - "checked" one in case of RADIO buttons */
							if ( _self_recursion != false )
							{
								// Do nothing : click() handler above already invokes this recursion
								//_self_recursion = jQuery(this);
							}
						}
					}
				});
			}

			/* Self-recursion */
			if ( _self_recursion != false )
			{
				return arguments.callee(_self_recursion, registry);
			}

			return true;
		});
	}
	else if ( this.obj.attr("name") == 'connector_enabled' )
	{
		var whoami = jQuery("#forms__components__ddl__alter_add .connector_length_cap.ondemand");
		this.obj.val() == '1' ? acp__components_viewmodule.Form.enableOnDemandElement(whoami) : acp__components_viewmodule.Form.resetOnDemandElement(whoami);
	}
	else if ( this.obj.attr("name") == 'links_with' )
	{
		/* We don't have a module selected yet, so return here */
		if ( this.obj.val() == '' )
		{
			acp__components_viewmodule.Form.resetOnDemandElement("#forms__components__ddl__alter_add .links_with__e_data_definition.ondemand");
			return true;
		}

		/* Fetch m_data_definition for selected module */
		var _response = jQuery.ajax({
			type : "GET",
			url: "{{$MODULE_URL}}/components/viewmodule-" + this.obj.val(),
			async : false
		});
		_response = jQuery.parseJSON(_response.responseText);
		_response = _response['me']['m_data_definition'];
		var _m_data_definition = [];
		jQuery.each(_response, function ( key , value )
		{
			if ( value['type'] == 'link' )
			{
				// continue;
				return;
			}

			if ( value['connector_enabled'] == 1 )
			{
				for ( var c in value['c_data_definition'] )
				{
					_m_data_definition[key + "." + value['c_data_definition'][c]['name']] = {
						'name' : key + "." + value['c_data_definition'][c]['name'],
						'label' : value['c_data_definition'][c]['label']
					};
				}
			}
			else
			{
				_m_data_definition[key] = {
					'name' : value['name'],
					'label' : value['label']
				};
			}
		});
		delete(_response);

		/* Populate "links_with__e_data_definition" */
		jQuery("#forms__components__ddl__alter_add [name^='links_with__e_data_definition']").empty();
		for ( key in _m_data_definition )
		{
			jQuery("#forms__components__ddl__alter_add .links_with__e_data_definition.ondemand > [name^='links_with__e_data_definition']")
				.append("<option value='" + _m_data_definition[key]['name'] + "'>" + _m_data_definition[key]['label'] + " [" + _m_data_definition[key]['name'] + "]</option");
		}

		acp__components_viewmodule.Form.enableOnDemandElement("#forms__components__ddl__alter_add .links_with__e_data_definition.ondemand");
	}

	return true;
};

jQuery(document).ready(function ( event )
{
	/**
	 * Opens "DDL-Create" Form
	 */
	jQuery("#forms__components__ddl__list [type='button']:eq(0)").click(function ( event )
	{
		if ( jQuery("#components__ddl__alter_add").is(":hidden") )
		{
			jQuery("#content UL.data_container > .ondemand").hide(); // Close all .ondemand panes
			jQuery("#components__ddl__alter_add").show(); // Open "DDL-Create" form
		}
		jQuery("#forms__components__ddl__alter_add").trigger("reset"); // Reset "DDL-Create" Form
		jQuery("#forms__components__ddl__alter_add [type='reset']").attr("disabled", ""); // Re-enable 'Reset' button
		return;
	});

	/**
	 * Resets "DDL-Create" Form to its clean and default state
	 */
	jQuery("#forms__components__ddl__alter_add").bind("reset", function ( event )
	{
		/* Prelim */
		var whoami = acp__components_viewmodule.Form.self = jQuery("#forms__components__ddl__alter_add");
		var typeElement = whoami.find("[name='type']");

		/* Reset errors and close consoles */
		whoami.find(".error").removeClass("error");
		acp__components_viewmodule.Form.closeConsoles(true);

		/* Show hidden elements */
		whoami.find("[name='name']").removeAttr("disabled");
		whoami.find(".type").show();

		/* Reset values */
		whoami.find("INPUT[type='text']").val(null); // Reset all INPUT text-fields
		whoami.find("SELECT > OPTION:eq(0)").attr("selected","selected"); // Reset all SELECTs
		whoami.find("[name='do']").val("ddl_alter__add"); // 'do'
		whoami.find("[type='submit']").val("Register New Data-field");

		/* Apply Registry */
		acp__components_viewmodule.ddl__register__apply_registry(typeElement);

		/* Scroll to global Console */
		var currentConsole = acp__components_viewmodule.Form.scrollToConsole(true);
		currentConsole.html("Required fields (in red) must be filled-in!").removeClass("error success");

		/* Stop "reset" event from occuring... This is our show :-) */
		event.preventDefault();

		return;
	});

	/**
	 * Close "DDL-Create" Form
	 */
	jQuery("#forms__components__ddl__alter_add [type='button']:eq(0)").click(function ( event )
	{
		if ( jQuery("#components__ddl__alter_add").is(":visible") )
		{
			acp__components_viewmodule.Form.closeConsoles();
			jQuery("#components__ddl__alter_add").slideUp("medium");
		}
	});

	/**
	 * Binds onChange event for SELECTs of ".js__trigger_on_change" class
	 */
	jQuery("#forms__components__ddl__alter_add SELECT.js__trigger_on_change").change(function ( event )
	{
		acp__components_viewmodule.ddl__register__apply_registry(jQuery(this));
	});

	/**
	 * Binds onClick event for LABELs, :radio's and :checkbox'es of ".js__trigger_on_change" class
	 */
	jQuery("#forms__components__ddl__alter_add .js__trigger_on_change").filter("LABEL,:radio,:checkbox").click(function ( event )
	{
		var what_is_being_triggered = ( jQuery(this).is("LABEL") ) ? jQuery("#" + jQuery(this).attr("for")) : jQuery(this);
		if ( !( what_is_being_triggered.is(":disabled") ) )
		{
			acp__components_viewmodule.ddl__register__apply_registry(what_is_being_triggered);
		}
	});

	/**
	 * Submits "Define-as-Title" Request
	 */
	jQuery("#forms__components__ddl__list A.ddl_alter__set_title_column").click(function ( event )
	{
		event.preventDefault();
		jQuery("#forms__components__ddl__list [name='do']").val("ddl_alter__set_title_column");
		jQuery("#forms__components__ddl__list [name='ddl_checklist']").val(jQuery(this).attr("href").replace(/\?/,""));
		var whoami = acp__components_viewmodule.Form.self = jQuery("#forms__components__ddl__list");
		whoami.trigger("submit"); // Submit the form via AJAX (.js__go_ajax), it will handle itself...
	});

	/**
	 * Opens "DDL-Edit" Form
	 */
	jQuery("#forms__components__ddl__list A.ddl_alter__edit").click(function ( event )
	{
		event.preventDefault();
		var whoami = acp__components_viewmodule.Form.self = jQuery("#forms__components__ddl__list");
		whoami.find("[name='do']").val("ddl_alter__pre_edit");
		whoami.find("[name='ddl_checklist']").val(jQuery(this).attr("href").replace(/\?/,""));
		jQuery.ajax({
			data : whoami.serialize(),
			cache : false,
			async : false,
			success : function ( data )
			{
				if ( typeof data != 'object' )
				{
					return false;
				}

				/* Building Registry [configuration] */
				var registry = acp__components_viewmodule.cloneObject( acp__components_viewmodule.registry );

				/* Continue... */
				jQuery("#forms__components__ddl__list [type='button']:eq(0)").trigger("click"); // Click "Create New DDL" button to open form and reset it
				var typeElement = jQuery("#forms__components__ddl__alter_add [name='type']");

				/* Populate data into Registry (part 1) */
				registry[ data['type'] ]['value__for'] = {
					'name' : data['name'],
					'label' : data['label']
				};
				if ( data['connector_length_cap'] !== null )
				{
					registry[ data['type'] ]['value__for']['connector_length_cap'] = data['connector_length_cap'];
				}
				if ( data['links_with'] !== null )
				{
					registry[ data['type'] ]['_links']['links_with'] = data['links_with'].replace(/[^0-9a-z]/gi, "").toLowerCase();
				}
				if ( data['links_with__e_data_definition'] !== null )
				{
					registry[ data['type'] ]['value__for']['links_with__e_data_definition'] = data['links_with__e_data_definition'];
				}

				typeElement.val( data['type'] ).trigger("change"); // Trigger change() event handler for 'type' field

				/* Further clean-up on types which deal with 'subtype' field */
				if ( data['subtype'] !== null )
				{
					var subTypeElement = jQuery("#forms__components__ddl__alter_add [name='subtype']");
					subTypeElement.val( data['subtype'] ).trigger("change");

					/* Populate data into Registry (part 2) */
					for ( option in registry[ data['type'] ]['_subtypes'][ data['subtype'] ]['options'] )
					{
						if ( option == 'allowed_filetypes' )
						{
							registry[ data['type'] ]['value__for']['allowed_filetypes'] = data[ option ];
							continue;
						}
						var option__as_appears_in_data = option.replace(/^@/, "");
						if ( option__as_appears_in_data in data )
						{
							registry[ data['type'] ]['_subtypes'][ data['subtype'] ]['options'][ option ] = data[ option__as_appears_in_data ];
						}
					}
				}

				acp__components_viewmodule.ddl__register__apply_registry(typeElement, registry, true); // Apply Registry, BUT do not re-populate sub-type (third argument = true); otherwise, it will empty SELECT.subtype and we will lose our current value!!!
				jQuery("#forms__components__ddl__alter_add [type='reset']").attr("disabled", "disabled"); // Disable 'Reset' button

				/* Some values */
				jQuery("#forms__components__ddl__alter_add [name='do']").val("ddl_alter__edit");
				jQuery("#forms__components__ddl__alter_add [name='name__old']").val( data['name'] );
				jQuery("#forms__components__ddl__alter_add [type='submit']").val("Alter Data-field");

				/* Lock elements which shouldn't be changed */
				jQuery("#forms__components__ddl__alter_add [name='name']").attr("disabled", "disabled"); // 'name'
				jQuery("#forms__components__ddl__alter_add .type").hide(); // 'type'
				jQuery("#forms__components__ddl__alter_add .subtype").hide(); // 'subtype'
				jQuery("#forms__components__ddl__alter_add .links_with").hide(); // 'links_with'
				jQuery("#forms__components__ddl__alter_add .default_options").hide(); // 'default_options'
				jQuery("#forms__components__ddl__alter_add .maxlength").hide(); // 'maxlength'
				jQuery("#forms__components__ddl__alter_add .connector_enabled").hide(); // 'connector_enabled'

				return true;
			}
		});
	});

	/**
	 * "DDL-Drop" handler
	 */
	jQuery("#forms__components__ddl__list A.ddl_alter__drop").click(function ( event )
	{
		event.preventDefault();
		jQuery("#forms__components__ddl__list [name='ddl_checklist']").val(jQuery(this).attr("href").replace(/\?/,""));

		/* "Shall I back-up?" */
		acp__components_viewmodule.Form.closeConsoles();
		acp__components_viewmodule.Form.getDialogObject({
			body : "Would you like to BACK-UP the structure and the data of the dropped field?<br /><br /><b>STRONGLY RECOMMENDED!!!</b><br /><br /><b>IMPORTANT NOTICE:</b> 'Required' fields will have a data inconsistency as you add more content later on. Future restoration of this field from backups might become problematic!!!",
			buttons : [
				{
					text : "Yes, *backup* as well!",
					click : function ( event )
					{
						jQuery("#forms__components__ddl__list [name='do_backup_dropped_field']").val("1");
						jQuery(this).dialog("close");
					}
				},
				{
					text : "No, don't make backups!",
					click : function ( event )
					{
						jQuery("#forms__components__ddl__list [name='do_backup_dropped_field']").val("0");
						jQuery(this).dialog("close");
					}
				}
			],
			width : 500
		}).dialog("open").bind("dialogclose", function ( event , ui )
		{
			/* "Are you sure?" */
			acp__components_viewmodule.Form.getDialogObject({
				body : "You are about to <b>drop</b> a data-field! ACTION IS IRREVERSIBLE!!!<br /><br />Shall I proceed?",
				buttons : [
					{
						text : "Yes, *drop* it!",
						click : function ( event )
						{
							jQuery(this).unbind("dialogclose").dialog("close");
							jQuery("#forms__components__ddl__list [name='do']").val("ddl_alter__drop");
							var whoami = acp__components_viewmodule.Form.self = jQuery("#forms__components__ddl__list");
							whoami.trigger("submit"); // Submit the form via AJAX (.js__go_ajax), it will handle itself...
						}
					},
					{
						text : "No, stop!",
						click : function ( event )
						{
							jQuery(this).dialog("close");
							acp__components_viewmodule.Form.getDialogObject({
								body : "Request aborted by user! No actions performed!",
								buttons : [
									{
										text : "Thanks!",
										click : function ( event )
										{
											jQuery(this).dialog("close");
										}
									}
								]
							}).dialog("open").unbind("dialogclose");
						}
					}
				],
				width : 500
			}).dialog("open");
		});
	});

	/**
	 * Submits "DDL-BAK-Restore" Form
	 */
	jQuery("#forms__components__ddl__list_bak [type='button']:eq(0)").click(function ( event )
	{
		jQuery("#forms__components__ddl__list_bak [name='do']").val("ddl_alter__restore_backup");
		var whoami = acp__components_viewmodule.Form.self = jQuery("#forms__components__ddl__list_bak");
		whoami.trigger("submit"); // Submit the form via AJAX (.js__go_ajax), it will handle itself...
	});

	/**
	 * Submits "DDL-BAK-Purge" Form
	 */
	jQuery("#forms__components__ddl__list_bak [type='button']:eq(1)").click(function ( event )
	{
		/* "Are you sure?" */
		acp__components_viewmodule.Form.getDialogObject({
			body : "You are about to <b>purge</b> a backup of deleted data-field! ACTION IS IRREVERSIBLE!!!<br /><br />Shall I proceed?",
			buttons : [
				{
					text : "Yes, *purge* it!",
					click : function ( event )
					{
						jQuery(this).unbind("dialogclose").dialog("close");
						jQuery("#forms__components__ddl__list_bak [name='do']").val("ddl_alter__purge_backup");
						var whoami = acp__components_viewmodule.Form.self = jQuery("#forms__components__ddl__list_bak");
						whoami.trigger("submit"); // Submit the form via AJAX (.js__go_ajax), it will handle itself...
					}
				},
				{
					text : "No, stop!",
					click : function ( event )
					{
						jQuery(this).dialog("close");
						acp__components_viewmodule.Form.getDialogObject({
							body : "Request aborted by user! No actions performed!",
							buttons : [
								{
									text : "Thanks!",
									click : function ( event )
									{
										jQuery(this).dialog("close");
									}
								}
							]
						}).dialog("open").unbind("dialogclose");
					}
				}
			],
			width : 500
		}).dialog("open");
	});

	/**
	 * Executes "Link to Connector-Unit" Request
	 */
	jQuery("#forms__components__ddl__list A.ddl_alter__link_to_connector_unit").click(function ( event )
	{
		event.preventDefault();
		jQuery("#forms__components__ddl__list [name='do']").val("ddl_alter__link_to_connector_unit");
		jQuery("#forms__components__ddl__list [name='ddl_checklist']").val(jQuery(this).attr("href").replace(/\?/,""));
		var whoami = acp__components_viewmodule.Form.self = jQuery("#forms__components__ddl__list");
		whoami.trigger("submit"); // Submit the form via AJAX (.js__go_ajax), it will handle itself...
	});

	/**
	 * Handles DDL sorting
	 */
	jQuery("#tables__components__ddl__list TBODY.js__sortable").bind( "sortupdate", function ( e , tr )
	{
		var _post_serialized_for__ddl_sorted__with_action = jQuery(this).sortable("serialize") + '&do=ddl_alter__sort';
		var currentConsole = acp__components_viewmodule.Form.scrollToConsole();
		currentConsole.html("Processing... Please wait!").removeClass("error success").addClass("notice");
		jQuery.ajax({
			data : _post_serialized_for__ddl_sorted__with_action,
			cache : false,
			success : function ( data )
			{
				if ( data.responseCode == '1' )
				{
					currentConsole.html(data.responseMessage).removeClass("notice error").addClass("success").show();
					setTimeout("window.location = window.location.href", 2000);
					return 1;
				}
				else
				{
					currentConsole.html(data.faultMessage).removeClass("notice success").addClass("error").show();
					return 0;
				}
			}
		});
	});

	/**
	 * Opens "SR-Create" Form
	 */
	jQuery("#forms__components__sr__list [type='button']:eq(0)").click(function ( event )
	{
		if ( jQuery("#components__sr__create").is(":hidden") )
		{
			/* Close all .ondemand panes */
			jQuery("#content UL.data_container > .ondemand").hide();

			/* Open "SR-Create" form */
			jQuery("#components__sr__create").slideDown("medium", function ()
			{
				/* Reset "DDL-Create" Form */
				//jQuery("#forms__components__ddl__alter_add").trigger("reset");
			});
		}
	});
});