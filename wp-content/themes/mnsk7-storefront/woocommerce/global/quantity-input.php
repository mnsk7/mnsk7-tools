<?php
/**
 * Product quantity inputs
 *
 * Override: mnsk7-storefront. Na PDP etykieta i aria-label tylko "Ilość" (bez nazwy produktu) — a11y.
 *
 * @see     woocommerce/templates/global/quantity-input.php
 * @package WooCommerce\Templates
 * @version 10.1.0
 *
 * @var bool   $readonly If the input should be set to readonly mode.
 * @var string $type     The input type attribute.
 */

defined( 'ABSPATH' ) || exit;

$label = esc_html__( 'Ilość', 'mnsk7-storefront' );
$is_locked_qty = $readonly || $type === 'hidden' || ( isset( $min_value, $max_value ) && (string) $min_value !== '' && (string) $max_value !== '' && (float) $min_value === (float) $max_value );
$quantity_classes = 'quantity' . ( $is_locked_qty ? ' quantity--locked' : ' quantity--stepper' );
$input_type = $is_locked_qty ? 'hidden' : $type;
$quantity_display = sprintf(
	/* translators: %s: quantity value */
	__( '%s szt.', 'mnsk7-storefront' ),
	wc_stock_amount( $input_value )
);

?>
<div class="<?php echo esc_attr( $quantity_classes ); ?>">
	<?php
	do_action( 'woocommerce_before_quantity_input_field' );
	?>
	<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $label ); ?></label>
	<?php if ( ! $is_locked_qty ) : ?>
		<button type="button" class="mnsk7-qty-btn mnsk7-qty-btn--minus" aria-label="<?php esc_attr_e( 'Zmniejsz ilość', 'mnsk7-storefront' ); ?>">&minus;</button>
	<?php endif; ?>
	<input
		type="<?php echo esc_attr( $input_type ); ?>"
		<?php echo $readonly ? 'readonly="readonly"' : ''; ?>
		id="<?php echo esc_attr( $input_id ); ?>"
		class="<?php echo esc_attr( join( ' ', (array) $classes ) ); ?>"
		name="<?php echo esc_attr( $input_name ); ?>"
		value="<?php echo esc_attr( $input_value ); ?>"
		aria-label="<?php echo esc_attr( $label ); ?>"
		<?php if ( in_array( $input_type, array( 'text', 'search', 'tel', 'url', 'email', 'password' ), true ) ) : ?>
			size="4"
		<?php endif; ?>
		min="<?php echo esc_attr( $min_value ); ?>"
		<?php if ( 0 < $max_value ) : ?>
			max="<?php echo esc_attr( $max_value ); ?>"
		<?php endif; ?>
		<?php if ( ! $readonly ) : ?>
			step="<?php echo esc_attr( $step ); ?>"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
			inputmode="<?php echo esc_attr( $inputmode ); ?>"
			autocomplete="<?php echo esc_attr( isset( $autocomplete ) ? $autocomplete : 'on' ); ?>"
		<?php endif; ?>
	/>
	<?php if ( $is_locked_qty ) : ?>
		<span class="mnsk7-quantity-lock" aria-hidden="true"><?php echo esc_html( $quantity_display ); ?></span>
	<?php else : ?>
		<button type="button" class="mnsk7-qty-btn mnsk7-qty-btn--plus" aria-label="<?php esc_attr_e( 'Zwiększ ilość', 'mnsk7-storefront' ); ?>">+</button>
	<?php endif; ?>
	<?php
	do_action( 'woocommerce_after_quantity_input_field' );
	?>
</div>
<?php
