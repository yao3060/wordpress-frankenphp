<hr />
</div>


<div id="footer">

<!-- If you'd like to support WordPress, having the "powered by" link somewhere on your blog is the best way; it's our only promotion or advertising. -->
  <div id="footercontainer">

        <h1><a href="<?php echo get_option('home'); ?>/"><?php bloginfo('name'); ?></a></h1>
	<div class="description"><?php bloginfo('description'); ?></div>

<ul>
<?php if (!is_home()){ ?><li><a href="<?php echo get_settings('home'); ?>">Home<?php echo $langblog;?></a></li><?php } ?>
<?php wp_list_pages('sort_column=menu_order&depth=1&title_li='); ?>
</ul>
	<p>
		<?php bloginfo('name'); ?> is proudly powered by
		<a href="http://wordpress.org/">WordPress</a>, and uses the <a href="http://ozanonay.com/blog/blogging/headless-wordpress-theme">Headless theme</a> by <a href="http://www.ozanonay.com">Ozan Onay</a>.</p><p>
		You can subscribe to an RSS feed of <a href="<?php bloginfo('rss2_url'); ?>">entries</a>
		or <a href="<?php bloginfo('comments_rss2_url'); ?>">comments</a>.
		<!-- <?php echo get_num_queries(); ?> queries. <?php timer_stop(1); ?> seconds. -->
	</p>
  </div>
</div>

		<?php wp_footer(); ?>
</body>
</html>
