<?php
/**
 * File upload field (Pro).
 *
 * @package ActiveFormsPro
 */

namespace ActiveFormsPro\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Generic file upload (documents, archives, etc.).
 */
class FileUploadField extends AbstractUploadField {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type     = 'file_upload';
		$this->label    = __( 'File Upload', 'activeforms' );
		$this->icon     = 'upload';
		$this->category = 'general';
		$this->input    = true;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function default_extensions() {
		return array( 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'jpg', 'jpeg', 'png' );
	}
}
