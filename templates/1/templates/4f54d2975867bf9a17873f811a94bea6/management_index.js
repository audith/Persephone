function tablesorter__module_list ()
{
	$("LI#media_library TABLE")
		.tablesorter(
			{
				/*
				sortList:[[0,0]],
				*/
				headers:{
					0:{ sorter:false },
					1:{ sorter:false },
					2:{ sorter:false },
					3:{ sorter:false },
					4:{ sorter:false },
					5:{ sorter:false }
				},
				widgets: ['zebra'],
				widthFixed: true
			}
		);
}

$( document ).ready(
	function() {
		// Tablesorting...

		tablesorter__module_list();

		// "DDL Management" action

		$("FORM#forms__modules__list INPUT[type='button']:eq(0)").click(
			function(event){
				m_unique_id = $("FORM#forms__modules__list INPUT[name='m_unique_id']:checked").val();
				if ( ! m_unique_id )
				{
					$("SAMP#system_console").html("No module was selected! Please select one...").removeClass("notice success").addClass("error").slideDown();
					return 0;
				}
				m_unique_id_clean = m_unique_id.replace( /[^a-z0-9]/gi , "" ).toLowerCase();
				window.location = "{{$MODULE_URL}}/management/content-" + m_unique_id_clean;
			}
		);
		
		$("TABLE#tables__media_library TBODY TR TD.w_preview_hover").mouseover(
			function ( event )
			{
				event.stopPropagation();
				var _media_id = $(this).parent().attr("id");
				if ( _media_id != null )
				{
					$("DIV#media_library_sneak_peek").css( "background-image","url('/static/stream/s-" + _media_id + "')" );
				}
				
				$(this).mousemove(
					function(event)
					{
						if ( event.pageY > 0 && event.pageX > 0 && event.pageX < $(document).width() && event.pageY < $(document).height() )
						{
							$("DIV#media_library_sneak_peek")
								.css( "position","absolute" )
								.css( "top",event.pageY )
								.css( "left",event.pageX + 10 ).show( 0 )
								.show( 0 );
						}
					}
				);
				
				$(this).mouseout(
					function(event)
					{
						$("DIV#media_library_sneak_peek").hide();
					}
				);
			}
		);
	}
);