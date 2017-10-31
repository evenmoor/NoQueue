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
        postDoorStatus(doorid, statusid);
    });

    $('body').find('.pop-action').on('click', function() {        
        if(event.ctrlKey) { $('body').addClass('admin'); }
        var doorid = $(this).attr('data-door-id');
        document.getElementById('popup-main').setAttribute('data-door-id',doorid);
        $('.popup-main').foundation('open');
    });
});

setInterval(function() {
    getCurrentBuildingState();
}, 5000);

function postDoorStatus(doorid, statusid) {
    var postdoorstatus = $.post( "http://svhack.yetilair.com/endpoints/?action=setDoorStatus&id="+ doorid +"&status="+ statusid, function() {
    })
    .fail(function() {
        console.log('uh oh');
    })
}

function getCurrentBuildingState() {
    $.get('http://svhack.yetilair.com/endpoints/?action=getCurrentBuildingState&ids=1,2?' + Math.random(), function(data) {
        //Pull in buildings
        data.forEach(function(building) {
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
    var poproot = $('[data-door-id="' + doorid + '"]');
    var nextservice = 'NA';

    try {
        nextservice = window.requestCountEstimate(doorid);
    }
    catch(err) {
        // uh oh, rob did you let alexander help u?
    }
    
    $.get('http://svhack.yetilair.com/endpoints/?action=getDoorDetails&id=' + doorid, function(data) {
        poproot.find('.title').html(data.label);
        poproot.find('.description').html(data.description);
        poproot.find('.next-service').html(nextservice);
        poproot.find('[data-view-1] [data-target]').html(data.times_used);
        poproot.find('[data-view-2] [data-target]').html(data.average_time_taken);
        poproot.find('[data-view-3] [data-target]').html(data.last_used);
    });
}

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
