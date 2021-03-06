<?php
//echo 1; exit;
add_action('lp_init','lp_library_shareme_enqueue');
function lp_library_shareme_enqueue()
{
	wp_enqueue_script('sharrre', LANDINGPAGES_URLPATH . 'assets/libraries/shareme/sharrre/jquery.sharrre-1.3.3.min.js', array('jquery'));
}

function lp_library_shareme_js($nature)
{
global $post;
if(has_post_thumbnail()) {
	$thumb = wp_get_attachment_url( get_post_thumbnail_id($post->ID) );
}
else {
	$thumb = "";
}
	$page_title = get_the_title();
	$new_page_title = str_replace('"', '\"', $page_title);
	$newest_page_title = str_replace("'", "\'", $new_page_title);
	if ($nature!='vertical')
	{
	?>
		<script type="text/javascript">
		jQuery(document).ready(function () {
			jQuery('#shareme').sharrre({
			  share: {
				googlePlus: true,
				twitter: true,
				linkedin: true,
				pinterest: true,
				facebook: true
			  },
			  buttons: {
				googlePlus: {size: 'medium'},
				twitter: {count: 'horizontal'},
				linkedin: {counter: 'right'},
				pinterest: {media: '<?php echo $thumb;?>', description: '<?php echo $newest_page_title;?>', layout: 'horizontal'},
				facebook: {layout: 'like_count', width: '50', colorscheme: 'dark' }
			  },
			  enableHover: false,
			  enableCounter: false,
			  enableTracking: true
			});
		});
		</script>
	<?php
	}
	else
	{
		?>
		<script type="text/javascript">
		jQuery(document).ready(function () {
			jQuery('#shareme').sharrre({
			  share: {
				googlePlus: true,
				facebook: true,
				twitter: true,
				linkedin: true,
				pinterest: true
			  },
			  buttons: {
				googlePlus: {size: 'medium'},
				facebook: {layout: 'like_count', width: '45'},
				twitter: {count: 'horizontal'},
				linkedin: {counter: 'right'},
				pinterest: {media: '<?php echo $thumb;?>', description: '<?php echo $newest_page_title;?>', layout: 'horizontal'}
			  },
			  enableHover: false,
			  enableCounter: false,
			  enableTracking: true
			});
		 });
		</script>
		<?php
	}
}

function lp_library_shareme_css($nature)
{
	?>
<style type="text/css">

<?php if ($nature=='vertical') {?>
	#lp-social-buttons{
		position: absolute;
		top: 70px;
		width: 70px;
		left: 990px;
		padding: 10px;
		overflow: hidden;
		background: transparent;
		z-index: 999;}

	#lp-social-buttons iframe {
		width:85px !important;}

	#lp-social-buttons {
		margin: 0 auto;
		text-align: left;}
	.linkedin {margin-bottom: -20px;}
<?php } else { ?>
	.sharrre .button {
		width: 95px;
		display: inline-block;
		vertical-align: top;
		margin-top: 10px;}
	.linkedin {margin-right: -15px;}
<?php } ?>

	</style>
	<?php
}

function lp_social_media($nature=null)
{
	//lp_library_shareme_js($nature);
	//lp_library_shareme_css($nature);
	?>
	<style type="text/css">
	#inbound-social-inbound-social-buttons{border-radius:5px;padding:14px 7px;background:#fff;width:460px;margin-left:-10px;overflow:hidden}.inbound-social-button{background:#DCE0E0;position:relative;display:block;float:left;height:40px;margin:0 7px;overflow:hidden;width:100px;border-radius:3px;-o-border-radius:3px;-ms-border-radius:3px;-moz-border-radius:3px;-webkit-border-radius:3px}.inbound-social-icon{display:none;float:left;position:relative;z-index:3;height:100%;vertical-align:top;width:38px;-moz-border-radius-topleft:3px;-moz-border-radius-topright:0;-moz-border-radius-bottomright:0;-moz-border-radius-bottomleft:3px;-webkit-border-radius:3px 0 0 3px;border-radius:3px 0 0 3px;text-align:center}.inbound-social-icon i{color:#fff;line-height:42px}.inbound-social-slide{z-index:2;display:block;margin:0;height:100%;left:38px;position:absolute;width:112px;-moz-border-radius-topleft:0;-moz-border-radius-topright:3px;-moz-border-radius-bottomright:3px;-moz-border-radius-bottomleft:0;-webkit-border-radius:0 3px 3px 0;border-radius:0 3px 3px 0}.inbound-social-slide p{font-family:Open Sans;font-weight:400;border-left:1px solid #fff;border-left:1px solid rgba(255,255,255,.35);color:#fff;font-size:16px;left:0;margin:0;position:absolute;text-align:center;top:10px;width:100%}.inbound-social-button .inbound-social-slide{-webkit-transition:all .2s ease-in-out;-moz-transition:all .2s ease-in-out;-ms-transition:all .2s ease-in-out;-o-transition:all .2s ease-in-out;transition:all .2s ease-in-out}.inbound-social-facebook iframe{display:block;position:absolute;right:16px;top:10px;z-index:1}.inbound-social-twitter iframe{width:90px!important;right:5px;top:10px;z-index:1;display:block;position:absolute}.inbound-social-google #___plusone_0{width:70px!important;top:10px;right:15px;position:absolute;display:block;z-index:1}.inbound-social-linkedin .IN-widget{top:10px;right:22px;position:absolute;display:block;z-index:1}.inbound-social-facebook:hover .inbound-social-slide{left:150px}.inbound-social-twitter:hover .inbound-social-slide{top:-40px}.inbound-social-google:hover .inbound-social-slide{bottom:-40px}.inbound-social-linkedin:hover .inbound-social-slide{left:-150px}.inbound-social-facebook .inbound-social-icon,.inbound-social-facebook .inbound-social-slide{background:#305c99}.inbound-social-twitter .inbound-social-icon,.inbound-social-twitter .inbound-social-slide{background:#00cdff}.inbound-social-google .inbound-social-icon,.inbound-social-google .inbound-social-slide{background:#d24228}.inbound-social-linkedin .inbound-social-icon,.inbound-social-linkedin .inbound-social-slide{background:#007bb6}


	 #inbound-social-inbound-social-buttons{text-align:center;background:rgba(0,0,0,0);padding:0;margin-top:10px} .inbound-social-slide{display:none!important} .inbound-social-button{height:26px;background:0;border-color:transparent} .inbound-social-icon i{color:#FFF;line-height:27px} .inbound-social-facebook iframe, .inbound-social-twitter iframe, .inbound-social-google #___plusone_0, .inbound-social-linkedin .IN-widget{top:0px}</style>
	<div id="inbound-social-inbound-social-buttons" class="social-media-inbound-buttons">
	  <div class="inbound-social-facebook inbound-social-button">
	    <i class="inbound-social-icon">
	      <i class="icon-facebook">
	    </i>
	  </i>
	  <div class="inbound-social-slide">
	    <p>
	      facebook
	    </p>
	  </div>
	  <iframe src="//www.facebook.com/plugins/like.php?href=<?php the_permalink();?>&send=false&layout=button_count&width=80&show_faces=false&font&colorscheme=light&action=like&height=20&appId=568581339861351" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:80px; height:20px;" allowTransparency="true">
	  </iframe>
	  </div>

	  <div class="inbound-social-twitter inbound-social-button">
	    <i class="inbound-social-icon">
	      <i class="icon-twitter">
	    </i>
	  </i>
	  <div class="inbound-social-slide">
	    <p>
	      twitter
	    </p>
	  </div>
	  <a href="https://twitter.com/share" class="twitter-share-button">
	    Tweet
	  </a>
	  <script>
	    !function(d,s,id){
	      var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';
	      if(!d.getElementById(id)){
	        js=d.createElement(s);
	        js.id=id;
	        js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');
	  </script>
	  </div>

	  <div class="inbound-social-google inbound-social-button">
	    <i class="inbound-social-icon">
	      <i class="icon-google-plus">
	    </i>
	  </i>
	  <div class="inbound-social-slide">
	    <p>
	      google+
	    </p>
	  </div>
	  <!-- Place this tag where you want the +1 inbound-social-button to render. -->
	  <div class="g-plusone" data-size="medium">
	  </div>

	  <!-- Place this tag after the last +1 inbound-social-button tag. -->
	  <script type="text/javascript">
	    (function() {
	      var po = document.createElement('script');
	      po.type = 'text/javascript';
	      po.async = true;
	      po.src = 'https://apis.google.com/js/plusone.js';
	      var s = document.getElementsByTagName('script')[0];
	      s.parentNode.insertBefore(po, s);
	    }
	    )();
	  </script>
	  </div>


	  <div class="inbound-social-linkedin inbound-social-button">
	    <i class="inbound-social-icon">
	      <i class="icon-linkedin">
	    </i>
	  </i>
	  <div class="inbound-social-slide">
	    <p>
	      linkedin
	    </p>
	  </div>
	  <script type="IN/Share" data-counter="right">
	  </script>
	  <script src="//platform.linkedin.com/in.js" type="text/javascript">
	    lang: en_US
	  </script>
	  </div>
	</div>
	<?php
}