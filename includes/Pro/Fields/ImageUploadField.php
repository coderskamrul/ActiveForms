<?php
/**
 * Image upload field (Pro).
 *
 * @package RadiusFormsPro
 */

namespace RadiusFormsPro\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Image-only upload that shows thumbnail previews of the chosen images.
 */
class ImageUploadField extends AbstractUploadField {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type     = 'image_upload';
		$this->label    = __( 'Image Upload', 'radiusforms' );
		$this->icon     = 'format-image';
		$this->category = 'general';
		$this->input    = true;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function default_extensions() {
		return array( 'jpg', 'jpeg', 'png', 'gif', 'webp' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function images_only() {
		return true;
	}
}
