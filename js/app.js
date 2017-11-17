$(function(){
	const OCCUPIED = 1;
	// endpoint
	var endpoint = "./sys/";
	var currentBuildings = [];
	var visibleBuildings = [];
	var initialized = false;
	var pollingSpeed = 5000;

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
		//console.log(currentBuildings);

		if(currentBuildings.length > 0) {
			initialized = true;
			// ***** Improve this in the future to only get data for visible buildings, getting all for now
			visibleBuildings = currentBuildings;
		}

		setInterval(function() {
			if(initialized) {
				visibleBuildings.forEach(function(buildingID) {
				    getCurrentBuildingRoomStates(buildingID);
				});			
			}		    
		}, pollingSpeed);
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
		//console.log(rtn);
		//parse building list
		var firstItemClass = 'is-active';
		rtn.buildings.forEach(function(building){
			if(currentBuildings.indexOf(building.id) < 0) {
				currentBuildings.push(building.id);
			}
			var slide_string = '<div class="slide" data-building-id="'+building.id+'"><h2>'+building.name+' <small>(Building '+building.number+')</small></h2>';
				slide_string += '<div class="floorplan-svg">';
					slide_string += '<?xml version="1.0" encoding="utf-8"?>';
					slide_string += '<!-- Generator: Adobe Illustrator 21.0.2, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->';
					slide_string += building.svg;
				slide_string += '</div>';
			slide_string += '</div>';

			var accordion_string = '<li class="accordion-item ' + firstItemClass + '" data-accordion-item>';
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
			firstItemClass = '';
		});

		//go go gadget UI
		initializeUI();
	});//end ajax request


	function getCurrentBuildingRoomStates(buildingid) {
		// grab room status data
		$.ajax({
			method: "GET",
			url: endpoint,
			data: {
				method: 'site'
				,request: 'getBuildingSensorData'
				,id : buildingid 
			}
		}).done(function(rtn) {
			//console.log(rtn);
			rtn.sensors.forEach(function(sensor){
				var currentDoor = $('[data-door-id="' + sensor.id + '"]');
				var currentDoorSlider = document.querySelector('#bathroom_' + buildingid + '_' + sensor.id);
				if(sensor.state == OCCUPIED && !currentDoor.find('.status').hasClass('occupied')) {
					// Update to active
					currentDoorSlider.classList.add('occupied');
					currentDoor.addClass('occupied');
				} else if(sensor.state != OCCUPIED && currentDoor.find('.status').hasClass('occupied')){
					// Update to inactive			
		            currentDoorSlider.classList.removeClass('occupied');
					currentDoor.removeClass('occupied');
		        }
			});
		});//end ajax request
	}
});