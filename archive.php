<?php
/**
 * The template for displaying post archives
 *
 * @package Layers
 * @since Layers 1.0.0
 */

get_header(); ?>
<?php get_template_part( 'partials/header' , 'page-title' ); ?>
<section class="container content-main archive clearfix">
	<?php get_sidebar( 'left' ); ?>

	<div <?php layers_center_column_class(); ?>>
		<?php if( have_posts() ) : ?>
			<?php while( have_posts() ) : the_post(); ?>
				<?php get_template_part( 'partials/content' , 'list' ); ?>
			<?php endwhile; // while has_post(); ?>
			<?php the_posts_pagination(); ?>
		<?php endif; // if has_post() ?>
	</div>

	<?php get_sidebar( 'right' ); ?>
</section>
<?php get_footer();
