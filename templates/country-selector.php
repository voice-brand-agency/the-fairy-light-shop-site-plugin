<?php
/**
 * Country selector form
 *
 * @author      Nicholas Byfleet, oscargare
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( $countries ) : ?>

	<form name="tfls-country-selector" method="post">
		<select name="tfls-manual-country">
			<?php foreach ( $countries as $key => $value ) : ?>
				<option
					value="<?php echo $key ?>" <?php echo selected( $key, WC()->customer->country ); ?> ><?php echo $value; ?></option>
			<?php endforeach; ?>
		</select>
		<input type="submit" value="select"/>
	</form>

<?php endif; ?>