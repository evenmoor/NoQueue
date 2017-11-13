$(function(){
	// endpoint
	var endpoint = "./sys/";

	function initializeUI(){
		//initialize foundation
		$(document).foundation();

		//initialize slider
		$('[data-building-slider]').on('beforeChange', function(evt, slick, cur, nxt){
			var building_id = $('[data-slick-index="' + nxt + '"]').attr('data-building-id');
			$('[data-building-accordion]').find('[data-building-id="' + building_id + '"]').click();
		});

		$('[data-building-slider]').slick({
			slidesToShow : 1,
			dots : true,
			arrows : false
		});
	}//end initializeUi

	// grab area data
	$.ajax({
		method: "GET",
		url: endpoint,
		data: {
			method: 'site'
			,request: 'getAreaData'
			,id : 1 
		}
	}).done(function(rtn) {
		console.log(rtn);
		//parse building list
		rtn.buildings.forEach(function(building){
			var slide_string = '<div class="slide" data-building-id="'+building.id+'"><h2>'+building.name+' <small>(Building '+building.number+')</small></h2>';
				slide_string += '<div class="floorplan-svg">';
					slide_string += '<?xml version="1.0" encoding="utf-8"?>';
					slide_string += '<!-- Generator: Adobe Illustrator 21.0.2, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->';
					slide_string += building.svg;
				slide_string += '</div>';
			slide_string += '</div>';

			var accordion_string = '<li class="accordion-item" data-accordion-item>';
				accordion_string += '<a href="#" class="accordion-title" data-building-id="'+building.id+'">'+building.name+' <small>('+building.number+')</small></a>';
				accordion_string += '<div class="accordion-content" data-tab-content>';
					accordion_string += '<div class="row small-up-2">';
						accordion_string += '<div class="column column-block">';
							building.sensors.forEach(function(sensor){
								accordion_string += '<div class="item pop-action" data-door-id="'+sensor.id+'" data-bathroom-id="bathroom_'+building.id+'_'+sensor.id+'">';
									accordion_string += '<button class="bathroom-label" data-open="bathroom_'+building.id+'_'+sensor.id+'_modal">'+sensor.name+'</button>';
									accordion_string += '<div class="status"></div>';
									accordion_string += '<div class="time-estimate"></div>';
								accordion_string += '</div>';
							});
						accordion_string += '</div>';
					accordion_string += '</div>';
				accordion_string += '</div>';
			accordion_string += '</li>';

			$('[data-building-slider]').append(slide_string);
			$('[data-accordion]').append(accordion_string);
		});

		//go go gadget UI
		initializeUI();
	});//end ajax request
});