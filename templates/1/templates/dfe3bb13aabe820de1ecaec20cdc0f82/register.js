{{if ! $CONFIG.security.no_reg}}
$( document ).ready(
	function() {
		// Load reCAPTCHA library
		$('TEXTAREA.tinymce').tinymce({
			// Location of TinyMCE script
			script_url : '/public/js/tiny_mce/tiny_mce.js',

			// General options
			theme    : "advanced",
			readonly : true,
			plugins  : "noneditable"
		});

		$("#system_console").html("Required fields (in red) must be filled-in!").removeClass("error success").addClass("notice").show();

		// Submit "Register"

		$("FORM#forms__register").submit(
			function(event){
				event.preventDefault();
				$("#system_console").html("Processing... Please wait!").removeClass("error success").addClass("notice").slideDown("fast");
				$("FORM#forms__register *").removeClass("error");
				var consoleTop = $("#system_console").offset().top;
				$("html,body").animate({scrollTop : consoleTop } , 500);
				// Action
				$_url = window.location.href + "?output=json";
				$.ajax({
					type:"POST",
					url:$_url,
					dataType:"json",
					data:$("FORM#forms__register").serialize(),
					cache:false,
					timeout:15000,
					error:function( xhr, text_status, error ){
						$("#system_console")
							.html("Unspecified error occured! Possible causes are <i>connection problems</i> or <i>invalid response from server</i>.")
							.removeClass("notice success").addClass("error");
						return 0;
					},
					success:function( data )
					{
						if ( data.responseCode )
						{
							$("#system_console").html( data.responseMessage ).removeClass("notice error").addClass("success");
							var timeOut = data.responseCode == '3' ? 2000 : 15000;
							setTimeout( "window.location = \"" + data.extra + "\"", timeOut );
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
										$("FORM#forms__register INPUT[name='username']").addClass("error");
										break;
									case 702:
										$("FORM#forms__register INPUT[name='display_name']").addClass("error");
										break;
									case 703:
										$("FORM#forms__register INPUT[name='email']").addClass("error");
										break;
									case 704:
										$("FORM#forms__register INPUT[name='email_repeat']").addClass("error");
										break;
									case 705:
										$("FORM#forms__register INPUT[name='password']").addClass("error");
										break;
									case 706:
										$("FORM#forms__register INPUT[name='password_repeat']").addClass("error");
										break;
									case 707:
										$("FORM#forms__register INPUT#tos_agree_checkbox").addClass("error");
										break;
								}
								faultMessageList.push( "<li>" + data[ i ].faultMessage + "</li>" );
							}
							faultMessageParsed = "One or more error(s) occured! Correct them and resubmit the form to continue:<ul>" + faultMessageList.join("") + "</ul>"
							$("#system_console").html( faultMessageParsed ).removeClass("notice success").addClass("error").show();
							Recaptcha.reload();
							return 0;
						}
					}
				});
			}
		);
	}
);
{{/if}}