<?php get_header(); ?>

	<div id="content">

	<?php if (have_posts()) : ?>

		<?php while (have_posts()) : the_post(); ?>

			<div class="post" id="post-<?php the_ID(); ?>">
				<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>

				<div class="entry">
					<?php the_excerpt('Read the rest of this entry &raquo;'); ?>
				</div>

				<p class="postmetadata">Posted <?php the_time('F jS, Y') ?> <!-- by <?php the_author() ?> <?php the_tags(', tagged: ', ', ', '&nbsp;'); ?> --> in <?php the_category(', ') ?>. <?php the_tags( 'Tagged: ', ', ', '.'); ?> <?php edit_post_link('Edit', ' | ', ' | '); ?> <!-- <?php comments_popup_link('No Comments &#187;', '1 Comment &#187;', '% Comments &#187;'); ?>--></p>
			</div>

		<?php endwhile; ?>

	<?php else : ?>

		<h2 class="center">Not Found</h2>
		<p class="center">Sorry, but you are looking for something that isn't here.</p>
		<?php include (TEMPLATEPATH . "/searchform.php"); ?>

	<?php endif; ?>

	</div>

<?php get_sidebar(); ?>

<?php get_footer(); ?>
