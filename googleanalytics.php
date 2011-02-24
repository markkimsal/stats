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

  var _gaq = _gaq || [];
  _gaq.push([\'_setAccount\', \''.$gaId.'\']);
  _gaq.push([\'_trackPageview\']);

  (function() {
    var ga = document.createElement(\'script\'); ga.type = \'text/javascript\'; ga.async = true;
    ga.src = (\'https:\' == document.location.protocol ? \'https://ssl\' : \'http://www\') + \'.google-analytics.com/ga.js\';
    var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>';
        }
	}
}
