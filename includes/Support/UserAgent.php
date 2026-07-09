<?php
/**
 * Lightweight user-agent parser.
 *
 * Derives a human-friendly browser label (name + major version), operating
 * system, and device type from a raw User-Agent string without external
 * dependencies. Intended for submission diagnostics, not exhaustive fingerprinting.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Parses User-Agent strings into browser, OS, and device metadata.
 */
class UserAgent {

	/**
	 * Parse a raw User-Agent string.
	 *
	 * @param string $ua Raw User-Agent header.
	 * @return array{browser:string,browser_name:string,browser_version:string,os:string,device:string,raw:string}
	 */
	public static function parse( $ua ) {
		$ua = (string) $ua;

		$browser = self::browser( $ua );
		$os      = self::os( $ua );
		$device  = self::device( $ua );

		$label = trim( $browser['name'] . ' ' . $browser['version'] );

		return array(
			'browser'         => '' !== $label ? $label : __( 'Unknown', 'activeforms' ),
			'browser_name'    => $browser['name'],
			'browser_version' => $browser['version'],
			'os'              => $os,
			'device'          => $device,
			'raw'             => $ua,
		);
	}

	/**
	 * Identify the browser name and major version.
	 *
	 * Order matters: more specific tokens (Edge, Opera, brand Chromium forks)
	 * are matched before the generic Chrome/Safari fallbacks they masquerade as.
	 *
	 * @param string $ua User-Agent.
	 * @return array{name:string,version:string}
	 */
	protected static function browser( $ua ) {
		$map = array(
			'Edge'              => '/Edg(?:e|A|iOS)?\/([0-9.]+)/i',
			'Opera'             => '/(?:OPR|Opera)\/([0-9.]+)/i',
			'Samsung Internet'  => '/SamsungBrowser\/([0-9.]+)/i',
			'Vivaldi'           => '/Vivaldi\/([0-9.]+)/i',
			'Brave'             => '/Brave\/([0-9.]+)/i',
			'UC Browser'        => '/UCBrowser\/([0-9.]+)/i',
			// Legacy IE only (≤10); IE11 reports "Trident" and is caught above. The
			// bare "rv:" token must not appear here — Firefox UAs include it too.
			'Internet Explorer' => '/MSIE\s([0-9.]+)/i',
			'Firefox'           => '/(?:Firefox|FxiOS)\/([0-9.]+)/i',
			'Chrome'            => '/(?:Chrome|CriOS)\/([0-9.]+)/i',
			'Safari'            => '/Version\/([0-9.]+).*Safari/i',
		);

		// IE 11 hides "MSIE"; detect Trident explicitly before Safari fallbacks.
		if ( false !== strpos( $ua, 'Trident' ) && preg_match( '/rv:([0-9.]+)/i', $ua, $m ) ) {
			return array(
				'name'    => 'Internet Explorer',
				'version' => self::major( $m[1] ),
			);
		}

		foreach ( $map as $name => $pattern ) {
			if ( preg_match( $pattern, $ua, $m ) ) {
				return array(
					'name'    => $name,
					'version' => isset( $m[1] ) ? self::major( $m[1] ) : '',
				);
			}
		}

		return array(
			'name'    => '',
			'version' => '',
		);
	}

	/**
	 * Identify the operating system.
	 *
	 * @param string $ua User-Agent.
	 * @return string
	 */
	protected static function os( $ua ) {
		$patterns = array(
			'/Windows NT 10\.0/i'    => 'Windows 10/11',
			'/Windows NT 6\.3/i'     => 'Windows 8.1',
			'/Windows NT 6\.2/i'     => 'Windows 8',
			'/Windows NT 6\.1/i'     => 'Windows 7',
			'/Windows/i'             => 'Windows',
			'/iPhone|iPad|iPod/i'    => 'iOS',
			'/Mac OS X|Macintosh/i'  => 'macOS',
			'/Android/i'             => 'Android',
			'/(?:CrOS)/i'            => 'ChromeOS',
			'/Linux/i'               => 'Linux',
		);

		foreach ( $patterns as $pattern => $label ) {
			if ( preg_match( $pattern, $ua ) ) {
				return $label;
			}
		}

		return __( 'Unknown', 'activeforms' );
	}

	/**
	 * Classify the device type.
	 *
	 * @param string $ua User-Agent.
	 * @return string One of "Mobile", "Tablet", "Desktop".
	 */
	protected static function device( $ua ) {
		if ( preg_match( '/iPad|Tablet|PlayBook|(?=.*\bAndroid\b)(?=.*\bMobile\b)?Silk/i', $ua )
			|| ( preg_match( '/Android/i', $ua ) && ! preg_match( '/Mobile/i', $ua ) ) ) {
			return 'Tablet';
		}

		if ( preg_match( '/Mobi|iPhone|iPod|Windows Phone|BlackBerry|Opera Mini|IEMobile/i', $ua ) ) {
			return 'Mobile';
		}

		return 'Desktop';
	}

	/**
	 * Reduce a dotted version to major.minor for compact display.
	 *
	 * @param string $version Full version string.
	 * @return string
	 */
	protected static function major( $version ) {
		$parts = explode( '.', $version );
		return isset( $parts[1] ) ? $parts[0] . '.' . $parts[1] : $parts[0];
	}
}
