jQuery(document).ready(function()
{
	/**
	 * Submits "Edit" Request
	 */
	jQuery(".forms__settings__edit INPUT[type='submit']").click(function ( event )
	{
		jQuery(this).parent().parent().find("[name='do']").val("edit");
		whoami.trigger("submit"); // Submit the form via AJAX (.js__go_ajax), it will handle itself...
	});

	/**
	 * Submits "Revert" Request
	 */
	jQuery(".forms__settings__edit A.revert").click(function ( event )
	{
		event.preventDefault();
		var whoami = jQuery(this).parent().parent();
		whoami.find("[name='do']").val("revert");
		whoami.find("[name='revert__for']").val(jQuery(this).attr("href").replace(/\?revert__for_/,""));
		whoami.trigger("submit"); // Submit the form via AJAX (.js__go_ajax), it will handle itself...
	});
});