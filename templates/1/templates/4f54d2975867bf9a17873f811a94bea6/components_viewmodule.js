/**
 * Local Javascript object
 *
 * @returns {Object} Object handler
 */
function /* Class */ Acp_ComponentsViewmodule ()
{
	return {
		constructor : Acp_ComponentsViewmodule,
		obj         : null
	};
}

/* Extending with Persephone */
extend(Acp_ComponentsViewmodule, Persephone);

/* Instantiating Class handler */
var acp__components_viewmodule = Acp_ComponentsViewmodule.singleton();

/**
 * Fetches Mime-list to be populated into 'allowed_filetypes' field
 *
 * @param {String} subtype
 * @returns {*} NULL if no data, FALSE on error, TRUE otherwise
 */
Acp_ComponentsViewmodule.prototype.ddl__register__file__allowed_filetypes__do_fetch = function ( subtype )
{
	if ( subtype == '' || subtype == undefined || subtype == false ) {
		return false;
	}
	var _response = jQuery.ajax({
		type  : "GET",
		url   : window.location.href + "?do=ddl_alter__add__mimelist__do_fetch",
		data  : "mimetype=" + subtype,
		async : false
	});
	return jQuery.parseJSON(_response.responseText);
};

Acp_ComponentsViewmodule.prototype.registry__ddl = {
	'alphanumeric' : {
		'_label'      : "Alphanumeric",
		'_subtypes'   : {
			'string'              : {
				'_label'  : "General String",
				'options' : {
					'maxlength'          : "",
					'default_value'      : "",
					'@connector_enabled' : false,
					'is_html_allowed'    : false,
					'is_required'        : false,
					'is_unique'          : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Maxlength (in bytes):'
						},
						'tip__for'   : {
							'maxlength' : 'Maximum length in bytes. Leave empty to default to &quot;255&quot;. <b>Don&#039;t forget to compensate for multi-byte characters!</b>'
						}
					}
				}
			},
			'integer_signed_8'    : {
				'_label'  : "Numeric: Signed 8-bit-Integer (-128/127)",
				'options' : {
					'maxlength'          : "4",
					'default_value'      : "",
					'@connector_enabled' : false,
					'is_required'        : false,
					'is_unique'          : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Max # of Digits:'
						},
						'tip__for'   : {
							'maxlength' : 'Maximum number of digits, including sign (+/-) character. The value is automatically set and is shown here for your reference.'
						}
					}
				}
			},
			'integer_unsigned_8'  : {
				'_label'  : "Numeric: Unsigned 8-bit-Integer (0/255)",
				'options' : {
					'maxlength'          : "3",
					'default_value'      : "",
					'@connector_enabled' : false,
					'is_required'        : false,
					'is_unique'          : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Max # of Digits:'
						},
						'tip__for'   : {
							'maxlength' : 'Maximum number of digits, including sign (+/-) character. The value is automatically set and is shown here for your reference.'
						}
					}
				}
			},
			'integer_signed_16'   : {
				'_label'  : "Numeric: Signed 16-bit-Integer (-32768/32767)",
				'options' : {
					'maxlength'          : "6",
					'default_value'      : "",
					'@connector_enabled' : false,
					'is_required'        : false,
					'is_unique'          : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Max # of Digits:'
						},
						'tip__for'   : {
							'maxlength' : 'Maximum number of digits, including sign (+/-) character. The value is automatically set and is shown here for your reference.'
						}
					}
				}
			},
			'integer_unsigned_16' : {
				'_label'  : "Numeric: Unsigned 16-bit-Integer (0/65535)",
				'options' : {
					'maxlength'          : "5",
					'default_value'      : "",
					'@connector_enabled' : false,
					'is_required'        : false,
					'is_unique'          : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Max # of Digits:'
						},
						'tip__for'   : {
							'maxlength' : 'Maximum number of digits, including sign (+/-) character. The value is automatically set and is shown here for your reference.'
						}
					}
				}
			},
			'integer_signed_24'   : {
				'_label'  : "Numeric: Signed 24-bit-Integer (-8388608/8388607)",
				'options' : {
					'maxlength'          : "8",
					'default_value'      : "",
					'@connector_enabled' : false,
					'is_required'        : false,
					'is_unique'          : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Max # of Digits:'
						},
						'tip__for'   : {
							'maxlength' : 'Maximum number of digits, including sign (+/-) character. The value is automatically set and is shown here for your reference.'
						}
					}
				}
			},
			'integer_unsigned_24' : {
				'_label'  : "Numeric: Unsigned 24-bit-Integer (0/16777215)",
				'options' : {
					'maxlength'          : "8",
					'default_value'      : "",
					'@connector_enabled' : false,
					'is_required'        : false,
					'is_unique'          : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Max # of Digits:'
						},
						'tip__for'   : {
							'maxlength' : 'Maximum number of digits, including sign (+/-) character. The value is automatically set and is shown here for your reference.'
						}
					}
				}
			},
			'integer_signed_32'   : {
				'_label'  : "Numeric: Signed 32-bit-Integer (-2147483648/2147483647)",
				'options' : {
					'maxlength'          : "11",
					'default_value'      : "",
					'@connector_enabled' : false,
					'is_required'        : false,
					'is_unique'          : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Max # of Digits:'
						},
						'tip__for'   : {
							'maxlength' : 'Maximum number of digits, including sign (+/-) character. The value is automatically set and is shown here for your reference.'
						}
					}
				}
			},
			'integer_unsigned_32' : {
				'_label'  : "Numeric: Unsigned 32-bit-Integer (0/4294967295)",
				'options' : {
					'maxlength'          : "10",
					'default_value'      : "",
					'@connector_enabled' : false,
					'is_required'        : false,
					'is_unique'          : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Max # of Digits:'
						},
						'tip__for'   : {
							'maxlength' : 'Maximum number of digits, including sign (+/-) character. The value is automatically set and is shown here for your reference.'
						}
					}
				}
			},
			'integer_signed_64'   : {
				'_label'  : "Numeric: Signed 64-bit-Integer (-9223372036854775808/9223372036854775807)",
				'options' : {
					'maxlength'          : "20",
					'default_value'      : "",
					'@connector_enabled' : false,
					'is_required'        : false,
					'is_unique'          : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Max # of Digits:'
						},
						'tip__for'   : {
							'maxlength' : 'Maximum number of digits, including sign (+/-) character. The value is automatically set and is shown here for your reference.'
						}
					}
				}
			},
			'integer_unsigned_64' : {
				'_label'  : "Numeric: Unsigned 64-bit-Integer (0/18446744073709551615)",
				'options' : {
					'maxlength'          : "20",
					'default_value'      : "",
					'@connector_enabled' : false,
					'is_required'        : false,
					'is_unique'          : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Max # of Digits:'
						},
						'tip__for'   : {
							'maxlength' : 'Maximum number of digits, including sign (+/-) character. The value is automatically set and is shown here for your reference.'
						}
					}
				}
			},
			'decimal_signed'      : {
				'_label'  : "Numeric: Signed Decimal (Fixed-Point)",
				'options' : {
					'maxlength'          : "10,0",
					'default_value'      : "0",
					'@connector_enabled' : false,
					'is_required'        : false,
					'is_unique'          : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Precision &amp; Scale:'
						},
						'tip__for'   : {
							'maxlength' : 'In &quot;<i><b>p,s</b></i>&quot; format; e.g.: &quot;5,2&quot; means total 5 digits, 2 of which follows decimal point, as in &quot;999.99&quot;. Leave empty to default to &quot;10,0&quot;.'
						}
					}
				}
			},
			'decimal_unsigned'    : {
				'_label'  : "Numeric: Unsigned Decimal (Fixed-Point)",
				'options' : {
					'maxlength'          : "10,0",
					'default_value'      : "0",
					'@connector_enabled' : false,
					'is_required'        : false,
					'is_unique'          : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Precision &amp; Scale:'
						},
						'tip__for'   : {
							'maxlength' : 'In &quot;<i><b>p,s</b></i>&quot; format; e.g.: &quot;5,2&quot; means total 5 digits, 2 of which follows decimal point, as in &quot;999.99&quot;. Leave empty to default to &quot;10,0&quot;.'
						}
					}
				}
			},
			'dropdown'            : {
				'_label'  : "Single Select (Dropdowns/Radios)",
				'options' : {
					'default_options'    : "",
					'default_value'      : "",
					'is_required'        : true,
					'_additional_params' : {}
				}
			},
			'multiple'            : {
				'_label'  : "Multiple Select (incl. Checkboxes)",
				'options' : {
					'default_options'    : "",
					'default_value'      : "",
					'is_required'        : true,
					'_additional_params' : {}
				}
			}
		},
		'_value__for' : {}
	},
	'file'         : {
		'_label'      : "File",
		'_subtypes'   : {
			'image' : {
				'_label'  : "Image files",
				'options' : {
					'allowed_filetypes'  : acp__components_viewmodule.ddl__register__file__allowed_filetypes__do_fetch('image'),
					'maxlength'          : "",
					'@connector_enabled' : true,
					'is_required'        : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Maximum Filesize:'
						},
						'tip__for'   : {
							'maxlength' : 'Maximum filesize (suffixes supported: &quot;1K&quot;, &quot;2M&quot;). Enter &quot;0&quot; to disable this restriction.'
						}
					}
				}
			},
			'audio' : {
				'_label'  : "Audio files",
				'options' : {
					'allowed_filetypes'  : acp__components_viewmodule.ddl__register__file__allowed_filetypes__do_fetch('audio'),
					'maxlength'          : "",
					'@connector_enabled' : true,
					'is_required'        : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Maximum Filesize:'
						},
						'tip__for'   : {
							'maxlength' : 'Maximum filesize (suffixes supported: &quot;1K&quot;, &quot;2M&quot;). Enter &quot;0&quot; to disable this restriction.'
						}
					}
				}
			},
			'video' : {
				'_label'  : "Video files",
				'options' : {
					'allowed_filetypes'  : acp__components_viewmodule.ddl__register__file__allowed_filetypes__do_fetch('video'),
					'maxlength'          : "",
					'@connector_enabled' : true,
					'is_required'        : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Maximum Filesize:'
						},
						'tip__for'   : {
							'maxlength' : 'Maximum filesize (suffixes supported: &quot;1K&quot;, &quot;2M&quot;). Enter &quot;0&quot; to disable this restriction.'
						}
					}
				}
			},
			'any'   : {
				'_label'  : "Any",
				'options' : {
					'allowed_filetypes'  : acp__components_viewmodule.ddl__register__file__allowed_filetypes__do_fetch('any'),
					'maxlength'          : "",
					'@connector_enabled' : true,
					'is_required'        : false,
					'_additional_params' : {
						'label__for' : {
							'maxlength' : 'Maximum Filesize:'
						},
						'tip__for'   : {
							'maxlength' : 'Maximum filesize (suffixes supported: &quot;1K&quot;, &quot;2M&quot;). Enter &quot;0&quot; to disable this restriction.'
						}
					}
				}
			}
		},
		'_value__for' : {}
	},
	'link'         : {
		'_label'      : "Link with other module",
		'_links'      : {
			'options' : {
				'is_required' : false
			}
		},
		'_value__for' : {}
	}

};

Acp_ComponentsViewmodule.prototype.registry__sr = {
	's_data_source' : {
		'no-fetch' : {
			'_tabs'                  : {},
			'_disabled_options__for' : {
				's_data_target' : ['tpl']
			},
			'_value__for'            : {}
		},
		'rdbms'    : {
			'_tabs'                  : {
				1 : {  // 'tabs__sr_alter_add__s_data_source'
					's_data_definition'                     : null,
					'@s_fetch_criteria__all_or_selected'    : "all",
					's_fetch_criteria__limit'               : "",
					's_fetch_criteria__pagination'          : "",
					'@s_fetch_criteria__do_perform_sorting' : "0"
				}
			},
			'_disabled_options__for' : {},
			'_value__for'            : {}
		},
		'dom'      : {

		},
		'json'     : {

		}
	}
};

//noinspection JSCommentMatchesSignature
/**
 * @params {Object} obj Object that triggers this method
 * @returns {Boolean} TRUE on success, FALSE otherwise
 */
Acp_ComponentsViewmodule.prototype.ddl__register__apply_registry = function ( obj /* , customRegistry , doNotRePopulateSubType */ )
{
	/* Working form element */
	acp__components_viewmodule.Form.self = jQuery("#forms__components__ddl__alter_add");

	/* Registry */
	var registry = ( arguments[1] ) ? arguments[1] : acp__components_viewmodule.registry__ddl;

	/* 'doNotRePopulateSubType' - Defaults to FALSE */
	var doNotRePopulateSubType = ( arguments[2] ) ? arguments[2] : false;

	/* Initializing this.obj */
	this.obj = obj;

	/* Make sure it's not a string, but (DOM or jQuery) object */
	if ( typeof this.obj == 'string' ) {
		if ( this.obj.match(/^#/) ) {
			this.obj = jQuery(this.obj);
		}
		else {
			this.debug("Error: Invalid HTML-tag-id received - leading # character missing!");
			return false;
		}
	}
	else if ( typeof this.obj == 'object' ) {
		/* We need to convert our object to jQuery object, if it's not already so. */
		if ( !(this.obj instanceof jQuery) ) {
			this.obj = jQuery(this.obj);
		}
	}

	/**
	 * @var {Object} Node of the Registry to populate
	 */
	var node_to_populate = null;

	/* Retrieve the node of the Registry we are going to populate */
	if ( this.obj.attr("name") == 'type' ) {
		acp__components_viewmodule.Form.resetOnDemandObjects();

		node_to_populate = registry[this.obj.val()];

		if ( '_subtypes' in node_to_populate ) {
			if ( doNotRePopulateSubType === false ) {
				/* Populate "subtype" */
				jQuery("#forms__components__ddl__alter_add [name='subtype']").empty();
				var _subtype_options = "";
				jQuery.each(node_to_populate['_subtypes'], function ( key, value )
				{
					_subtype_options += "<option value='" + key + "'>" + value['_label'] + "</option>";
				});
				jQuery("#forms__components__ddl__alter_add .subtype.ondemand > [name='subtype']").append(_subtype_options);
			}
			acp__components_viewmodule.Form.enableOnDemandElement("#forms__components__ddl__alter_add .subtype.ondemand");

			/* Populate the subtype-configuration, by recursively-calling this method */
			arguments.callee("#forms__components__ddl__alter_add [name='subtype']", registry);
		}
		else if ( '_links' in node_to_populate ) {
			/* Reveal 'Links with ...' */
			acp__components_viewmodule.Form.enableOnDemandElement("#forms__components__ddl__alter_add .links_with.ondemand");

			/* Do we have any 'linkable' modules in list? */
			if ( !jQuery("#forms__components__ddl__alter_add [name='links_with']").children("OPTION").not(":disabled").length ) {
				jQuery("#forms__components__ddl__alter_add [name='links_with']").prepend("<option value=\"\" disabled=\"disabled\">-- no modules found --</option>");
				return false;
			}
			else {
				/* Do we have a module-name which needs to be "selected"? */
				if ( 'links_with' in node_to_populate['_links'] && node_to_populate['_links']['links_with'] != '' ) {
					jQuery("#forms__components__ddl__alter_add [name='links_with']").children("OPTION[value='" + node_to_populate['_links']['links_with'] + "']").not(":disabled").get(0).selected = true;
				}
			}

			/* Populate the link-configuration, by recursively-calling this method */
			arguments.callee("#forms__components__ddl__alter_add [name='links_with']", registry);
		}

		/* Populate values, if any */
		for ( var key in node_to_populate['_value__for'] ) {
			var field_obj = jQuery("#forms__components__ddl__alter_add [name^='" + key + "']");
			var field_value = node_to_populate['_value__for'][ key ];

			if ( field_obj.is("INPUT[type='text'],TEXTAREA") ) {
				field_obj.val(field_value);
			}
			else if ( field_obj.is("SELECT") ) {
				if ( !( field_value instanceof Array ) ) {
					field_value = field_value.split(",");
				}
				field_obj.val(field_value);
			}
		}
	}
	else if ( this.obj.attr("name") == 'subtype' ) {
		acp__components_viewmodule.Form.resetOnDemandObjects(".subtype");

		/* Is "subtype" populated and visible? Populate, if not. */
		if ( jQuery("#forms__components__ddl__alter_add .subtype.ondemand").is(":hidden") ) {
			return arguments.callee("#forms__components__ddl__alter_add [name='type']");
		}

		var _what_is_type = jQuery("#forms__components__ddl__alter_add [name='type']").val();
		node_to_populate = registry[_what_is_type]['_subtypes'][this.obj.val()];
		jQuery.each(node_to_populate['options'], function ( key, value )
		{
			/**
			 * @var {*} Self-recursion flag/object-reference (type is boolean for the former, object for the
			 *      latter)
			 */
			var _self_recursion = false;

			/* Skip keys with leading underscore */
			if ( !node_to_populate['options'].hasOwnProperty(key) || (typeof key == 'string' && key.match(/^_/)) ) {
				return;
			}
			/* and determine keys which require self-recursion */
			else if ( typeof key == 'string' && key.match(/^@/) ) {
				_self_recursion = true;
				key = key.replace(/^@/, "");
			}

			/* Apply additional information first */
			var _label = "";
			var _tip = "";
			if ( node_to_populate['options']['_additional_params'] ) {
				if ( node_to_populate['options']['_additional_params']['label__for'] ) {
					if ( key in node_to_populate['options']['_additional_params']['label__for'] ) {
						_label = node_to_populate['options']['_additional_params']['label__for'][key];
						jQuery("#forms__components__ddl__alter_add ." + key + ".ondemand > LABEL:first > STRONG").html(_label);
					}
				}
				if ( node_to_populate['options']['_additional_params']['tip__for'] ) {
					if ( key in node_to_populate['options']['_additional_params']['tip__for'] ) {
						_tip = node_to_populate['options']['_additional_params']['tip__for'][key];
						jQuery("#forms__components__ddl__alter_add ." + key + ".ondemand > EM.ui-tooltip").html(_tip);
					}
				}
			}

			/* Reveal the element */
			acp__components_viewmodule.Form.enableOnDemandElement("#forms__components__ddl__alter_add ." + key + ".ondemand");

			/**
			 * @var {Object} Form fields
			 */
			var input_fields = jQuery("#forms__components__ddl__alter_add ." + key + ".ondemand [name^='" + key + "']");

			if ( input_fields.length == 1 ) {
				input_fields.val("");
				if ( value != null ) {
					/* SELECTs... */
					if ( typeof value == 'object' || value instanceof Array ) {
						var _options = "";
						jQuery.each(value, function ( _key, _value )
						{
							_options += "<option value=\"" + _key + "\">." + _key + " - " + _value + "</option>";
						});
						input_fields.empty().append(_options);
					}
					/* Everything else... */
					else if ( typeof value == 'string' ) {
						input_fields.val(value);
					}
				}

				/* Self-recursion obj */
				if ( _self_recursion != false ) {
					_self_recursion = input_fields;
				}
			}
			else if ( input_fields.length > 1 ) {
				input_fields.each(function ( index )
				{
					if ( jQuery(this).is(":radio") ) {
						/* We have RADIO buttons here */
						jQuery(this).get(0).checked = false; // @see 4-5 lines below
						if ( jQuery(this).val() == '1' && ( value == true || value == '1' ) || jQuery(this).val() == '0' && ( value == false || value == '0' ) ) {
							// Not working when invoked from form "reset" event handler :(
							jQuery("LABEL[for='" + jQuery(this).attr("id") + "']").click();
							jQuery(this).get(0).checked = true; // Going old-school...

							/* Self-recursion obj - "checked" one in case of RADIO buttons */
							if ( _self_recursion != false ) {
								// Do nothing : click() handler above already invokes this recursion
								//_self_recursion = jQuery(this);
							}
						}
					}
				});
			}

			/* Self-recursion */
			if ( _self_recursion != false ) {
				return arguments.callee(_self_recursion, registry);
			}

			return true;
		});
	}
	else if ( this.obj.attr("name") == 'connector_enabled' ) {
		var whoami = jQuery("#forms__components__ddl__alter_add .connector_length_cap.ondemand");
		this.obj.val() == '1' ? acp__components_viewmodule.Form.enableOnDemandElement(whoami) : acp__components_viewmodule.Form.resetOnDemandElement(whoami);
	}
	else if ( this.obj.attr("name") == 'links_with' ) {
		/* We don't have a module selected yet, so return here */
		if ( this.obj.val() == '' ) {
			acp__components_viewmodule.Form.resetOnDemandElement("#forms__components__ddl__alter_add .links_with__e_data_definition.ondemand");
			return true;
		}

		/* Fetch m_data_definition for selected module */
		var _response = jQuery.ajax({
			type  : "GET",
			url   : "{{$MODULE_URL}}/components/viewmodule-" + this.obj.val(),
			async : false
		});
		_response = jQuery.parseJSON(_response.responseText);
		_response = _response['me']['m_data_definition'];
		var _m_data_definition = [];
		jQuery.each(_response, function ( key, value )
		{
			if ( value['type'] == 'link' ) {
				// continue;
				return;
			}

			if ( value['connector_enabled'] == 1 ) {
				for ( var c in value['c_data_definition'] ) {
					_m_data_definition[key + "." + value['c_data_definition'][c]['name']] = {
						'name'  : key + "." + value['c_data_definition'][c]['name'],
						'label' : value['c_data_definition'][c]['label']
					};
				}
			}
			else {
				_m_data_definition[key] = {
					'name'  : value['name'],
					'label' : value['label']
				};
			}
		});
		delete(_response);

		/* Populate "links_with__e_data_definition" */
		jQuery("#forms__components__ddl__alter_add [name^='links_with__e_data_definition']").empty();
		for ( key in _m_data_definition ) {
			jQuery("#forms__components__ddl__alter_add .links_with__e_data_definition.ondemand > [name^='links_with__e_data_definition']")
				.append("<option value='" + _m_data_definition[key]['name'] + "'>" + _m_data_definition[key]['label'] + " [" + _m_data_definition[key]['name'] + "]</option");
		}

		acp__components_viewmodule.Form.enableOnDemandElement("#forms__components__ddl__alter_add .links_with__e_data_definition.ondemand");
	}

	return true;
};

/**
 * @param {Object} obj Object that triggers this method
 * @returns {Boolean} TRUE on success, FALSE otherwise
 */
Acp_ComponentsViewmodule.prototype.sr__create__apply_registry = function ( obj /* , customRegistry */ )
{
	/* Working form element */
	acp__components_viewmodule.Form.self = jQuery("#forms__components__sr__create");

	/* Registry */
	var registry = ( arguments[1] ) ? arguments[1] : acp__components_viewmodule.registry__sr;

	/* Initializing this.obj */
	this.obj = obj;

	/* Make sure it's not a string, but (DOM or jQuery) object */
	if ( typeof this.obj == 'string' ) {
		if ( this.obj.match(/^#/) ) {
			this.obj = jQuery(this.obj);
		}
		else {
			this.debug("Error: Invalid HTML-tag-id received - leading # character missing!");
			return false;
		}
	}
	else if ( typeof this.obj == 'object' ) {
		/* We need to convert our object to jQuery object, if it's not already so. */
		if ( !(this.obj instanceof jQuery) ) {
			this.obj = jQuery(this.obj);
		}
	}

	/**
	 * @var {Object} Node of the Registry to populate
	 */
	var node_to_populate = null;

	/* Retrieve the node of the Registry we are going to populate */
	if ( this.obj.attr("name") == 's_data_source' ) {
		/* Disable all "tabs" and reset .ondemand elements */
		jQuery(".ui-tabs.sr_alter_add__s_data_flow_config").tabs("option", "disabled", [1, 2, 3]);
		acp__components_viewmodule.Form.resetOnDemandObjects();

		/* Delete cloned elements */
		acp__components_viewmodule.Form.self.find("SPAN.s_fetch_criteria__query").not(":first").remove();
		acp__components_viewmodule.Form.self.find("SPAN.s_fetch_criteria__policies").not(":first").remove();
		acp__components_viewmodule.Form.self.find("SPAN.sr__sort_by").not(":first").remove();

		node_to_populate = registry['s_data_source'][this.obj.val()];

		if ( '_tabs' in node_to_populate ) {
			/* Enable "tabs" and their .ondemand elements */
			for ( var tabId in node_to_populate['_tabs'] ) {
				jQuery(".ui-tabs.sr_alter_add__s_data_flow_config").tabs("enable", parseInt(tabId));

				jQuery.each(node_to_populate['_tabs'][ tabId ], function ( key, value )
				{
					/**
					 * @var {*} Self-recursion flag/object-reference (type is {Boolean} for the former, {Object} for the latter)
					 */
					var _self_recursion = false;

					// Skip keys with leading underscore
					if ( !node_to_populate['_tabs'][ tabId ].hasOwnProperty(key) || (typeof key == 'string' && key.match(/^_/)) ) {
						return;
					}
					// and determine keys which require self-recursion
					else if ( typeof key == 'string' && key.match(/^@/) ) {
						_self_recursion = true;
						key = key.replace(/^@/, "");
					}

					// Reveal the element
					acp__components_viewmodule.Form.enableOnDemandElement("#tabs__sr_alter_add__s_data_source > ." + key + ".ondemand", true);

					/**
					 * @var {Object} Form fields
					 */
					var input_fields = acp__components_viewmodule.Form.self.find("." + key + " [name^='" + key + "']");

					if ( input_fields.length == 1 ) {
						input_fields.val("");
						if ( value != null ) {
							if ( input_fields.is("INPUT[type='text'],TEXTAREA") ) {
								input_fields.val(value);
							}
							else if ( input_fields.is("SELECT") ) {
								if ( !( value instanceof Array ) ) {
									value = value.split(",");
								}
								input_fields.change(value);
							}
						}

						// Self-recursion obj
						if ( _self_recursion != false ) {
							_self_recursion = input_fields;
						}
					}
					else if ( input_fields.length > 1 ) {
						input_fields.each(function ( index )
						{
							if ( jQuery(this).is(":radio") ) {
								// We have RADIO buttons here
								jQuery(this).get(0).checked = false; // @see 4-5 lines below
								if ( jQuery(this).val() == '1' && (value == true || value == '1') || jQuery(this).val() == '0' && (value == false || value == '0') ) {
									// Not working when invoked from form "reset" event handler :(
									jQuery("LABEL[for='" + jQuery(this).attr("id") + "']").click();
									jQuery(this).get(0).checked = true; // Going old-school...

									// Self-recursion obj - "checked" one in case of RADIO buttons
									if ( _self_recursion != false ) {
										// Do nothing : click() handler above already invokes this recursion
										// _self_recursion = jQuery(this);
									}
								}
							}
						});
					}

					// Self-recursion
					if ( _self_recursion != false ) {
						return arguments.callee(_self_recursion, registry);
					}
					return true;
				});
			}
		}

		if ( '_value__for' in node_to_populate ) {
			// Populate values, if any
			for ( var key in node_to_populate['_value__for'] ) {
				var field_obj = acp__components_viewmodule.Form.self.find("[name^='" + key + "']");
				var field_value = node_to_populate['_value__for'][ key ];

				if ( field_obj.is("INPUT[type='text'],TEXTAREA") ) {
					field_obj.val(field_value);
				}
				else if ( field_obj.is("SELECT") ) {
					if ( !( field_value instanceof Array ) ) {
						field_value = field_value.split(",");
					}
					for ( var _key in field_value ) {
						field_obj.val(field_value);
					}
				}
			}
		}

		if ( '_disabled_options__for' in node_to_populate ) {
			acp__components_viewmodule.Form.self.find("SELECT > OPTION:disabled").prop("disabled", "");
			for ( var key in node_to_populate['_disabled_options__for'] ) {
				for ( var option in node_to_populate['_disabled_options__for'][key] ) {
					acp__components_viewmodule.Form.self.find("SELECT[name='" + key + "'] > OPTION[value='" + node_to_populate['_disabled_options__for'][key] + "']").prop("disabled", "disabled");
				}
				if ( acp__components_viewmodule.Form.self.find("SELECT[name='" + key + "']").val() == node_to_populate['_disabled_options__for'][key] ) {
					acp__components_viewmodule.Form.self.find("SELECT[name='" + key + "'] > OPTION:enabled:first").prop("selected", "selected");
				}
			}
		}

		/* Populate Data-target-configuration, by recursively-calling this method */
		arguments.callee("#forms__components__sr__create [name='s_data_target']", registry);
	}

	else if ( this.obj.attr("name") == 's_data_target' ) {
		/* Disable **only** .ondemand elemens within Data-target-configuration-tab!!! */
		acp__components_viewmodule.Form.resetOnDemandObjects("#tabs__sr_alter_add__s_data_source .ondemand, #tabs__sr_alter_add__s_data_processing .ondemand");

		// @todo Coming soon...
	}

	else if ( this.obj.attr("name") == 's_fetch_criteria__all_or_selected' ) {
		var whoami = jQuery("#forms__components__sr__create .ondemand.s_fetch_criteria__all_or_selected .s_fetch_criteria__policies, #forms__components__sr__create .ondemand.s_fetch_criteria__all_or_selected .s_fetch_criteria__query");
		if ( this.obj.val() == 'all' ) {
			jQuery("#forms__components__sr__create SPAN.s_fetch_criteria__query").not(":first").remove();
			jQuery("#forms__components__sr__create SPAN.s_fetch_criteria__policies").not(":first").remove();

			acp__components_viewmodule.Form.resetOnDemandElement(whoami);
		}
		else if ( this.obj.val() == 'selected' ) {
			acp__components_viewmodule.Form.enableOnDemandElement(whoami);
		}
	}

	else if ( this.obj.attr("name") == 's_fetch_criteria__do_perform_sorting' ) {
		var whoami = jQuery("#forms__components__sr__create .ondemand.s_fetch_criteria__do_perform_sorting SPAN.sr__sort_by");
		if ( this.obj.val() == '0' ) {
			jQuery("#forms__components__sr__create SPAN.sr__sort_by").not(":first").remove();
			acp__components_viewmodule.Form.resetOnDemandElement(whoami);
		}
		else if ( this.obj.val() == '1' ) {
			acp__components_viewmodule.Form.enableOnDemandElement(whoami);
		}
	}

	return true;
};

jQuery(document).ready(function ( event )
{
	/**
	 * Opens "DDL-Create" Form
	 */
	jQuery("#forms__components__ddl__list [type='button']:first").click(function ( event )
	{
		if ( jQuery("#components__ddl__alter_add").is(":hidden") ) {
			jQuery(".section > .ondemand").hide(); // Close all .ondemand panes
			jQuery("#components__ddl__alter_add").show(); // Open "DDL-Create" form
		}
		jQuery("#forms__components__ddl__alter_add").trigger("reset"); // Reset "DDL-Create" Form
		jQuery("#forms__components__ddl__alter_add [type='reset']").prop("disabled", ""); // Re-enable 'Reset' button
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
		whoami.find("[name='do']").val("ddl_alter__add"); // 'do'
		whoami.find("[type='submit']").val("Register New Data-field");
		whoami.find("SELECT").each(function ( index ) // Reset all SELECTs
		{
			jQuery(this).find("OPTION:first").prop("selected", "selected");
		});

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
	jQuery("#forms__components__ddl__alter_add [type='button']:first").click(function ( event )
	{
		if ( jQuery("#components__ddl__alter_add").is(":visible") ) {
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
		if ( !( what_is_being_triggered.is(":disabled") ) ) {
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
		jQuery("#forms__components__ddl__list [name='ddl_checklist']").val(jQuery(this).attr("href").replace(/\?/, ""));
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
		whoami.find("[name='ddl_checklist']").val(jQuery(this).attr("href").replace(/\?/, ""));
		jQuery.ajax({
			data    : whoami.serialize(),
			cache   : false,
			async   : false,
			success : function ( data )
			{
				if ( typeof data != 'object' ) {
					return false;
				}

				/* Building Registry [configuration] */
				var registry = acp__components_viewmodule.cloneObject(acp__components_viewmodule.registry__ddl);

				/* Continue... */
				jQuery("#forms__components__ddl__list [type='button']:first").trigger("click"); // Click "Create New DDL" button to open form and reset it
				var typeElement = jQuery("#forms__components__ddl__alter_add [name='type']");

				/* Populate data into Registry (part 1) */
				registry[ data['type'] ]['_value__for'] = {
					'name'  : data['name'],
					'label' : data['label']
				};
				if ( data['connector_length_cap'] !== null ) {
					registry[ data['type'] ]['_value__for']['connector_length_cap'] = data['connector_length_cap'];
				}
				if ( data['links_with'] !== null ) {
					registry[ data['type'] ]['_links']['links_with'] = data['links_with'].replace(/[^0-9a-z]/gi, "").toLowerCase();
				}
				if ( data['links_with__e_data_definition'] !== null ) {
					registry[ data['type'] ]['_value__for']['links_with__e_data_definition'] = data['links_with__e_data_definition'];
				}

				typeElement.val(data['type']).trigger("change"); // Trigger change() event handler for 'type' field

				/* Further clean-up on types which deal with 'subtype' field */
				if ( data['subtype'] !== null ) {
					var subTypeElement = jQuery("#forms__components__ddl__alter_add [name='subtype']");
					subTypeElement.val(data['subtype']).trigger("change");

					/* Populate data into Registry (part 2) */
					for ( option in registry[ data['type'] ]['_subtypes'][ data['subtype'] ]['options'] ) {
						if ( option == 'allowed_filetypes' ) {
							registry[ data['type'] ]['_value__for']['allowed_filetypes'] = data[ option ];
							continue;
						}
						var option__as_appears_in_data = option.replace(/^@/, "");
						if ( option__as_appears_in_data in data ) {
							registry[ data['type'] ]['_subtypes'][ data['subtype'] ]['options'][ option ] = data[ option__as_appears_in_data ];
						}
					}
				}

				acp__components_viewmodule.ddl__register__apply_registry(typeElement, registry, true); // Apply Registry, BUT do not re-populate sub-type (third argument = true); otherwise, it will empty SELECT.subtype and we will lose our current value!!!
				jQuery("#forms__components__ddl__alter_add [type='reset']").prop("disabled", "disabled"); // Disable 'Reset' button

				/* Some values */
				jQuery("#forms__components__ddl__alter_add [name='do']").val("ddl_alter__edit");
				jQuery("#forms__components__ddl__alter_add [name='name__old']").val(data['name']);
				jQuery("#forms__components__ddl__alter_add [type='submit']").val("Alter Data-field");

				/* Lock elements which shouldn't be changed */
				jQuery("#forms__components__ddl__alter_add [name='name']").prop("disabled", "disabled"); // 'name'
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
		jQuery("#forms__components__ddl__list [name='ddl_checklist']").val(jQuery(this).attr("href").replace(/\?/, ""));

		/* "Shall I back-up?" */
		acp__components_viewmodule.Form.closeConsoles();
		acp__components_viewmodule.Form.getDialogObject({
			body    : "Would you like to BACK-UP the structure and the data of the dropped field?<br /><br /><b>STRONGLY RECOMMENDED!!!</b><br /><br /><b>IMPORTANT NOTICE:</b> 'Required' fields will have a data inconsistency as you add more content later on. Future restoration of this field from backups might become problematic!!!",
			buttons : [
				{
					text  : "Yes, *backup* as well!",
					click : function ( event )
					{
						jQuery("#forms__components__ddl__list [name='do_backup_dropped_field']").val("1");
						jQuery(this).dialog("close");
					}
				},
				{
					text  : "No, don't make backups!",
					click : function ( event )
					{
						jQuery("#forms__components__ddl__list [name='do_backup_dropped_field']").val("0");
						jQuery(this).dialog("close");
					}
				}
			],
			width   : 500
		}).dialog("open").bind("dialogclose", function ( event, ui )
			{
				/* "Are you sure?" */
				acp__components_viewmodule.Form.getDialogObject({
					body    : "You are about to <b>drop</b> a data-field! ACTION IS IRREVERSIBLE!!!<br /><br />Shall I proceed?",
					buttons : [
						{
							text  : "Yes, *drop* it!",
							click : function ( event )
							{
								jQuery(this).unbind("dialogclose").dialog("close");
								jQuery("#forms__components__ddl__list [name='do']").val("ddl_alter__drop");
								var whoami = acp__components_viewmodule.Form.self = jQuery("#forms__components__ddl__list");
								whoami.trigger("submit"); // Submit the form via AJAX (.js__go_ajax), it will handle itself...
							}
						},
						{
							text  : "No, stop!",
							click : function ( event )
							{
								jQuery(this).dialog("close");
								acp__components_viewmodule.Form.getDialogObject({
									body    : "Request aborted by user! No actions performed!",
									buttons : [
										{
											text  : "Thanks!",
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
					width   : 500
				}).dialog("open");
			});
	});

	/**
	 * Submits "DDL-BAK-Restore" Form
	 */
	jQuery("#forms__components__ddl__list_bak [type='button']:first").click(function ( event )
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
			body    : "You are about to <b>purge</b> a backup of deleted data-field! ACTION IS IRREVERSIBLE!!!<br /><br />Shall I proceed?",
			buttons : [
				{
					text  : "Yes, *purge* it!",
					click : function ( event )
					{
						jQuery(this).unbind("dialogclose").dialog("close");
						jQuery("#forms__components__ddl__list_bak [name='do']").val("ddl_alter__purge_backup");
						var whoami = acp__components_viewmodule.Form.self = jQuery("#forms__components__ddl__list_bak");
						whoami.trigger("submit"); // Submit the form via AJAX (.js__go_ajax), it will handle itself...
					}
				},
				{
					text  : "No, stop!",
					click : function ( event )
					{
						jQuery(this).dialog("close");
						acp__components_viewmodule.Form.getDialogObject({
							body    : "Request aborted by user! No actions performed!",
							buttons : [
								{
									text  : "Thanks!",
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
			width   : 500
		}).dialog("open");
	});

	/**
	 * Executes "Link to Connector-Unit" Request
	 */
	jQuery("#forms__components__ddl__list A.ddl_alter__link_to_connector_unit").click(function ( event )
	{
		event.preventDefault();
		jQuery("#forms__components__ddl__list [name='do']").val("ddl_alter__link_to_connector_unit");
		jQuery("#forms__components__ddl__list [name='ddl_checklist']").val(jQuery(this).attr("href").replace(/\?/, ""));
		var whoami = acp__components_viewmodule.Form.self = jQuery("#forms__components__ddl__list");
		whoami.trigger("submit"); // Submit the form via AJAX (.js__go_ajax), it will handle itself...
	});

	/**
	 * Handles DDL sorting
	 */
	jQuery("#tables__components__ddl__list TBODY.js__sortable").bind("sortupdate", function ( e, tr )
	{
		var _post_serialized_for__ddl_sorted__with_action = jQuery(this).sortable("serialize") + '&do=ddl_alter__sort';
		var currentConsole = acp__components_viewmodule.Form.scrollToConsole();
		currentConsole.html("Processing... Please wait!").removeClass("error success").addClass("notice");
		jQuery.ajax({
			data    : _post_serialized_for__ddl_sorted__with_action,
			cache   : false,
			success : function ( data )
			{
				if ( data.responseCode == '1' ) {
					currentConsole.html(data.responseMessage).removeClass("notice error").addClass("success").show();
					setTimeout("window.location = window.location.href", 2000);
					return 1;
				}
				else {
					currentConsole.html(data.faultMessage).removeClass("notice success").addClass("error").show();
					return 0;
				}
			}
		});
	});

	/**
	 * "Sr__Create" : Binds onChange event for SELECTs of ".js__trigger_on_change" class
	 */
	jQuery("#forms__components__sr__create").find("SELECT.js__trigger_on_change").change(function ( event )
	{
		acp__components_viewmodule.sr__create__apply_registry(event.currentTarget);
		console.log(event);
	});

	/**
	 * "Sr__Create" : Binds onClick event for LABELs, :radio's and :checkbox'es of ".js__trigger_on_change" class
	 */
	jQuery("#forms__components__sr__create").find(".js__trigger_on_change").filter("LABEL,:radio,:checkbox").click(function ( event )
	{
		var what_is_being_triggered = ( event.currentTarget.is("LABEL") ) ? jQuery("#" + event.currentTarget.attr("for")) : event.currentTarget;
		if ( !( what_is_being_triggered.is(":disabled") ) ) {
			acp__components_viewmodule.sr__create__apply_registry(what_is_being_triggered);
		}
	});

	/**
	 * Resets "Sr-Create" Form to its clean and default state
	 */
	jQuery("#forms__components__sr__create").bind("reset", function ( event )
	{
		/* Prelim */
		var whoami = acp__components_viewmodule.Form.self = jQuery("#forms__components__sr__create");
		var sDataSourceElement = whoami.find("[name='s_data_source']");

		/* Reset common fields */
		whoami.find("[name='do']").val("sr_alter__add"); // 'do'
		whoami.find("[type='submit']").val("Create New Subroutine");
		whoami.find("INPUT[type='text']").val(null); // Reset all INPUT text-fields
		whoami.find("SELECT").each(function ( index ) // Reset all SELECTs
		{
			jQuery(this).find("OPTION:enabled:first").prop("selected", "selected");
		});

		/* Reset errors and close consoles */
		whoami.find(".error").removeClass("error");
		acp__components_viewmodule.Form.closeConsoles(true);

		/* Apply Registry */
		acp__components_viewmodule.sr__create__apply_registry(sDataSourceElement);

		/* Reset jQuery-UI-tabs */
		jQuery(".ui-tabs.sr_alter_add__s_data_flow_config").tabs("option", "active", 0).tabs("option", "disabled", [1, 2, 3]);
		acp__components_viewmodule.Form.resetOnDemandObjects();

		/* Scroll to global Console */
		var currentConsole = acp__components_viewmodule.Form.scrollToConsole(true);
		var defaultConsoleMessage =
			"Please note that:<ul><li>&quot;<strong>Data-source</strong>&quot; tab is disabled, as it is set to &#39;<em>-- no content fetching --</em>&#39;.</li>"
				+ "<li>&quot;<strong>Data-binding</strong>&quot; tab is disabled, as no eligible data-binding options are available for setup.</li>"
				+ "<li>&quot;<strong>Data-target</strong>&quot; tab is disabled, as no eligible data-targetting options are available for setup.</li></ul>";
		currentConsole.html(defaultConsoleMessage).removeClass("error success");

		/* Prevent default "reset" event from occuring... */
		event.preventDefault();

		return;
	});

	/**
	 * Opens "SR-Create" Form
	 */
	jQuery("#forms__components__sr__list [type='button']:first").click(function ( event )
	{
		if ( jQuery("#components__sr__create").is(":hidden") ) {
			/* Close all .ondemand panes */
			jQuery(".section > .ondemand").hide();

			/* Open "SR-Create" form */
			jQuery("#components__sr__create").slideDown("medium", function ()
			{
				/* Reset "Sr-Create" Form */
				jQuery("#forms__components__sr__create").trigger("reset");
			});
		}
	});

	/**
	 * Close "SR-Create" Form
	 */
	jQuery("#forms__components__sr__create .buttons > [type='button']:first").click(function ( event )
	{
		if ( jQuery("#components__sr__create").is(":visible") ) {
			acp__components_viewmodule.Form.closeConsoles();
			jQuery("#components__sr__create").slideUp("medium");
		}
	});

	/**
	 * Handles "Add-query-policy" button
	 */
	jQuery("#forms__components__sr__create").find("BUTTON.buttons__s_fetch_criteria__add_policy").click(function ( event )
	{
		/* Clone the first policy-container and insert the clone after the original one. */
		var htmlToCopy = jQuery("SPAN.s_fetch_criteria__policies:first").html();
		jQuery("<SPAN>").addClass("s_fetch_criteria__policies").html(htmlToCopy).insertAfter(jQuery("SPAN.s_fetch_criteria__policies:last"));
		jQuery("SPAN.s_fetch_criteria__policies:last BUTTON").attr("class", "buttons__s_fetch_criteria__remove_policy").text("remove query-policy");
		jQuery("SPAN.s_fetch_criteria__policies:last TEXTAREA:first").val("1");

		/* Re-enumerate all policy elements */
		var nr_of_policies = jQuery("SPAN.s_fetch_criteria__policies").length;
		for ( i = 1; i < nr_of_policies; i++ ) // No need to touch the first one
		{
			jQuery("SPAN.s_fetch_criteria__policies:eq(" + i + ") TEXTAREA:first")
				.attr("name", "s_fetch_criteria[policies][" + i + "]")
				.attr("id", "s_fetch_criteria__policies__" + i)
				.attr("class", "text required _704-" + i);
			jQuery("SPAN.s_fetch_criteria__policies:eq(" + i + ") LABEL")
				.attr("for", "s_fetch_criteria__policies__" + i)
				.html("Query Policy " + (i + 1));
		}
	});

	/**
	 * Handles "Remove-query-policy" button
	 */
	jQuery("#forms__components__sr__create").on("click", "BUTTON.buttons__s_fetch_criteria__remove_policy", function ( event )
	{
		/* Remove policy-container */
		jQuery(this).parent().remove();

		/* Re-enumerate remaining policy elements */
		var nr_of_policies = jQuery("SPAN.s_fetch_criteria__policies").length;
		for ( i = 1; i < nr_of_policies; i++ )  // No need to touch the first one
		{
			jQuery("SPAN.s_fetch_criteria__policies:eq(" + i + ") TEXTAREA:first")
				.attr("name", "s_fetch_criteria[policies][" + i + "]")
				.attr("id", "s_fetch_criteria__policies__" + i)
				.attr("class", "text required _704-" + i);
			jQuery("SPAN.s_fetch_criteria__policies:eq(" + i + ") LABEL")
				.attr("for", "s_fetch_criteria__policies__" + i)
				.html("Query Policy " + (i + 1));
		}
	});

	/**
	 * Handles "Add-query" button
	 */
	jQuery("#forms__components__sr__create").find("BUTTON.buttons__s_fetch_criteria__add_query").click(function ( event )
	{
		/* Clone the first query-container and insert the clone after the original one. */
		var htmlToCopy = jQuery("#forms__components__sr__create SPAN.s_fetch_criteria__query:first").html();
		jQuery("<SPAN>").addClass("s_fetch_criteria__query").html(htmlToCopy).insertAfter(jQuery("SPAN.s_fetch_criteria__query:last"));
		jQuery("SPAN.s_fetch_criteria__query:last BUTTON").attr("class", "buttons__s_fetch_criteria__remove_query").text("remove the query");

		/* Re-enumerate all query elements */
		var nr_of_queries = jQuery("SPAN.s_fetch_criteria__query").length;
		for ( i = 1; i < nr_of_queries; i++ ) // No need to touch the first one
		{
			jQuery("SPAN.s_fetch_criteria__query:eq(" + i + ") SELECT:eq(0)").attr("name", "s_fetch_criteria[rules][" + i + "][field_name]");
			jQuery("SPAN.s_fetch_criteria__query:eq(" + i + ") SELECT:eq(1)").attr("name", "s_fetch_criteria[rules][" + i + "][math_operator]");
			jQuery("SPAN.s_fetch_criteria__query:eq(" + i + ") SELECT:eq(2)").attr("name", "s_fetch_criteria[rules][" + i + "][type_of_expr_in_value]").attr("class", "_709-" + i);
			jQuery("SPAN.s_fetch_criteria__query:eq(" + i + ") INPUT:eq(0)").attr("name", "s_fetch_criteria[rules][" + i + "][value]").attr("class", "text required _705-" + i);
			jQuery("SPAN.s_fetch_criteria__query:eq(" + i + ") SPAN:last").html("Shortcut: <i>" + ( i + 1 ) + "</i>");
		}
	});

	/**
	 * Handles "Remove-query" button
	 */
	jQuery("#forms__components__sr__create").on("click", "BUTTON.buttons__s_fetch_criteria__remove_query", function ( event )
	{
		/* Remove query-container */
		jQuery(this).parent().remove();

		/* Re-enumerate remaining query elements */
		var nr_of_queries = jQuery("SPAN.s_fetch_criteria__query").length;
		for ( i = 1; i < nr_of_queries; i++ ) // No need to touch the first one
		{
			jQuery("SPAN.s_fetch_criteria__query:eq(" + i + ") SELECT:eq(0)").attr("name", "s_fetch_criteria[rules][" + i + "][field_name]");
			jQuery("SPAN.s_fetch_criteria__query:eq(" + i + ") SELECT:eq(1)").attr("name", "s_fetch_criteria[rules][" + i + "][math_operator]");
			jQuery("SPAN.s_fetch_criteria__query:eq(" + i + ") SELECT:eq(2)").attr("name", "s_fetch_criteria[rules][" + i + "][type_of_expr_in_value]").attr("class", "_709-" + i);
			jQuery("SPAN.s_fetch_criteria__query:eq(" + i + ") INPUT:eq(0)").attr("name", "s_fetch_criteria[rules][" + i + "][value]").attr("class", "text required _705-" + i);
			jQuery("SPAN.s_fetch_criteria__query:eq(" + i + ") SPAN:last").html("Shortcut: <i>" + ( i + 1 ) + "</i>");
		}
	});

	/**
	 * Handles "Add-sorting" button
	 */
	jQuery("#forms__components__sr__create BUTTON.buttons__s_fetch_criteria__add_sorting").click(function ( event )
	{
		/* Clone the first query-container and insert the clone after the original one. */
		var htmlToCopy = jQuery("#forms__components__sr__create SPAN.sr__sort_by:first").html();
		jQuery("<SPAN>").addClass("sr__sort_by").html(htmlToCopy).insertAfter(jQuery("SPAN.sr__sort_by:last"));
		jQuery("SPAN.sr__sort_by:last BUTTON").attr("class", "buttons__s_fetch_criteria__remove_sorting").text("remove the new sorting-rule");

		/* Re-enumerate all query elements */
		var nr_of_rules = jQuery("SPAN.sr__sort_by").length;
		for ( i = 1; i < nr_of_rules; i++ ) // No need to touch the first one
		{
			jQuery("SPAN.sr__sort_by:eq(" + i + ") SELECT:eq(0)").attr("name", "s_fetch_criteria[sort_by][" + i + "][field_name]").attr("class", "_708-" + i);
			jQuery("SPAN.sr__sort_by:eq(" + i + ") SELECT:eq(1)").attr("name", "s_fetch_criteria[sort_by][" + i + "][dir]").attr("class", "_708-" + i);
		}
	});

	/**
	 * Handles "Remove-sorting" button
	 */
	jQuery("#forms__components__sr__create").on("click", "BUTTON.buttons__s_fetch_criteria__remove_sorting", function ( event )
	{
		/* Remove query-container */
		jQuery(this).parent().remove();

		/* Re-enumerate remaining query elements */
		var nr_of_rules = jQuery("SPAN.sr__sort_by").length;
		for ( i = 1; i < nr_of_rules; i++ ) // No need to touch the first one
		{
			jQuery("SPAN.sr__sort_by:eq(" + i + ") SELECT:eq(0)").attr("name", "s_fetch_criteria[sort_by][" + i + "][field_name]").attr("class", "_708-" + i);
			jQuery("SPAN.sr__sort_by:eq(" + i + ") SELECT:eq(1)").attr("name", "s_fetch_criteria[sort_by][" + i + "][dir]").attr("class", "_708-" + i);
		}
	});

	/**
	 * Handles Subroutine Removal
	 */
	jQuery("#forms__components__sr__list").find("A.sr_alter__drop").click(function ( event )
	{
		event.preventDefault();
		jQuery("#forms__components__sr__list").find("[name='s_name']").val(jQuery(this).attr("href").replace(/\?/, ""));

		/* "Are you sure?" */
		acp__components_viewmodule.Form.getDialogObject({
			body    : "You are about to <b>*delete*</b> a module-subroutine! ACTION IS IRREVERSIBLE!!!<br /><br />Shall I proceed?",
			buttons : [
				{
					text  : "Yes, *drop* it!",
					click : function ( event )
					{
						jQuery(this).unbind("dialogclose").dialog("close");
						jQuery("#forms__components__sr__list").find("[name='do']").val("sr_alter__drop");
						var whoami = acp__components_viewmodule.Form.self = jQuery("#forms__components__sr__list");
						whoami.trigger("submit"); // Submit the form via AJAX (.js__go_ajax), it will handle itself...
					}
				},
				{
					text  : "No, stop!",
					click : function ( event )
					{
						jQuery(this).dialog("close");
						acp__components_viewmodule.Form.getDialogObject({
							body    : "Request aborted by user! No actions performed!",
							buttons : [
								{
									text  : "Thanks!",
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
			width   : 500
		}).dialog("open");
	});
});
