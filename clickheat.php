<?php
/**
 * Wrapper for clickheat stat collecting.
 *
 * http://www.labsmedia.com/clickheat/index.html
 *
 * @see http://www.labsmedia.com/clickheat/index.html
 */
class Cgn_Service_Stats_Clickheat extends Cgn_Service {


	function  setTrackingBug($sectionName, $extraTitle = '') {
 		$host    = Cgn_ObjectStore::getConfig('config://layout/clickheat/host');
		$path    = Cgn_ObjectStore::getConfig('config://layout/clickheat/path');
		$siteid  = Cgn_ObjectStore::getConfig('config://layout/clickheat/siteid');

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

<script type="text/javascript" src="'.$scheme.'://'.$host.$path.'/js/clickheat.js"></script><noscript><p><a href="http://www.labsmedia.com/index.html">Open source tools</a></p></noscript><script type="text/javascript"><!--
try { clickHeatSite = \'\';clickHeatGroup ='.$extraTitle.';clickHeatServer = \''.$scheme.'://'.$host.$path.'/click.php\';initClickHeat(); } catch (err) {}
//-->
</script>
';
	}
}
