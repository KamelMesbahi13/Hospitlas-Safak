<?php
/**
 * Single Hospital: Video Tour Embed Part with Native Player & Official Play Controls.
 *
 * Renders the native YouTube/Vimeo embed player directly with YouTube's official
 * thumbnail, responsive 16:9 ratio, and default red/white play button.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

$post_id   = isset( $post_id ) ? (int) $post_id : get_the_ID();
$lang      = isset( $lang ) ? $lang : shm_post_lang( $post_id );
$is_rtl    = shm_is_rtl( $lang );
$dir       = $is_rtl ? 'rtl' : 'ltr';
$video_url = shm_meta( $post_id, '_hospital_video_url' );

if ( empty( $video_url ) ) {
	return;
}

$video = shm_parse_video( $video_url );
if ( ! $video ) {
	return;
}
?>

<section id="shm-video" class="safak-dept-wrapper shm-video-section" dir="<?php echo esc_attr( $dir ); ?>" lang="<?php echo esc_attr( $lang ); ?>">
	<div class="safak-dept-header-row">
		<div class="safak-header-start">
			<span class="safak-kicker"><?php echo esc_html( shm_t( 'gallery_kicker', $lang ) ); ?></span>
			<h2 class="safak-headline"><?php echo esc_html( shm_t( 'video', $lang ) ); ?></h2>
		</div>
	</div>

	<div class="shm-video-player-wrap">
		<?php if ( 'youtube' === $video['provider'] ) : ?>
			<div class="shm-video-responsive">
				<iframe
					src="https://www.youtube.com/embed/<?php echo esc_attr( $video['id'] ); ?>?rel=0&modestbranding=1"
					title="<?php echo esc_attr( get_the_title( $post_id ) ); ?>"
					frameborder="0"
					allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
					allowfullscreen
					loading="lazy"></iframe>
			</div>
		<?php elseif ( 'vimeo' === $video['provider'] ) : ?>
			<div class="shm-video-responsive">
				<iframe
					src="https://player.vimeo.com/video/<?php echo esc_attr( $video['id'] ); ?>?dnt=1"
					title="<?php echo esc_attr( get_the_title( $post_id ) ); ?>"
					frameborder="0"
					allow="autoplay; fullscreen; picture-in-picture"
					allowfullscreen
					loading="lazy"></iframe>
			</div>
		<?php elseif ( 'file' === $video['provider'] ) : ?>
			<div class="shm-video-responsive">
				<video controls preload="metadata" class="shm-video-element">
					<source src="<?php echo esc_url( $video['embed'] ); ?>" type="video/mp4">
					<?php esc_html_e( 'Your browser does not support HTML5 video.', 'safak-hospitals' ); ?>
				</video>
			</div>
		<?php endif; ?>
	</div>
</section>
