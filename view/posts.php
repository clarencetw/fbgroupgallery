<?php if($posts['error']):?>
	Oops! Something Error!
<?php elseif( count($posts['data']) ):?>
	<h3 style='text-align: center;'><?php echo isset($title) ? $title : ''; ?></h3>
	<div class='post_container'>
	<?php $i=1; foreach($posts['data'] as $post):?>
	<div class="well well-sm post">			
		<?php if(count($post['pictures'])): ?>
		<div class="photo">
			<a class='lightbox' href="<?php echo $post['pictures'][0];?>" title="<?php echo $post['message'];?>">
			<img src="<?php echo $post['pictures'][ round(count($post['pictures'])/2) ];?>" class="img-responsive center-block" alt="image">
			</a>
		</div>
		<?php endif;?>
		<div class="infos">
			<div class="head">
				<img class='userIcon img-circle' style='width:30px' src='https://graph.facebook.com/<?php echo $post['userID']?>/picture'/>
				<div class="userName">						
					<?php echo $post['name'];?>
				</div>
			</div>
			<div class="message">
				<?php echo nl2br($post['message']);?>
			</div>
			<div class="foot">										
				<div class="pull-left likes">
					<?php echo $post['likes']?>
				</div>
				<div class="pull-right order">
					#<?php echo $i; ?>
				</div>				
				<div class='pull-right posturl'>
					<a href='<?php echo $post['link']?>' target='_blank'>原始貼文</a>						
				</div>
				<div class="pull-right date">
					<?php echo date('Y-m-d H:i', strtotime($post['updated_time']))?>
				</div>
				<div class='clearfix'></div>					
			</div>
		</div>
	</div>
	<?php $i++; endforeach;?>
</div>
<?php endif;?>