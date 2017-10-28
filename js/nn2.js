	$(document).ready(function(){


	const { Layer, Network } = window.synaptic;

	var network = [];
	var inputLayer = [];
	var hiddenLayer = [];
	var outputLayer = [];

	var learningRate = .25;

	var data;

	var xhr = $.get("http://svhack.yetilair.com/endpoints/?action=getMLPredictiveSchedulingBuilding&id=1&status=3&test=true", function(ndata) {
		data = ndata.data;
		
		activateNet(data);
	}).fail(function() {
		alert( "error" );
	});

	/*var data=[
		{
		info:{
			gender:1,
			maxuses:100,
			avgperday:34,
			numsincelast: 11,
			timesincelast: {hours:2,minutes:22}
		},
		flags:[
			
			{
				count:30,
				timesince:{hours:2,minutes:02}
			},
			{
				count:22,
				timesince:{hours:1,minutes:45}
			},
			{
				count:31,
				timesince:{hours:2,minutes:17}
			},
			{
				count:40,
				timesince:{hours:4,minutes:00}
			},
			{
				count:32,
				timesince:{hours:3,minutes:15}
			},
			{
				count:25,
				timesince:{hours:2,minutes:02}
			},
			{
				count:28,
				timesince:{hours:2,minutes:45}
			},
			{
				count:27,
				timesince:{hours:2,minutes:00}
			},
			{
				count:46,
				timesince:{hours:4,minutes:00}
			}

		]
	}
	];*/





	function activateNet(data){

		

		for(d=1;d<=data.length;d++){
			
			var info = data[d-1].info;
			var flags = data[d-1].flags;

			var countstotal=0;
			var counts=0;
			var timestotal = 0;

			for(var flag=0; flag<flags.length; flag++){
				var i=flags[flag];
				countstotal+=i.count;
				var th = parseInt(i.timesince.hours);
				var tm = parseInt(i.timesince.minutes);
				timestotal+= timeToSig(th,tm,0);
				counts++;
			}
			data[d-1].info.average = countstotal/counts;
			data[d-1].info.averagetime = timestotal/counts;

			for(var x=0;x<12;x++)flags.push({count:0,timesince:{hours:0,minutes:0}});
			flags.push({count:246,timesince:{hours:24,minutes:0}});

			inputLayer[d-1] = new Layer(1);
			hiddenLayer[d-1] = new Layer(2);
			outputLayer[d-1] = new Layer(1);
			inputLayer[d-1].project(hiddenLayer[d-1]);
			hiddenLayer[d-1].project(outputLayer[d-1]);

			network[d-1] = new Network({
				input: inputLayer[d-1],
				hidden: [hiddenLayer[d-1]],
				output: outputLayer[d-1]
			});

			for (var runs = 0; runs < 50000; runs++){

				for(var flag=0; flag<flags.length; flag++){

					var i=flags[flag];

					var th = parseInt(i.timesince.hours);
					var tm = parseInt(i.timesince.minutes);
	
					var t = timeToSig(th,tm,0);

					var c = parseInt(i.count)/100.00; 
					//console.log(t,c);
 					
					network[d-1].activate([t]);
					network[d-1].propagate(learningRate,[c]);

				};
			
			}
			
		}

		for(d=1;d<=data.length;d++){
			



			var r =  data[d-1].info.average - requestCount([data[d-1].info.timesincelast.hours,data[d-1].info.timesincelast.minutes,0],d);
			var t = data[d-1].info.averagetime/ data[d-1].info.average;
			//console.log(t);
			var s = sigToTime(r/t*50);
			//console.log("~"+s[1]+" hours");
			

		}

		//window.requestCountEstimate(1);
	}


	function timeToSig(h,m,s){
		var ts = 24 * 60 * 60 *7;
		return  (h * (60*60) + m * 60 + s)/ts;
	}

	function sigToTime(sg){
		var ts = 24 * 60 * 60 * 7;
		var sts = sg*ts;
		var h = Math.floor(sg*24);
		var m = Math.floor((sg*24*60) % 24);
		var s = Math.floor(60*(((sg*24*60) % 24 ) - m) );
		return([h,m,s]);
	}

	function requestCount(t,d){
	//console.log(timeToSig(t[0],t[1],t[2]));
		var n = network[d-1].activate([timeToSig(t[0],t[1],t[2])]);
		
		return(n);
	}

	
	window.requestCountEstimate = function(d){
		var r =  data[d-1].info.average - requestCount([data[d-1].info.timesincelast.hours,data[d-1].info.timesincelast.minutes,0],d);
		var t = data[d-1].info.averagetime/ data[d-1].info.average;
		
		var s = sigToTime(r/t*50);
		return("~"+s[1]+1+" hours");
		
	}
});