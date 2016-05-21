<? 
/*__nks{ проверка отображаемого рейтинга через $_GET['rate'] - соответственно от 1 до 5 }*/  
?>
<html>
<head>
	<title>Test</title>
	<style type="text/css">
		body{
			text-align: center;
			padding-top: 50px;
			padding-left: 50px;
		}
		.wrapper{
			width: 90px; /*__nks{ меняем, если надо шире }*/			  
			overflow: hidden;
		}
		.wrapper span{
			margin: 0;
			padding: 0;
			width: 19%; 
			height: 16px; /*__nks{ меняем, если надо шире }*/	
			margin-right: 1%;
			background: url('star.png') no-repeat center top;
			background-size: 100% auto;
			display: block;
			float: left;
			cursor: pointer;
		}
		.wrapper .checked{
			background-position: center 100%;
		}
	</style>
</head>
<body>
	<div class="wrapper" data-cur="<?=isset($_GET['rate'])?$_GET['rate']:''?>">
		<span data-rate="1"></span>
		<span data-rate="2"></span>
		<span data-rate="3"></span>
		<span data-rate="4"></span>
		<span data-rate="5"></span>
	</div>

	<!-- __nks{ ну не люблю я нативный js }-->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.3/jquery.min.js"></script>
	<script type="text/javascript">
		function chRate(rate){
			if (rate != undefined) {
				var curRate = rate;
			}else{
				/*__nks{ .data('cur') использовать не получиться, т.к. он кешируется }*/	
				var curRate = $('.wrapper').attr('data-cur');
			};			
			$('.wrapper span').each(function(){
				var itemRate = $(this).attr('data-rate');
				if (itemRate <= curRate) {
					$(this).addClass('checked');
				}else{
					$(this).removeClass('checked');
				}
			});
		}
		
		$(function(){
			chRate();
			$('.wrapper span').on('mouseenter',function(){
				var itemRate = $(this).attr('data-rate');
				chRate(itemRate);
			});
			$('.wrapper span').on('mouseout',function(){
				var itemRate = $('.wrapper').attr('data-cur');
				chRate(itemRate);
			});
			$('.wrapper span').on('click',function(){
				var itemRate = $(this).attr('data-rate');
				$('.wrapper').attr('data-cur',itemRate);
			});
		})
	</script>
</body>
</html>