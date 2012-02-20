/* jQuery Setup */
if ( jQuery != undefined && typeof jQuery == 'function' )
{
	/* jQuery::noConflict() */
	jQuery.noConflict();

	/* HTML-5 support for IE8 and earlier */
	/*
	if ( jQuery.support.leadingWhitespace == false ) // This =false in IE 6-8
	{
		document.createElement("header");
		document.createElement("footer");
		document.createElement("section");
		document.createElement("aside");
		document.createElement("nav");
		document.createElement("article");
		document.createElement("hgroup");
		document.createElement("time");
	}
	*/

	/* AJAX setup */
	jQuery.ajaxSetup({
		async : true,
		cache : true,
		data : "",
		dataType : "json",
		global : true,
		ifModified : true,
		timeout : 15000,
		type : "POST",
		url : window.location.href,

		error : function ( jqXHR , textStatus , errorThrown )
		{
			if ( persephone.Form.currentConsole != undefined && persephone.Form.currentConsole )
			{
				persephone.Form.currentConsole.html("Unspecified error occured! Possible causes are <i>connection problems</i> or <i>invalid response from server</i>.").removeClass("success").addClass("error");
			}
			return 0;
		}
	});
}

/**
 * Basic extend function.
 */
function extend ( B , A )
{
	// Intermediate function
	function I () {};

	// The rest...
	I.prototype = A.prototype;
	B.prototype = new I;
	B.prototype.constructor = B;
	B.prototype.parent = A;
}

/**
 * Javascript Singleton pattern.
 */
Function.prototype.singleton = function ()
{
	if ( this._singleton === undefined )
	{
		// Create an Intermediate constructor to avoid problems during initialization of the generic construct itself
		function I () {};

		// Assign the same prototype to extend itself
		I.prototype = this.prototype;

		// Create a singleton instance
		this._singleton = new I;

		// No need to re-declare the instance constructor, as we are extending the contructor itself. Let's call it with apply();
		this.apply(this._singleton, arguments);
	}

	return this._singleton;
};

/**
 * Checks whether given arrays are the same.
 *
 * @param {Array} First array.
 * @param {Array} Second array.
 * @returns {Boolean} TRUE if both arrays are the same, FALSE otherwise.
 *
 * @see http://stackoverflow.com/questions/784012/javascript-equivalent-of-phps-in-array
 */
Persephone.prototype.arrayCompare = function ( a1 , a2 )
{
	if ( a1.length != a2.length )
	{
		return false;
	}
	var length = a2.length;
	for ( var i = 0; i < length; i++ )
	{
		if ( a1[i] !== a2[i] )
		{
			return false;
		}
	}
    return true;
};

/**
 * Checks if a value exists in an array.
 *
 * @param {Mixed} The searched value.
 * @param {Array} The haystack-array.
 * @param {Boolean} Optional: If TRUE then the types of the needle will also be checked. Defaults to FALSE.
 * @returns {Boolean} TRUE if needle is found in the array, FALSE otherwise.
 *
 * @see http://stackoverflow.com/questions/784012/javascript-equivalent-of-phps-in-array
 */
Persephone.prototype.inArray = function ( needle , haystack /* , strict */ )
{
	var length = haystack.length;
	var strict = false;
	if ( arguments[2] != undefined && arguments[2] == true )
	{
		strict = true;
	}
	for ( var i = 0; i < length; i++ )
	{
		if ( typeof haystack[i] == 'object' )
		{
			if ( persephone.arrayCompare(haystack[i], needle) )
			{
				return true;
			}
		}
		else
		{
			if ( strict )
			{
				if ( haystack[i] === needle )
				{
					return true;
				}
			}
			else
			{
				if ( haystack[i] == needle )
				{
					return true;
				}
			}
		}
	}
	return false;
};

/**
 * Creates a copy/clone of an object.
 * @param    {Object}  Object to copy/clone
 * @returns  {Object}  New object
 * @usage    var newObj = persephone.cloneObject( objectToClone );
 */
Persephone.prototype.cloneObject = function ( objectToClone )
{
	var newObj = (objectToClone instanceof Array) ? [] : {};
	for ( i in objectToClone )
	{
		if ( objectToClone[i] && typeof objectToClone[i] == "object" )
		{
			newObj[i] = persephone.cloneObject(objectToClone[i]);
		}
		else
		{
			newObj[i] = objectToClone[i];
		}
	}

	return newObj;
};

/**
 * Checks whether the given object is empty or not.
 *
 * @returns {Mixed} TRUE if the object is empty, FALSE if not; undefined if error occurs.
 */
Persephone.prototype.isEmptyObject = function ( object )
{
	/* Return undefined if we are dealing with non-object. */
	if ( typeof object != 'object' )
	{
		return undefined;
	}

	/**
	 * jQuery Objects
	 */
	if ( object instanceof jQuery )
	{
		return object.length === 0 ? true : false;
	}

	/**
	 * @deprecated Since Javascript 1.8.5
	 * @see https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/Object
	 */
	if ( object.__count__ !== undefined )
	{
		return object.__count__ === 0 ? true : false;
	}

	/* Less-aesthetic method, if above method fails */
	for ( var property in object )
	{
		if ( object.hasOwnProperty(property) )
		{
			return false;
		}
	}
	return true;
};

/**
 * Persephone main Javascript object.
 *
 * @returns {Object} Object handler.
 */
function /* Class */ Persephone ()
{
	return {
		constructor : Persephone
	};
}

/* Instantiating Persephone object */
var persephone = Persephone.singleton();

/* Public methods */

/**
 * Tries to output a debug message/object to Window-console.
 *
 * @param mixed Message/object to output.
 * @returns void
 */
Persephone.prototype.debug = function ( item )
{
	if ( window.console && typeof window.console.log == 'function' )
	{
		window.console.log(item);
	}
};

Persephone.prototype.Form = {
	/**
	 * Working form object.
	 * @var {Mixed}
	 */
	self : null,

	/**
	 * jQuery-UI Dialog instance
	 * @var {Mixed}
	 */
	dialogInstance : null,

	/**
	 * Returns jQuery Dialog instance
	 * @returns {jQuery} jQuery Dialog instance
	 */
	getDialogObject : function ( newOptions )
	{
		/* Options */
		var options = {
			autoOpen : false,
			buttons : [
				{
					text : "Ok",
					click : function ( event )
					{
						jQuery(this).dialog("close");
					}
				}
			],
			closeOnEscape : true,
			maxHeight : (2 * jQuery(window).height() / 3),
			maxWidth : (2 * jQuery(window).width() / 3),
			modal : true,
			stack : true,
			zIndex : 1000,

			title : "Please confirm...",
			body : "Are you sure?"
		};
		if ( typeof newOptions == 'object' && !persephone.isEmptyObject(newOptions) )
		{
			jQuery.extend(true, options, newOptions);  // 'true' means we merge two arrays and assign the resulting value to the first variable
		}

		/* Do we have a valid instance? If so, return it; otherwise, create new one. */
		if ( this.dialogInstance === null )
		{
			/* Instantiate */
			this.dialogInstance = jQuery("<div></div>").dialog(options).html(newOptions['body']);
		}
		else
		{
			/* Set title and body */
			this.dialogInstance.dialog("option", options).html(newOptions['body']);
		}

		return this.dialogInstance;
	},

	/**
	 * Scrolls the page to the to the "top" of Console
	 *
	 * @returns {Mixed} [jQuery] jQuery object (current canvas) on success, [Boolean] FALSE otherwise
	 * @uses jQuery
	 */
	scrollToConsole : function ( enforceGlobalConsole )
	{
		if ( jQuery === undefined || typeof jQuery != 'function' )
		{
			return false;
		}

		var currentConsole = {};

		/* Is "self" set? */
		if ( !enforceGlobalConsole && this.self !== null )
		{
			/* We need to convert our object to jQuery object, if it's not already so. */
			if ( !(this.self instanceof jQuery) )
			{
				this.self = jQuery(this.self);
			}

			/* Find the "local" console */
			currentConsole = this.self.find(".system_console");
		}

		/* Revert to the "global" console if no "local" one was found */
		if ( enforceGlobalConsole || persephone.isEmptyObject( currentConsole ) )
		{
			currentConsole = jQuery("#system_console");
		}

		/* Reveal console */
		currentConsole.show();

		/* If the current console is above the window canvas, scroll up to it; otherwise, scroll down;
		 * If enforceGlobalConsole = true, always scroll to console. */
		var consoleTop = parseInt(currentConsole.offset().top);
		var consoleBottom = parseInt(currentConsole.offset().top + currentConsole.height());
		var canvasTop = parseInt(jQuery("html,body").scrollTop());
		var canvasBottom = parseInt(jQuery("html,body").scrollTop() + jQuery(window).height());
		if ( enforceGlobalConsole || consoleTop < canvasTop )
		{
			jQuery("html,body").animate({
				scrollTop : consoleTop
			}, 500);
		}
		else if ( consoleBottom > canvasBottom )
		{
			jQuery("html,body").animate({
				scrollTop : consoleBottom
			}, 500);
		}

		return currentConsole;
	},

	/**
	 * Closes all Consoles
	 *
	 * @returns {Void}
	 * @uses jQuery
	 */
	closeConsoles : function ( excludeGlobalConsole )
	{
		if ( jQuery === undefined || typeof jQuery != 'function' )
		{
			return false;
		}
		excludeGlobalConsole ? jQuery(".system_console").hide() : jQuery("#system_console, .system_console").hide();
		return;
	},

	/**
	 * Hides all .ondemand elements
	 *
	 * @param {String} Optional: What to exclude (DOM elements)
	 * @return {Boolean} TRUE on success, FALSE otherwise (working form not defined or no matching DOM objects found)
	 * @uses jQuery
	 */
	resetOnDemandObjects : function ( exceptions )
	{
		if ( jQuery === undefined || typeof jQuery != 'function' )
		{
			return false;
		}

		/* Is "self" set? */
		if ( this.self === null )
		{
			return false;
		}

		/* We need to convert our object to jQuery object, if it's not already so. */
		if ( !(this.self instanceof jQuery) )
		{
			this.self = jQuery(this.self);
		}

		/* Hide containers [fieldsets] */
		var _obj = this.self.find(".ondemand");

		/* No matching elements? */
		if ( _obj.length == 0 )
		{
			return false;
		}

		if ( exceptions )
		{
			_obj = _obj.not(exceptions);
		}

		/* Disable form elements within those containers */
		_obj.find("INPUT,TEXTAREA,BUTTON,SELECT").prop("disabled","disabled");

		/* Hide the container(s) [fieldsets] */
		_obj.hide();

		return true;
	},

	/**
	 * Enables/reveals/shows requested .ondemand element
	 *
	 * @param {Mixed} Requested object: of type {jQuery} or otherwise
	 * @return {Boolean} TRUE on success, FALSE otherwise (working form not defined or no matching DOM objects found)
	 * @uses jQuery
	 */
	enableOnDemandElement : function ( obj /* , enableImmediateChildrenOnly = false */ )
	{
		if ( jQuery === undefined || typeof jQuery != 'function' )
		{
			return false;
		}

		/* We need to convert our object to jQuery object, if it's not already so. */
		if ( !(obj instanceof jQuery) )
		{
			obj = jQuery(obj);
		}

		/* No matching elements? */
		if ( obj.length == 0 )
		{
			return false;
		}

		/* First show those containers */
		obj.show();

		/* Then enable the disabled form elements within the container(s) [fieldsets] */
		if ( arguments[1] !== undefined && arguments[1] === true )
		{
			obj.children(":input").removeAttr("disabled");
			obj.children("SPAN.input").children(":input").removeAttr("disabled");
		}
		else
		{
			obj.find(":input").removeAttr("disabled");
		}

		return true;
	},

	/**
	 * Resets/hides/disables requested .ondemand element
	 *
	 * @param {Mixed} Requested object: of type {jQuery} or otherwise
	 * @return {Boolean} TRUE on success, FALSE otherwise (working form not defined or no matching DOM objects found)
	 * @uses jQuery
	 */
	resetOnDemandElement : function ( obj )
	{
		if ( jQuery === undefined || typeof jQuery != 'function' )
		{
			return false;
		}

		/* We need to convert our object to jQuery object, if it's not already so. */
		if ( !(obj instanceof jQuery) )
		{
			obj = jQuery(obj);
		}

		/* No matching elements? */
		if ( obj.length == 0 )
		{
			return false;
		}

		/* First disable the form elements within those containers */
		obj.find("INPUT,TEXTAREA,BUTTON,SELECT").prop("disabled","disabled");

		/* Then hide the container(s) [fieldsets] */
		obj.hide();

		return true;
	},

	decodeHtmlEntities : function ( obj )
	{
		if ( obj instanceof Array )
		{
			for ( i in obj )
			{
				obj[i] = this.decodeHtmlEntities(obj[i]);
			}
		}
		else
		{
			obj = obj.replace("&#38;", "&");
			obj = obj.replace("&#60;", "<");
			obj = obj.replace("&#62;", ">");
			obj = obj.replace("&#34;", "\"");
			obj = obj.replace("&#39;", "'");
			obj = obj.replace("&#33;", "!");
			obj = obj.replace("&#36;", "$");
			obj = obj.replace("&#46;&#46;/", "../");
			obj = obj.replace("&lt;", "<");
			obj = obj.replace("&gt;", ">");
			obj = obj.replace("&quot;", "\"");
		}
		return obj;
	}
};

/**
 * Modal windows : Masking the Window/Document
 *
 * @returns {Mixed} [Number] Mask z-index value on success, [Boolean] FALSE otherwise
 * @uses jQuery
 */
Persephone.prototype.dimLights = function ()
{
	if ( jQuery === undefined || typeof jQuery != 'function' )
	{
		return false;
	}
	jQuery("#matte").css({
		"width" : jQuery(window).width(),
		"height" : jQuery(document).height()
	}).attr("title", "Click to close...").fadeIn("medium").fadeTo("medium", 0.8);
	return jQuery("#matte").css("z-index");
};

/**
 * Modal windows : Removing the mask
 *
 * @returns {Boolean} TRUE on success, FALSE otherwise
 * @uses jQuery
 */
Persephone.prototype.unDimLights = function ()
{
	if ( jQuery === undefined || typeof jQuery != 'function' )
	{
		return false;
	}
	jQuery(".modal, #matte").hide();
	return true;
};

if ( jQuery != undefined && typeof jQuery == 'function' )
{
	jQuery(document).ready(function ( event )
	{
		jQuery("#matte").click(Persephone.unDimLights);

		jQuery(document).on("click", "A.js__go_ajax", function ( event )
		{
			event.preventDefault();
			var url = jQuery(this).attr("href");
			var currentConsole = persephone.Form.scrollToConsole();

			/* Find FORM container for this Anchor-element */
			if ( 'function' == typeof jQuery().metadata )  // Is jQuery.metadata() installed?
			{
				var formToBeUpdated = jQuery(this).metadata().whoami;
			}
			var parentForm = jQuery(this).parent();
			while ( !parentForm.is("FORM, BODY") && parentForm != undefined )  // If we reached BODY, then stop loop.
			{
				parentForm = parentForm.parent();
			}

			/* No FORM's? Exit. */
			if ( !parentForm.is("FORM") )
			{
				return false;
			}

			var jqXHR = jQuery.ajax({
				url : url,
				data : parentForm.serialize(),
				cache : false,
				success : function ( data )
				{
					/* responseCode ... */
					if ( 'responseCode' in data && data.responseCode == '1' )
					{
						/* ... with responseMessage */
						if ( 'responseMessage' in data && data.responseMessage != '' )
						{
							currentConsole.html(data.responseMessage).removeClass("error").addClass("success");
						}
						/* ... without responseMessage */
						else if ( 'responseMessage' in data && data.responseMessage == '' )
						{
							persephone.Form.closeConsoles();
						}
						currentConsole.removeClass("notice error").addClass("success");

						/* ... with responseAction */
						if ( 'responseAction' in data )
						{
							var _responseActionMatch = data.responseAction.match(/refresh(?::(\d+))?/);

							var _refreshIn = 2000;
							if ( _responseActionMatch[1] )
							{
								_refreshIn = parseInt(_responseActionMatch[1]);
							}

							if ( data.responseMessage == '' )
							{
								currentConsole.html("Success! Refreshing...").removeClass("error").addClass("success");
							}

							setTimeout( "window.location = window.location.href", _refreshIn );
						}
						return 1;
					}
					/* Single faultCode */
					else if ( 'faultMessage' in data )
					{
						currentConsole.html(data.faultMessage).removeClass("success").addClass("error");
						return 0;
					}
					/* Multiple faultCodes */
					else if ( data.length )
					{
						var _faultMessages = [];
						jQuery.each(data, function ( key , value )
						{
							whoami.find("._" + value['faultCode']).addClass("error");
							_faultMessages.push("<li>" + value['faultMessage'] + "</li>");
						});
						var _faultMessagesParsed = "One or more error(s) occured! Correct them and resubmit the form to continue:<ul>" + _faultMessages.join("") + "</ul>";
						currentConsole.html(_faultMessagesParsed).removeClass("success").addClass("error");
						return 0;
					}
				}
			});

			return jQuery.parseJSON(jqXHR.responseText);
		});

		jQuery(document).on("click", "INPUT.js__go_dialogue", function ( event )
		{

		});

		jQuery(document).on("submit", "FORM.js__go_ajax", function ( event )
		{
			event.preventDefault();
			var whoami = persephone.Form.self = jQuery(this);
			whoami.find(".error").removeClass("error");
			persephone.Form.closeConsoles();
			var currentConsole = persephone.Form.scrollToConsole();
			currentConsole.html("Processing... Please wait!").removeClass("error success");
			var jqXHR = jQuery.ajax({
				url : whoami.attr("action") == '' ? window.location.href : whoami.attr("action"),
				data : whoami.serialize(),
				cache : false,
				success : function ( data )
				{
					/* responseCode ... */
					if ( typeof data == 'object' && 'responseCode' in data && data.responseCode == '1' )
					{
						/* ... with responseMessage */
						if ( 'responseMessage' in data && data.responseMessage != '' )
						{
							currentConsole.html(data.responseMessage).removeClass("error").addClass("success");
						}
						/* ... without responseMessage */
						else if ( 'responseMessage' in data && data.responseMessage == '' )
						{
							persephone.Form.closeConsoles();
						}
						currentConsole.removeClass("notice error").addClass("success");

						/* ... with responseAction */
						if ( 'responseAction' in data )
						{
							var _responseActionMatch = data.responseAction.match(/refresh(?:\:(\d+))?/);

							var _refreshIn = 2000;
							if ( _responseActionMatch[1] )
							{
								_refreshIn = parseInt(_responseActionMatch[1]);
							}

							if ( data.responseMessage == '' )
							{
								currentConsole.html("Success! Refreshing...").removeClass("error").addClass("success");
							}

							setTimeout( "window.location = window.location.href", _refreshIn );
						}
						return 1;
					}
					/* Single faultCode */
					else if ( typeof data == 'object' && 'faultMessage' in data )
					{
						currentConsole.html(data.faultMessage).removeClass("success").addClass("error");
						return 0;
					}
					/* Multiple faultCodes */
					else if ( typeof data == 'object' && data.length )
					{
						var _faultMessages = [];
						jQuery.each(data, function ( key , value )
						{
							whoami.find("._" + value['faultCode']).addClass("error");
							_faultMessages.push("<li>" + value['faultMessage'] + "</li>");
						});
						var _faultMessagesParsed = "One or more error(s) occured! Correct them and resubmit the form to continue:<ul>" + _faultMessages.join("") + "</ul>";
						currentConsole.html(_faultMessagesParsed).removeClass("success").addClass("error");
						return 0;
					}
				}
			});

			return jQuery.parseJSON(jqXHR.responseText);
		});

		// Do we have accordion() function defined? If so, apply it accordingly.
		if ( 'function' == typeof jQuery().accordion )
		{
			jQuery(".ui-accordion").accordion({
				active : false,
				animated : 'easeslide',
				autoHeight : false,
				clearStyle : false,
				collapsible : true,
				icons : {
					'header' : "",
					'headerSelected' : ""
				},
				navigation : true
			});
		}

		// Do we have accordion() function defined? If so, apply it accordingly.
		if ( 'function' == typeof jQuery().button )
		{
			jQuery(".buttons").find("INPUT[type='submit'],INPUT[type='button'],INPUT[type='reset'],BUTTON").button();
		}

		// Do we have buttonset() function defined? If so, apply it accordingly.
		if ( 'function' == typeof jQuery().buttonset )
		{
			jQuery(".ui-buttonset").buttonset();
		}

		// Do we have sortable() function defined? If so, apply it accordingly.
		if ( 'function' == typeof jQuery().sortable )
		{
			jQuery("TABLE TBODY.js__sortable").sortable({
				axis : 'y',
				// containment: 'parent',
				cursor : 'move',
				delay : 300,
				helper : function ( e , tr )
				{
					var _originals = tr.children();
					var _helper = tr.clone();
					_helper.children().each(function ( index )
					{
						// Set helper cell sizes to match the original sizes
						jQuery(this).width(_originals.eq(index).width());
					});
					return _helper;
				}
			}).disableSelection();

			// jQuery("TABLE TBODY.js__sortable").bind("sortstop", function ( e , tr )
			// {
			// 	console.log(jQuery(this).sortable("serialize"));
			// });
		}

		// Do we have tablesorter() function defined? If so, apply it accordingly.
		// Note: jQuery.tablesorter() already has jQuery.metadata() integrated, so we don't need to do that again!
		if ( 'function' == typeof jQuery().tablesorter )
		{
			jQuery("TABLE.tablesorter").tablesorter({
				widgets : [
					'zebra'
				],
				widthFixed : true
			});
		}

		// Do we have tabs() function defined? If so, apply it accordingly.
		if ( 'function' == typeof jQuery().tabs )
		{
			/* Options */
			var options = {
				collapsible : false,
				cookie : {
					expires : 365
				},
				event : "click"
			};

			jQuery(".ui-tabs").each(function ( index , element )
			{
				/**
				 * Checks for custom options
				 * @uses jQuery.metadata()
				 */
				if ( 'function' == typeof jQuery().metadata )  // Is jQuery.metadata() installed?
				{
					var newOptions = jQuery(this).metadata().tabs;
					if ( typeof newOptions == 'object' && !persephone.isEmptyObject(newOptions) )
					{
						jQuery.extend(true, options, newOptions);  // 'true' means we merge two arrays and assign the resulting value to the first variable
					}
				}
				jQuery(this).tabs(options);
			});
		}
	});

	/**
	 * Prevent memory leaks in IE; and prevent errors on refresh with events like mouseover in other browsers; Window
	 * isn't included so as not to unbind existing unload events
	 *
	 * @see http://stackoverflow.com/questions/57034/jquery-and-java-applets
	 */
	jQuery(window).unload(function ( event )
	{
		jQuery("*:not('APPLET,OBJECT')").add(document).unbind();
	});

	/**
	 * "On-Window-Scroll"
	 */
	jQuery(window).scroll(function ( event )
	{
		if ( jQuery("#jumpLoaderApplet") && jQuery("#jumpLoaderApplet").is(":visible") )
		{
			jQuery("#jumpLoaderApplet")
				.css("top", (jQuery(window).height() - jQuery("#jumpLoaderApplet").height()) / 2 + jQuery(document).scrollTop())
				.css("left", (jQuery(window).width() - jQuery("#jumpLoaderApplet").width()) / 2);
		}
	});

	/**
	 * "On-Window-Resize"
	 */
	jQuery(window).resize(function ()
	{
		if ( jQuery("#jumpLoaderApplet") && jQuery("#jumpLoaderApplet").is(":visible") )
		{
			jQuery("#jumpLoaderApplet")
				.css("top", (jQuery(window).height() - jQuery("#jumpLoaderApplet").height()) / 2 + jQuery(document).scrollTop())
				.css("left", (jQuery(window).width() - jQuery("#jumpLoaderApplet").width()) / 2);
		}
		jQuery("#matte").css({"width" : jQuery(window).width()});
	});
}