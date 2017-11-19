<!doctype html>
<html class="no-js" lang="en">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>NoQueue | Welcome</title>
	<link rel="stylesheet" href="http://dhbhdrzi4tiry.cloudfront.net/cdn/sites/foundation.min.css">
	<link href="https://fonts.googleapis.com/css?family=Open+Sans|Roboto+Slab" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="./js/slick/slick-theme.css"/>
	<link rel="stylesheet" type="text/css" href="./js/slick/slick.css"/>
	<link rel="stylesheet" href="./css/app.css">
	<link rel="stylesheet" href="./js/iziModal/iziModal.min.css">	
	<link rel="shortcut icon" href="./images/favicon.ico" type="image/x-icon">
	<link rel="icon" href="./images/favicon.ico" type="image/x-icon">
	<!-- <script src="./js/synaptic.js"></script> -->
</head>
<body>
	<!-- Start Top Bar -->
	<div class="top-bar">
		<div class="top-bar-inner">
			<div class="top-bar-left">
				<ul class="menu">
					<li class="menu-text">
						<a class="header-logo" href="/">
							<img src="./images/NoQueue_logo.png" alt="NoQueue">
						</a>
					</li>
				</ul>
			</div><!--/top-bar-left-->
			
			<div class="top-bar-right">	
			</div><!--/top-bar-right-->
		</div><!--/top-bar-inner-->
	</div><!--/top-bar-->

	<!-- Start Slider -->
	<div class="row">
		<div class="columns large-12 slide-wrapper">
			<div class="last-updated">Last Updated <div class="time"></div></div>
			<div class="slick-slider" data-building-slider></div><!--/slider-->
		</div>
	</div>
	<!-- End Slider -->

	<!-- Start Accordion -->
	<div class="row bathroom-list">
		<div class="columns">
			<ul class="accordion" data-accordion></ul><!--/accordion-->
		</div>
	</div>
	<!-- End Accordion -->

	<footer class="footer">
		<div class="row">
			<div class="columns small-12">
				<img src="./images/hackathon_logo.png">
				<p>Simpleview Inc. Hack-a-Thon 2017</p>
			</div>
		</div>
		<audio id="audio">
			<source id="audioSource" src=""></source>
		</audio>
	</footer>

<div class="reveal popup-main" id="popup-main" data-door-id="0" data-reveal>
	<div class="row">
		<div class="title"></div>
		<div class="description"></div>
	</div>
	<div class="row action-items">
		<div class="columns large-4 small-4">
			<a href="#" class="button-action" data-action-id="clean" data-status-id="2">
				<div class="next-service"></div>
				<div class="next-service-clear">x</div>
				<img src="./images/icon_cleaning.png" alt="need cleaning">
			</a>
		</div>
		<div class="columns large-4 small-4">
			<a href="#" class="button-action" data-action-id="supplies" data-status-id="3">
				<img src="./images/icon_supplies.png" alt="need supplies">
			</a>
		</div>
		<div class="columns large-4 small-4">
			<a href="#" class="button-action" data-action-id="favorite" data-status-id="0">
				<img src="./images/icon_favorite.png" alt="favorite">
			</a>
		</div>
	</div>
	<div class="row extras">
		<div class="columns large-4 small-4">
			<div class="wrapper" data-view-1>
				<span>used</span>
				<div data-target></div>
				<div>total</div>
			</div>
		</div>
		<div class="columns large-4 small-4">
			<div class="wrapper" data-view-2>
				<span>used</span>
				<div data-target></div>
				<div>today</div>
			</div>
		</div>
		<div class="columns large-4 small-4">
			<div class="wrapper" data-view-3>
				<span>used</span>
				<div data-target></div>
				<div>sec. ago</div>
			</div>
		</div>
	</div>
</div>

<script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
<script src="http://dhbhdrzi4tiry.cloudfront.net/cdn/sites/foundation.js"></script>
<!-- <script src="./js/nn.js"></script>
<script src="./js/nn2.js"></script> -->
<script src="./js/slick/slick.min.js"></script>
<script src="./js/app.js"></script>
</body>
</html>
