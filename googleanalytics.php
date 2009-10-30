<?php
/**
 * Wrapper for Google Analytics stat collecting.
 *
 * @see http://www.google.com/analytics/
 */
class Cgn_Service_Stats_Googleanalytics extends Cgn_Service {

	function  setTrackingBug($sectionName, $extraTitle = '') {
 		$gaId    = Cgn_ObjectStore::getConfig('config://layout/googleanalytics/id');

		if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ) {
			$scheme = 'https';
		} else {
			$scheme = 'http';
		}
		if ($extraTitle == '') {
			$extraTitle = 'document.title';
		} else {
			$extraTitle = "'".addslashes($extraTitle)."'";
		}

echo '
	<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));
</script>
	<script type="text/javascript">
try {
	var pageTracker = _gat._getTracker("'.$gaId.'");
	pageTracker._trackPageview();
} catch(err) {}</script>
	}
</script>
';
	}
}
