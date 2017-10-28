$(document).ready(function(){

	console.log("ready");
	const { Layer, Network } = window.synaptic;

	var network = [];
	var inputLayer = [];
	var hiddenLayer = [];
	var outputLayer = [];

	var learningRate = .1;

	

	var xhr = $.get("http://svhack.yetilair.com/endpoints/?action=getMLStructBuilding&id=1", function(data) {
		activateNet(data);
	}).fail(function() {
		alert( "error" );
	});


	function activateNet(data){

		for(d=1;d<=data.length;d++){
			
			var info = data[d-1].log;
		
			inputLayer[d-1] = new Layer(2);
			hiddenLayer[d-1] = new Layer(3);
			outputLayer[d-1] = new Layer(1);
			inputLayer[d-1].project(hiddenLayer[d-1]);
			hiddenLayer[d-1].project(outputLayer[d-1]);

			network[d-1] = new Network({
				input: inputLayer[d-1],
				hidden: [hiddenLayer[d-1]],
				output: outputLayer[d-1]
			});

			for (var runs = 0; runs < 50000; runs++){

				for(var inf=0; inf<info.length; inf++){

					var i=info[inf];

					var ch = parseInt(i.closed.hour);
					var cm = parseInt(i.closed.minute);
					var cs = parseInt(i.closed.second);

					var c = timeToSig(ch,cm,cs);

					var lm = parseInt(i.length.minute);
					var ls = parseInt(i.length.second);

					var b = parseInt(i.closed.building_state);
					if(b!=1){lm=0;ls=0;}
					var l = timeToSig(0,lm,ls);

					network[d-1].activate([c,b]);
					network[d-1].propagate(learningRate, [l]);

				};
			
			}
			console.log(d+" of "+data.length+" created");
		}

		/*for(d=1;d<=data.length;d++){
			console.log(d,'--------------------');
			console.log(3,00,requestTime([3,0,0],0,d));
			console.log(8,10,requestTime([8,10,0],1,d));
			console.log(9,45,requestTime([9,45,0],1,d));
			console.log(11,0,requestTime([11,0,0],1,d));
			console.log(1,05,requestTime([1,5,0],1,d));
			console.log(2,15,requestTime([14,00,0],1,d));
			console.log(4,30,requestTime([16,30,0],1,d));
			console.log(8,00,requestTime([20,0,0],0,d));
		}*/
	}


	function timeToSig(h,m,s){
		var ts = 24 * 60 * 60;
		return  (h * (60*60) + m * 60 + s)/ts;
	}

	function sigToTime(sg){
		var ts = 24 * 60 * 60;
		var sts = sg*ts;
		var h = Math.floor(sg*24);
		var m = Math.floor((sg*24*60) % 24);
		var s = Math.floor(60*(((sg*24*60) % 24 ) - m) );
		return([h,m,s]);
	}

	function requestTime(t,b,d){
		var n = network[d-1].activate([timeToSig(t[0],t[1],t[2]),b]);
		var nt = sigToTime(n);
		return(nt[0]+":"+nt[1]+":"+nt[2]);
	}

	function requestTimeArray(t,b,d){
		var n = network[d-1].activate([timeToSig(t[0],t[1],t[2]),b]);
		var nt = sigToTime(n);
		return(nt);
	}
	window.requestTimeEstimate = function(t,b,d){
		var n = requestTimeArray(t,b,d);
		var mins = n[1]-1;
		if(mins<=0)mins=1;
		
		return ("~"+mins+" mins");
	}
});