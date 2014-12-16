<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FB Group Gallery</title>
    <!-- Bootstrap -->
	<link rel="icon" href="favicon.ico" type="image/x-icon" />
    <link href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="//cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/0.9.9/magnific-popup.css" rel="stylesheet">
    <link href="css/site.css" rel="stylesheet">

    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>

  	<!-- Navbar -->
    <div class="navbar navbar-default" role="navigation">
      <div class="container-fluid">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="./">Brand</a>
        </div>
		<!-- Category Button Dropdown List -->
		<div class='collapse navbar-collapse'>
        <div class='navbar-left'>
			<ul class="nav navbar-nav">
				<li><a href='./'><i class='glyphicon glyphicon-camera'></i> Home</a></li>
			</ul>
		</div>		
		<ul class="nav navbar-nav navbar-right">
			<li><a title='總瀏覽量'><span><i class='glyphicon glyphicon-stats'></i><span id='counter' class='value'></span></span></a></li>
			<li>
			<?php if(!$_SESSION['fb_session']):?>
			<a href='<?php echo $loginUrl?>'>Facebook Login</a>
			<?php else:?>
			<a href='index.php?logout' title='Logout' style='padding:2px 10px;'><img class='img-circle' style='width:40px' src='https://graph.facebook.com/<?php echo $userID?>/picture'></a>
			<?php endif;?>
			</li>
		</ul>
		</div>
      </div>
    </div>
	<!-- /Navbar -->
	
	<div class="container-fluid" id='content'>
		<?php echo isset($content) ? $content : ''?>
	</div>

	<div id='footer' class="container-fluid">		
		<?php if(isset($runtime['counter'])):?>
		<div class="col-sm-5 pull-right">
			<span class='label label-default'>貼文處理計數</span>
			<div class='counter'>
				<span>總共貼文</span><span class='badge'><?php echo $runtime['counter']['feeds']?></span> | 
				<span>符合貼文</span><span class='badge'><?php echo $runtime['counter']['posts']?></span> | 
				<span>過濾數量</span><span class='badge'><?php echo $runtime['counter']['filtered']?></span> | 
				<span>無照片貼文</span><span class='badge'><?php echo $runtime['counter']['photoSkiped']?></span>
			</div>
		</div>
		<?php endif;?>
		<?php if(isset($runtime['fbGraphTime'])):?>
		<div class="col-sm-2 pull-right">
			<span class='label label-default'>抓FB執行時間</span>
			<div class='fbGraphTime'><?php echo $runtime['fbGraphTime']?> s</div>
		</div>
		<?php endif;?>
	</div>
	<button id='goTop' class='btn btn-primary' title='Back to Top'><i class='glyphicon glyphicon-chevron-up'></i></button>
    
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.1/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/masonry/3.2.1/masonry.pkgd.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery.imagesloaded/3.0.4/jquery.imagesloaded.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/0.9.9/jquery.magnific-popup.min.js"></script>
    <script src="js/Autolinker.min.js"></script>
    <script>
	(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	//ga('create', 'GA ID', 'auto');
	//ga('send', 'pageview');
	  
    $(function(){
    	$("#goTop").click(function(event) {
    		$('html, body').animate({scrollTop : 0},500);
    		return false;
    	});

		$(".post .message").each(function(){
			$(this).html( Autolinker.link($(this).html()) );
		});
		
		$('.post_container').imagesLoaded( function(){
			$(".post_container").masonry({
				itemSelector : '.post',
				isFitWidth: true
			});
		});
		$(".lightbox").magnificPopup({ 
			type: 'image',
			mainClass: 'mfp-with-zoom',
			zoom: {
				enabled: true, // By default it's false, so don't forget to enable it
				duration: 300, // duration of the effect, in milliseconds
				easing: 'ease-in-out', // CSS transition easing function 
			  }
		});	
	});
	
	//google analysis pageView
	var counter = {};
    	counter.container = 'counter';
    	counter.sheetID = "Google Sheet ID";
    	counter.sheetUrl = "https://spreadsheets.google.com/feeds/list/"+counter.sheetID+"/od8/public/values?alt=json-in-script&callback=counter.callback";
	/*
	(function(){
		var newScript = document.createElement("script");
		newScript.src = counter.sheetUrl;
		document.documentElement.firstChild.appendChild(newScript)
	})();
	*/
	counter.callback = function(res){
		var totalCnt = 0;
		var entry = res.feed.entry;
		//var len = entry.length;
		for(index in entry){
			var row = entry[index];
			var title = row.gsx$path.$t;
			var cnt = row.gsx$view.$t
			
			if( title.indexOf("?login") > 0 ){
				continue;
			}
			if( title.indexOf("index4test.php") > 0 ){
				continue;
			}
			totalCnt += parseInt(cnt, 10);
		}
		document.getElementById(counter.container).innerHTML = totalCnt;
	};
    </script>
  </body>
</html>