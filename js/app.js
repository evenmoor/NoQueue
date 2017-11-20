$(function(){
	const OCCUPIED = 1;
	// endpoint
	var endpoint = "./sys/";
	var currentBuildings = [];
	var visibleBuildings = [];
	var initialized = false;
	var pollingSpeed = 5000;
	var skipevent = false;

	function initializeUI(){
		//initialize foundation
		$(document).foundation();

		//initialize slider
		$('[data-building-slider]').on('beforeChange', function(evt, slick, cur, nxt){
			var building_id = $('[data-slick-index="' + nxt + '"]').attr('data-building-id');
			skipevent = true;
			$('[data-accordion]').find('[data-building-id="' + building_id + '"]').click();
		});

		$('.accordion-title').on('click', function(e) {
	        var buildingId = $(e.target).attr('data-building-id');
	        var activeBuilding = $('.slick-active').attr('data-building-id');
	        if(buildingId !== activeBuilding) {
	            var slide = $('.slide[data-building-id="' + buildingId + '"]').not('.slick-cloned').attr('data-slick-index');
	            $('[data-building-slider]').slick('slickGoTo', parseInt(slide));
	        }
	    });

		$('[data-building-slider]').slick({
			slidesToShow : 1,
			dots : true,
			arrows : false
		});

		// update the selected room modal detail from stored data
		$(document).on('open.zf.reveal', function(e) {
	        updateModalDetail($(e.target));
	    });
	    $('body').find('.pop-action').on('click', function() {        
	        if(event.ctrlKey) { $('body').addClass('admin'); }
	        var doorid = $(this).attr('data-door-id');
	        document.getElementById('popup-main').setAttribute('data-door-id',doorid);
	        $('.popup-main').foundation('open');
	    });
		//console.log(currentBuildings);

		if(currentBuildings.length > 0) {
			initialized = true;
			// ***** Improve this in the future to only get data for visible buildings, getting all for now
			visibleBuildings = currentBuildings;

			// BEGIN SET FAVORITE BUILDING
			// IT GOT MESSY WITH ALL THE SLIDE TRIGGERS		
			$('.favorite-building').click(function(){
			  var buildingid = $(this).attr('data-building-id');
			  if (typeof(Storage) !== "undefined") {			  	
			  	if(skipevent != true) {
			  		// remove disabled favorite button status
			  		var oldfavorite = $('.accordion').find('[data-building-id="' + localStorage.favoritebuilding + '"]');
			  		var newfavorite = $('.accordion').find('[data-building-id="' + buildingid + '"]');
					oldfavorite.removeClass('favorite');
					oldfavorite.prop("disabled",false);
					newfavorite.addClass('favorite');
					newfavorite.prop("disabled",true);
					skipevent = true;
					newfavorite.click();
					localStorage.setItem("favoritebuilding", buildingid);
			  	}
			  	skipevent = false;			  	
			  } else {
			  	//console.log('no storage fool');
			  }
			});
			// END SET FAVORITE BUILDING
		}
		// hard coded for now, add get to add marker or map key, or remove , or add to svg
		$('.slick-slider').find('[data-building-id=1] .map-marker').html('Front Desk');
		$('.slick-slider').find('[data-building-id=2] .map-marker').html('Kitchen');

		setInterval(function() {
			if(initialized) {							
				visibleBuildings.forEach(function(buildingID) {
				    getCurrentBuildingRoomStates(buildingID);
				});			
			}		    
		}, pollingSpeed);
		// MOVED TO DELAYED EVENT DUE TO WEIRD ACCORDION BOUNCING WHEN FIRED EARLY
		setTimeout(function() {
			if(initialized) {
				if(localStorage.favoritebuilding) {
					var temp = $('.accordion').find('[data-building-id="' + localStorage.favoritebuilding + '"]');
					skipevent = true;			
					temp.click();
					temp.addClass('favorite');
					temp.prop("disabled",true);
				}
			}		    
		}, 1000);
				
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
				slide_string += '<div class="map-marker"></div>';
				slide_string += '<div class="floorplan-svg">';
					slide_string += '<?xml version="1.0" encoding="utf-8"?>';
					slide_string += '<!-- Generator: Adobe Illustrator 21.0.2, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->';
					slide_string += building.svg;
				slide_string += '</div>';
			slide_string += '</div>';

			var accordion_string = '<li class="accordion-item ' + firstItemClass + '" data-accordion-item>';
				accordion_string += '<a href="#" class="accordion-title" data-building-id="'+building.id+'">'+building.name+' <small>('+building.number+')</small></a>';
				accordion_string += '<button class="favorite-building" type="button" data-building-id="'+building.id+'">Set Default</button>';				
				accordion_string += '<div class="accordion-content" data-tab-content>';
					accordion_string += '<div class="row small-up-2">';						
						building.sensors.forEach(function(sensor){
							accordion_string += '<div class="column column-block">';								
								accordion_string += '<div class="item pop-action" data-door-id="'+sensor.id+'" data-bathroom-id="bathroom_'+building.id+'_'+sensor.id+'">';
									accordion_string += '<button class="bathroom-label" data-open="bathroom_'+building.id+'_'+sensor.id+'_modal">'+sensor.name+'</button>';
									accordion_string += '<div class="status"></div>';
									accordion_string += '<div class="time-estimate"></div>';
								accordion_string += '</div>';
							accordion_string += '</div>';
						});						
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
				if(currentDoorSlider){
					if(sensor.state == OCCUPIED && !currentDoorSlider.classList.contains('occupied')) {
						// Update to active
						currentDoorSlider.classList.add('occupied');
						currentDoor.addClass('occupied');
					} else if(sensor.state != OCCUPIED && currentDoorSlider.classList.contains('occupied')){
						// Update to inactive			
			            currentDoorSlider.classList.remove('occupied');
						currentDoor.removeClass('occupied');
			        }
				}				
			});
			updateTime();
		});//end ajax request
	}
	// Called when the Modal is opened, get the room detail information and display it, assign button action/reference id(s)
	function updateModalDetail($element) {
	    var doorid = $element.attr('data-door-id');
	    var poproot = $('[data-door-id="' + doorid + '"]');
	    var nextservice = 'NA';

	    // grab room status data
		$.ajax({
			method: "GET",
			url: endpoint,
			data: {
				method: 'site'
				,request: 'getSensorDetail'
				,id : doorid 
			}
		}).done(function(rtn) {
			//console.log(rtn);
			rtn.sensors.forEach(function(sensor){
				poproot.find('.title').html(sensor.label);
		        poproot.find('.description').html(sensor.description);
		        poproot.find('.next-service').html(nextservice);
		        poproot.find('[data-view-1] [data-target]').html(sensor.totalusedall);
		        poproot.find('[data-view-2] [data-target]').html(sensor.totalusedtoday);
		        poproot.find('[data-view-3] [data-target]').html(sensor.lastusedseconds);
			});
		});//end ajax request
	}
	function updateTime() {
		var userTime = new Date();
		var formattedTime = userTime.getHours() + ":" + userTime.getMinutes() + ":" + userTime.getSeconds();
		$('.time').html(formattedTime);
	}
});