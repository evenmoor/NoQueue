var currentSlide = 0;

$(document).foundation()

$(document).ready(function() {
    $('[data-sv-slider]').on('beforeChange', function(event, slick, current, next) {
        var buildingId = $('[data-slick-index="' + next + '"]').attr('data-building-id');
        $('[data-accordion]').find('[data-building-id="' + buildingId + '"]').click();
    });

    $('[data-sv-slider]').slick({
        slidesToShow : 1,
        dots : true,
        arrows : false
    });

    $('.bathroom-label').on('mouseover', function(e) {
        var bathroomId = $(this).parent().attr('data-bathroom-id');
        document.querySelector('#' + bathroomId).classList.add('hover');
    });

    $('svg').find('.bathroom').on('mouseover', function(e) {
        var id = $(this).attr('id');
        $('[data-bathroom-id="' + id + '"]').addClass('hover');
    });

    $('.bathroom-label').on('mouseleave', function(e) {
        var bathroomId = $(this).parent().attr('data-bathroom-id');
        document.querySelector('#' + bathroomId).classList.remove('hover');
    });

    $('svg').find('.bathroom').on('mouseleave', function(e) {
        var id = $(this).attr('id');
        $('[data-bathroom-id="' + id + '"]').removeClass('hover');
    });

    $('.button-action').on('click', function(e) {
        var doorid = $('.popup-main').attr('data-door-id');
        var statusid = $(this).attr('data-status-id');
        postDoorStatus(doorid, statusid);
    });

    $(document).on('open.zf.reveal', function(e) {
        updateModalDetail($(e.target));
    });

    $('.accordion-title').on('click', function(e) {
        var buildingId = $(e.target).attr('data-building-id');
        var activeBuilding = $('.slick-active').attr('data-building-id');

        if(buildingId !== activeBuilding) {
            var slide = $('.slide[data-building-id="' + buildingId + '"]').not('.slick-cloned').attr('data-slick-index');
            $('[data-sv-slider]').slick('slickGoTo', parseInt(slide));
        }
    });

    $('.next-service-clear').on('click', function(e) {
        var doorid = $('.popup-main').attr('data-door-id');
        var statusid = 1;
        console.log('clear');
        postDoorStatus(doorid, statusid);
    });

    $('svg').find('.bathroom').on('click', function() {        
        if(event.ctrlKey) { $('body').addClass('admin'); }
        var id = $(this).attr('id');
        $('#' + id + '_modal').foundation('open');
    });    
});

setInterval(function() {
    getCurrentBuildingState();
}, 5000);

function postDoorStatus(doorid, statusid) {
    var postdoorstatus = $.post( "http://svhack.yetilair.com/endpoints/?action=setDoorStatus&id="+ doorid +"&status="+ statusid, function() {
      console.log('success, update ui?');
    })
    .fail(function() {
        console.log('uh oh');
    })
}

function getCurrentBuildingState() {
    $.get('http://svhack.yetilair.com/endpoints/?action=getCurrentBuildingState&ids=1,2?' + Math.random(), function(data) {
        //Pull in buildings
        data.forEach(function(building) {
            console.log('building', building);
            var buildingId = building.id;
            var doors = building.doors;
            // $('.occupied').removeClass('occupied');
            updateBathrooms(doors, building);

        });
    });
}

function updateBathrooms(doors, building) {
    doors.forEach(function(door) {
        var buildingId = building.id;
        var bathroomId = 'bathroom_' + buildingId + '_' + door.id;

        // document.querySelector('#bathroom_' + buildingId + '_' + door.id).classList.remove('occupied');
        if(door.status.occupied && !$('[data-bathroom-id="' + bathroomId + '"]').find('.status').hasClass('occupied')) {
            // console.log('occupied condition' + bathroomId);
            // console.log($('#'+bathroomId).hasClass('occupied'));

            if(!$('#'+bathroomId).hasClass('occupied')) {
                console.log('sound outter condition');
                playDoorSound();
            }
            document.querySelector('#bathroom_' + buildingId + '_' + door.id).classList.add('occupied');
            var $occupiedBathroom = $('[data-bathroom-id="' + bathroomId + '"]');
            var timeEstimate = getTimeEstimate(new Date(), building.status, door.id);
            $occupiedBathroom.find('.status').addClass('occupied');
            $occupiedBathroom.find('.time-estimate').html(timeEstimate);

        }else if(!door.status.occupied && $('[data-bathroom-id="' + bathroomId + '"]').find('.status').hasClass('occupied')){
            document.querySelector('#bathroom_' + buildingId + '_' + door.id).classList.remove('occupied');
            $('[data-bathroom-id="' + bathroomId + '"]').find('.status').removeClass('occupied');
            $('.time-estimate').html('');
        }
    });
}

// Called when the Modal is opened, get the room detail information and display it, assign button action/reference id(s)
function updateModalDetail($element) {
    var doorid = $element.attr('data-door-id');
    // document.getElementById('popup-main').setAttribute('data-door-id',doorid);
    var nextservice = window.requestCountEstimate(doorid);
    console.log(nextservice);

    $.get('http://svhack.yetilair.com/endpoints/?action=getDoorDetails&id=' + doorid, function(data) {
        var template = '<div id="popup-main" data-door-id>' +
            '<div class="row">' +
                '<div class="title">' + data.label + '</div>' +
                '<div class="description">' + data.description + '</div>' +
            '</div>' +
            '<div class="row action-items">' +
                '<div class="columns large-4 small-4">' +                    
                    '<a href="#" class="button-action" data-action-id="clean" data-status-id="2">' +
                        '<div class="next-service">' + nextservice + '</div>' +
                        '<div class="next-service-clear">x</div>' +  
                        '<img src="./images/icon_cleaning.png" alt="need cleaning">' +
                    '</a>' +
                '</div>' +
                '<div class="columns large-4 small-4">' +
                    '<a href="#" class="button-action" data-action-id="supplies" data-status-id="3">' +
                        '<img src="./images/icon_supplies.png" alt="need supplies">' +
                    '</a>' +
                '</div>' +
                '<div class="columns large-4 small-4">' +
                    '<a href="#" class="button-action" data-action-id="favorite" data-status-id="0">' +
                        '<img src="./images/icon_favorite.png" alt="favorite">' +
                    '</a>' +
                '</div>' +
            '</div>' +
            '<div class="row extras">' +
                '<div class="columns large-4 small-4">' +
                    '<div class="wrapper" data-view-1>' +
                        '<span>Times Used</span>' +
                        '<div data-target>' + data.times_used + '</div>' +
                        '<div>times</div>' +
                    '</div>' +
                '</div>' +
                '<div class="columns large-4 small-4">' +
                    '<div class="wrapper" data-view-2>' +
                        '<span>Average Time Taken</span>' +
                        '<div data-target>' + data.average_time_taken + '</div>' +
                        '<div>seconds</div>' +
                    '</div>' +
                '</div>' +
                '<div class="columns large-4 small-4">' +
                    '<div class="wrapper" data-view-3>' +
                        '<span>Last Used</span>' +
                        '<div data-target>' + data.last_used + '</div>' +
                        '<div>Mins. ago</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';

        $element.html(template);
    });    
}
//http://svhack.yetilair.com/endpoints/?action=getDoorDetails&id=1
//updateModalDetail(2);

function playDoorSound() {
    var myaudio = document.getElementById('audio');
    var source = document.getElementById('audioSource');
    var maxsounds = 2;
    var myrand = Math.floor(Math.random() * maxsounds) + 1 ;
    switch(myrand) {
        case 1:
            source.src = "http://www.noiseaddicts.com/samples_1w72b820/4930.mp3";
            break;
        case 2:
            source.src = "http://www.noiseaddicts.com/samples_1w72b820/4930.mp3";
            break;
        default:
            source.src = "sounds/price.mp3";
    }

    myaudio.load(); //call this to just preload the audio without playing
    myaudio.play(); //call this to play the song right away
}

function getTimeEstimate(date, buildingStatus, doorId) {
    var time = [date.getHours(), date.getMinutes(), date.getSeconds()];
    var timeEstimate = window.requestTimeEstimate(time, buildingStatus, doorId);

    return timeEstimate;
}
