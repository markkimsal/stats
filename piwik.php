<?php
/**
 * Wrapper for piwik stats which hides cookie domains and API keys.
 *
 * This module is basically a pass-through to your real piwik server, but it should 
 * hide your API key from the requesting browser if you're showing custom graphs.
 *
 * Showing stats is disabled for piwik until further notice.
 */
class Cgn_Service_Stats_Piwik extends Cgn_Service {

	public function fetchGraphData($dataUrl) {

			$data =  file_get_contents($dataUrl);

			//remove "links" because they contain the auth_token
			$data = preg_replace('/^&links(.)*$/m','',$data);
			//Y-m-d format makes it too scrunched for small boxes, remove "2008-"
			$year = date('Y');
			$data = preg_replace('/('.$year.'\-)/m','',$data);

			return $data;
	}

	public function _makeGraphVisitsUrl($host, $path, $token, $siteid=1, $startDay=-1, $today=-1) {
			$dataUrl =  'http://'.$host.$path .'index.php';
			$dataUrl .= '?module=VisitsSummary';
			$dataUrl .= '&action=getEvolutionGraph';
			$dataUrl .= '&idSite='.$siteid;
			$dataUrl .= '&period=day';
			$dataUrl .= '&columns%5B%5D=nb_visits';
			$dataUrl .= '&date='. $startDay;
			$dataUrl .= '%2C'. $today;
			$dataUrl .= '&viewDataTable=generateDataChartEvolution';
			$dataUrl .= '&disableLink=1';
			$dataUrl .= '&token_auth='.$token;
			return $dataUrl;
	}

	public function _makeGraphVisitsApiUrl($host, $path, $token, $siteid=1, $startDay=-1, $today=-1) {
			$dataUrl =  'http://'.$host.$path .'index.php';
			$dataUrl .= '?module=API';
			$dataUrl .= '&method=VisitsSummary.get';
			$dataUrl .= '&idSite='.$siteid;
			$dataUrl .= '&period=day';
//			$dataUrl .= '&date='. $startDay;
//			$dataUrl .= '%2C'. $today;
			$dataUrl .= '&date=last10';
			$dataUrl .= '&format=json';
//			$dataUrl .= '&action=getEvolutionGraph';
//			$dataUrl .= '&columns%5B%5D=nb_visits';
//			$dataUrl .= '&viewDataTable=generateDataChartPie';
			$dataUrl .= '&token_auth='.$token;
//			$dataUrl .= '&loading="Loading..."';
//			$dataUrl .= '&moduleToWidgetize=VisitsSummary';
//			$dataUrl .= '&actionToWidgetize=getEvolutionGraph';

			return $dataUrl;
	}

	/**
	 * Takes API JSON data and formats for the flahs chart, like
	 * "viewDataTable=generateDataChartEvolution" should do already
	 */
	public function _reformatForChart($json) {
		if (!function_exists('json_decode')) return array();

		$jsonData = json_decode($json,1);
		$data = array('elements'=>array());
		$data['elements']['type'] = 'line';
		foreach ($jsonData as $k=>$v) {
			$data['elements']['values'][] = 
				array('type'=>'hollow-dot',
					'value'=>$v,
					'tip'=>$k.'<br/>'.$v.'Visits');
		}

		$data['elements']['dot-style'] = array('type'=>'hollow-dot', 'dot-size'=>'3', 'colour'=>'0x3357A0');
		$data['elements']['text'] = 'Visits';
		$data['elements']['font-size'] = '11';
		$data['elements']['width'] = '1';
		$data['elements']['colour'] = '0x3357A0';
		return json_encode($data);
		return $json;
	}

	function requestGraphVisitsEvent($req, &$t) {
		$readFromCache = FALSE;
		$needsRecache = TRUE;
		if (file_exists('./var/piwik_cache.bin')) {
			$data = '';
			$f = fopen('./var/piwik_cache.bin', 'r');
			$fstat = fstat($f);
			while (!feof($f)) {
				$data .= fgets($f, 4096);
			}
			fclose($f);
			$readFromCache = TRUE;
			if (is_array($fstat) && isset($fstat['mtime']))	{
				$needsRecache = $fstat['mtime'] < (time() - 3600);
			}
		}

		$needsRecache=true;
		if ($needsRecache === TRUE) {
			$host    = Cgn_ObjectStore::getConfig('config://layout/piwik/host');
			$path    = Cgn_ObjectStore::getConfig('config://layout/piwik/path');
			$siteid  = Cgn_ObjectStore::getConfig('config://layout/piwik/siteid');
			$token   = Cgn_ObjectStore::getConfig('config://layout/piwik/authtoken');
			$today   = date('Y-m-d');
			$startDay = date('Y-m-d', (time() - 3600*24*12));

			//old style, doesn't work anymore
			//re: http://dev.piwik.org/trac/ticket/674
			//$dataUrl = $this->_makeGraphVisitsUrl($host, $path, $token, $siteid, $startDay, $today);
			$dataUrl = $this->_makeGraphVisitsApiUrl($host, $path, $token, $siteid, $startDay, $today);

			$data = $this->fetchGraphData($dataUrl);

			//piwik will no longer format to the flash chart format for you,
			//... not sure why not
			//re: http://dev.piwik.org/trac/ticket/674
			$data = $this->_reFormatForChart($data);
		}


		header('Content-Type: text; charset: utf-8');
		echo $data;
		exit();

		//unset the logger for this hit, it's only a proxy
		Cgn_ObjectStore::clearConfig("object://default/handler/log");
		$this->presenter = 'none';

		//cache
		if ($needsRecache === TRUE) {
			$cache = fopen('./var/piwik_cache.bin', 'w');
			if ($cache) {
				fputs($cache,$data);
				fclose($cache);
			}
		}
	}


	function showGraphVisitsEvent($req, &$t) {
$this->showGraphVisits();
exit();
}
	/**
	 * Outputs a flash graph that gathers stats from piwik about the last 7 days.
	 *
	 * Requires piwik.host
	 * Requires piwik.path
	 * Requires piwik.siteid
	 */
	function showGraphVisits() {
		/*
		$host    = Cgn_ObjectStore::getConfig('config://layout/piwik/host');
		$path    = Cgn_ObjectStore::getConfig('config://layout/piwik/path');
		$siteid  = Cgn_ObjectStore::getConfig('config://layout/piwik/siteid');
		$token   = Cgn_ObjectStore::getConfig('config://layout/piwik/authtoken');
		$today   = date('Y-m-d');
		$startDay = date('Y-m-d', (time() - 3600*24*7));

		$encodedHostPath = urlencode($host.$path);
		 */

		$dataUrl  =  cgn_appurl('stats','piwik','requestGraphVisits');
		$flashUrl =  cgn_url().'media/open-flash-chart.swf';

echo '
			<div class="items"><div class="widget">
				<div class="handle">
					<div class="widgetTitle">Last visits graph</div>
				</div>
				<div class="widgetLoading" style="display: none;">Loading widget, please wait...</div>
				<div class="widgetDiv" id="getLastVisitsGraph" plugin="VisitsSummary" style="display: block;">
					<div class="parentDivGraphEvolution" id="VisitsSummarygetLastVisitsGraph">
					<div id="VisitsSummarygetLastVisitsGraphFlashContent">
					<embed width="100%" height="150"
						flashvars="data-file='.urlencode($dataUrl).'"
						allowscriptaccess="sameDomain"
						quality="high"
						bgcolor="#FFFFFF"
						name="VisitsSummarygetLastVisitsGraphChart_swf" 
						id="VisitsSummarygetLastVisitsGraphChart_swf" 
						style=""
						src="'.$flashUrl.'" type="application/x-shockwave-flash"/>
				</div>

			<noscript>
			<div>
                <object classid=\'clsid:d27cdb6e-ae6d-11cf-96b8-444553540000\'
					codebase=\'http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0\'
					width=\'100%\' height=\'150\' id=\'VisitsSummarygetLastVisitsGraphChart\' >
							<param name=\'movie\' value=\''.$flashUrl.'?data-file='.urlencode($dataUrl).'\' />
							<param name=\'allowScriptAccess\' value=\'sameDomain\' />
							<embed src=\''.$flashUrl.'?data-file='.urlencode($dataUrl).'\' allowScriptAccess=\'sameDomain\'
							quality=\'high\' bgcolor=\'#FFFFFF\' 
							width=\'100%\' height=\'150\' 
							name=\'open-flash-chart\' type=\'application/x-shockwave-flash\' 
							id=\'VisitsSummarygetLastVisitsGraphChart\' />
                </object>
				</div>
			</noscript>
		</div></div></div></div>
                        Live stats by: <a href="http://piwik.org">piwik</a>
';
	}


	function  setTrackingBug($sectionName, $extraTitle = '') {
 		$host    = Cgn_ObjectStore::getConfig('config://layout/piwik/host');
		$path    = Cgn_ObjectStore::getConfig('config://layout/piwik/path');
		$siteid  = Cgn_ObjectStore::getConfig('config://layout/piwik/siteid');

		if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ) {
			$scheme = 'https';
		} else {
			$scheme = 'http';
		}
			
echo '
<!-- Piwik -->
<script language="javascript" src="'.$scheme.'://'. $host.$path .'piwik.js" type="text/javascript"></script>
<script type="text/javascript">
<!--
piwik_action_name = document.title + \''. $extraTitle .'\';
piwik_idsite = '. $siteid .';
piwik_url = \''.$scheme.'://'. $host.$path .'piwik.php\';
piwik_log(piwik_action_name, piwik_idsite, piwik_url);
//-->

</script><object>
<noscript><p>Website analytics <img src="'.$scheme.'://'. $host.$path .'piwik.php" style="border:none;padding:none;" alt="piwik"/></p>
</noscript></object>
<!-- /Piwik --> 
';
	}


	function  setTrackingBugHome($sectionName) {
		$this->setTrackingBug($sectionName, ' Home');
	}

}
