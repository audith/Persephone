jQuery(document).ready(function()
{
	/**
	 * Submits "Edit" Request
	 */
	jQuery("#forms__settings__edit").submit(function ( event )
	{
		event.preventDefault();
		jQuery(this).find("[name='do']").val("edit");
		jQuery(this).submit();
	});

	/**
	 * Submits "Revert" Request
	 */
	jQuery("#forms__settings__edit A.revert").click(function ( event )
	{
		event.preventDefault();
		var whoami = jQuery("#forms__settings__edit");
		whoami.find("[name='do']").val("revert");
		whoami.find("[name='revert__for']").val(jQuery(this).attr("href").replace(/\?revert__for_/,""));
		whoami.trigger("submit");
	});
});