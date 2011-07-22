/**
 * Local Javascript object
 *
 * @returns object Object handler
 */
function /* Class */ Acp_Components ()
{
	return {
		constructor : Acp_Components,
		obj : null
	};
};

/* Extending with Persephone */
extend(Acp_Components, Persephone);

/* Instantiating Class handler */
var acp__components = Acp_Components.singleton();

jQuery(document).ready(function ()
{
	/**
	 * Opens "Create-Module" Form.
	 */
	jQuery("#forms__modules__list INPUT[type='button']:eq(0)").click(function ( event )
	{
		if ( jQuery("#components__modules__alter_add").is(":hidden") )
		{
			/* Close all .ondemand panes */
			jQuery("#content UL.section > .ondemand").hide();
		}

		/* Open "DDL-Create" form */
		jQuery("#components__modules__alter_add").slideDown("medium", function ()
		{
			/* Reset "DDL-Create" Form */
			jQuery("#forms__modules__alter_add").trigger("reset");
		});

		/* do = create */
		jQuery("#forms__modules__alter_add [name='do']").val("create");

		/* Make-up */
		jQuery("#components__modules__alter_add H2").html( "Register a Module" ); // Form title
	});

	/**
	 * Resets "Create-Module" Form to its clean and default state.
	 */
	jQuery("#forms__modules__alter_add").bind("reset", function ( event )
	{
		var whoami = acp__components.Form.self = jQuery("#forms__modules__alter_add");
		whoami.find(".error").removeClass("error"); // Resetting errors
		acp__components.Form.closeConsoles();

		/* Cleanup */
		whoami.find("INPUT[type='text']").val(""); // Clear all INPUT text-fields
		whoami.find("SELECT > OPTION").each(function () // Getting all SELECT>OPTION s de-selected
		{
			jQuery(this).get(0).selected = false;
			return;
		});
		whoami.find("INPUT[type='radio']").each(function () // Getting all Radios un-checked
		{
			jQuery(this).get(0).checked = false;
			return;
		});
		jQuery("#forms__modules__alter_add [name='m_unique_id']").val("").prop("disabled","disabled");

		/* Scroll to global Console */
		var currentConsole = acp__components.Form.scrollToConsole(true);
		currentConsole.html("Required fields (in red) must be filled-in!").removeClass("error success").show();

		/* Stop "reset" event from occuring... This is our show :-) */
		event.preventDefault();
	});

	/**
	 * Submits "Create-Module" and "Edit-Module" Forms.
	 */
	jQuery("#forms__modules__alter_add INPUT[type='button']:eq(0)").click(function ( event )
	{
		event.preventDefault();
		var whoami = acp__components.Form.self = jQuery("#forms__modules__alter_add");
		whoami.trigger("submit"); // Submit the form via AJAX (.js__go_ajax), it will handle itself...
	});

	/**
	 * Close "Module-Create" Form.
	 */
	jQuery("#forms__modules__alter_add INPUT[type='button']:eq(1)").click(function ( event )
	{
		if ( jQuery("#components__modules__alter_add").is(":visible") )
		{
			acp__components.Form.closeConsoles();
			jQuery("#components__modules__alter_add").slideUp("medium");
		}
	});

	/**
	 * Prepares "Edit-Module" form.
	 */
	jQuery("FORM#forms__modules__list A.edit").click(function ( event )
	{
		event.preventDefault();

		/* Close all .ondemand panes */
		jQuery("#content UL.section > .ondemand").hide();

		/* The usual... */
		var whoami = acp__components.Form.self = jQuery("#components__modules__alter_add"); // Working form
		var currentConsole = acp__components.Form.scrollToConsole(true);
		whoami.trigger("reset"); // Reset form
		currentConsole.html("Resetting forms and fetching module information! Please stand-by...").removeClass("error success");

		/* Few minor things... */
		var m_unique_id_clean = jQuery(this).attr("href").replace(/^\?/,"").replace( /[^a-z0-9]/gi , "" ).toLowerCase();
		var url = "{{$MODULE_URL}}/components/viewmodule-" + m_unique_id_clean;

		/* Execute */
		jQuery.ajax({
			type : "GET",
			url : url,
			data : null,
			cache : false,
			success : function ( data )
			{
				if ( data.me == undefined )
				{
					var faultMessage = "One or more error(s) occured! Correct them and resubmit the form to continue:<ul><li>No such module (id: " + m_unique_id + ") found!</li></ul>";
					currentConsole.html(faultMessage).removeClass("success").addClass("error");
					return 0;
				}
				else
				{
					/* Make-up */
					jQuery("#components__modules__alter_add H2").html( "Editing Module <em>/" + data.me.m_name + "</em>" ); // Form title

					/* Values */
					var dataset_to_apply = {
						'm_name' : data.me.m_name,
						'm_description' : data.me.m_description,
						'm_extras' : data.me.m_extras,
						'm_enforce_ssl' : data.me.m_enforce_ssl,
						'm_enable_caching' : data.me.m_enable_caching
					};
					jQuery.each(dataset_to_apply, function ( key , value )
					{
						var input_field = jQuery("#forms__modules__alter_add ." + key + " [name^='" + key + "']");
						if ( input_field.length == 1 )
						{
							if ( value != null )
							{
								/* SELECTs... */
								if ( input_field.is("SELECT") )
								{
									if ( !( value instanceof Array ) )
									{
										value = value.split(",");
									}
									input_field.val(value);
								}
								/* Everything else... */
								else if ( input_field.is("INPUT[type='text']") )
								{
									input_field.val(acp__components.Form.decodeHtmlEntities(value));
								}
							}
						}
						else if ( input_field.length > 1 )
						{
							input_field.each(function ( index )
							{
								if ( jQuery(this).is(":radio") )
								{
									/* We have RADIO buttons here */
									jQuery(this).get(0).checked = false; // jQuery-UI: Although this doesn't work, I will leave it here...
									if ( jQuery(this).attr("value") == value )
									{
										jQuery(this).get(0).checked = true; // jQuery-UI: Although this doesn't work, I will leave it here...
										jQuery("LABEL[for='" + jQuery(this).attr("id") + "']").click();
									}
								}
							});
						}
					});

					/* do = edit */
					jQuery("#forms__modules__alter_add [name='do']").val("edit");
					jQuery("#forms__modules__alter_add [name='m_unique_id']").prop("disabled","").val(data.me.m_unique_id);
acp__components.debug(jQuery("#forms__modules__alter_add [name='m_unique_id']"));
					/* Finalize */
					currentConsole.html("Form successfully loaded!").removeClass("error").addClass("success");
					jQuery("#components__modules__alter_add").slideDown("medium");

					return 1;
				}
			}
		});
	});

	/**
	 * Executes "Delete-Module" Request
	 */
	jQuery("#forms__modules__list A.delete").click(function ( event )
	{
		event.preventDefault();
		var m_unique_id = jQuery(this).attr("href").replace(/^\?/,"");

		/* "Are you sure?" */
		acp__components.Form.getDialogObject({
			body : "You are about to <b>delete</b> a module, alongside with its <i>subroutines</i> and all associated data!<br /><b>THIS ACTION IS IRREVERSIBLE!!!</b><br /><br />Shall I proceed?",
			buttons : [
				{
					text : "Yes, *delete* the module, including its *content-base*!",
					click : function ( event )
					{
						jQuery(this).unbind("dialogclose").dialog("close");
						jQuery("#forms__modules__list [name='do']").val("delete");
						jQuery("#forms__modules__list [name='m_unique_id']").val(m_unique_id);
						var whoami = acp__components.Form.self = jQuery("#forms__modules__list");
						whoami.trigger("submit"); // Submit the form via AJAX (.js__go_ajax), it will handle itself...
					}
				},
				{
					text : "No, stop!",
					click : function ( event )
					{
						jQuery(this).dialog("close");
						acp__components.Form.getDialogObject({
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