<?php
/**
 * Single Hospital: Video Tour Embed Part.
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
			<div class="shm-video-facade" data-shm-video-facade data-embed="<?php echo esc_url( $video['embed'] ); ?>">
				<img src="<?php echo esc_url( $video['thumb'] ); ?>" alt="<?php echo esc_attr( get_the_title( $post_id ) ); ?>" loading="lazy" class="shm-video-poster" />
				<button type="button" class="shm-video-play-btn" aria-label="<?php echo esc_attr( shm_t( 'play_video', $lang ) ); ?>">
					<?php echo shm_icon( 'play', 'shm-play-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</button>
			</div>
		<?php elseif ( 'vimeo' === $video['provider'] ) : ?>
			<div class="shm-video-responsive">
				<iframe src="<?php echo esc_url( $video['embed'] ); ?>" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen loading="lazy" title="<?php echo esc_attr( get_the_title( $post_id ) ); ?>"></iframe>
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
