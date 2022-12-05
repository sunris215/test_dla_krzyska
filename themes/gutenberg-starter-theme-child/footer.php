<?php
/**
 * The template for displaying the footer.
 *
 * Contains the body & html closing tags.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<footer id="colophon" class="site-footer">
    <div class="site-info">
        <a href="<?php echo esc_url( __( 'https://wordpress.org/', 'gutenberg-starter-theme' ) ); ?>"><?php
            /* translators: %s: CMS name, i.e. WordPress. */
            printf( esc_html__( 'Proudly powered by %s', 'gutenberg-starter-theme' ), 'WordPress' );
            ?></a>
        <span class="sep"> | </span>
        <?php
        /* translators: 1: Theme name, 2: Theme author. */
        printf( esc_html__( 'Theme: %s', 'gutenberg-starter-theme' ), '<a href="https://github.com/WordPress/gutenberg-starter-theme/">Gutenberg</a>' );
        ?>
    </div><!-- .site-info -->
</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>