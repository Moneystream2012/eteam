<?php
/**
 * Class MigrationCommand
 *
 * @author Tarasenko Andrey
 */

Yii::import('application.components.monitoring.*');

/**
 * Console command for system monitoring
 *
 * The implementation is based on the Observer pattern
 */
class MonitoringCommand extends CronConsoleCommand
{
    /**
     * Creates a monitoring object (subject), adds notification systems (observers) to it, starts checks and sends alerts to subscribers
     */
    public function actionIndex()
    {
        $monitor = new SystemMonitoring();

        $email = new EmailMonitoringNotificator($monitor);
        $sms = new SMSMonitoringNotificator($monitor);
        new MonitoringStatus($monitor);

	    $this->runCommands($monitor);

        $email->notify('Monitoring system notification');
        $sms->notify();
    }

	/**
	 * Launches monitoring commands
	 *
	 * The method runs both from the web application and from the console. If no script name is specified,
	 *
	 * @param SystemMonitoring $monitor
	 * @param string $scriptName
	 */
	public function runCommands(SystemMonitoring $monitor, $scriptName = '')
	{
		if (empty($scriptName)) {
			$checkNames = MonitoringCheck::model()->getCheckNames();
			foreach ($checkNames as $checkName) {
				$this->runCommand($monitor, $checkName);
			}
		} else {
			$this->runCommand($monitor, $scriptName);
		}
	}

    /**
     * Returns critical detections from system settings
     *
     * @return array Detects array
     */
    protected function getInstallerCriticalDetects()
    {
        $detects = Settings::get('installer_detects', '');
        $detects = explode(',', $detects);
        foreach ($detects as $key => $detect) {
            $detects[$key] = trim($detect);
        }
        $detects = array_filter($detects);
        return $detects;
    }

    /**
     * Running a specific command
     *
     * Add new checks here
     *
     * @param SystemMonitoring $monitor
     * @param $scriptName
     *
     * @return bool
     */
    protected function runCommand(SystemMonitoring $monitor, $scriptName)
    {
        switch ($scriptName) {
            case 'event_scheduler': $monitor->checkEventScheduler(); break;
            case 'downloads_log':
            case 'preinit_log':
            case 'init_log':
            case 'offer_log':
            case 'start_log':
            case 'done_log': $monitor->checkTableDelay($scriptName); break;
            case 'downloads_unique_log':
            case 'preinit_unique_log':
            case 'init_unique_log':
            case 'offer_unique_log':
            case 'start_unique_log':
            case 'done_unique_log': $monitor->checkTableDelay($scriptName, 700); break;
            case 'external_stat_log': $monitor->checkTableDelay($scriptName, 900); break;
            case 'installer_key_site': $monitor->checkInstallerKeySiteDiff(); break;
            case 'broken_domains': $monitor->checkBrokenDomains(); break;
            case 'partitions': $monitor->checkPartitions(); break;
            case 'statistics': $monitor->checkStatistics(70); break;
            case 'init_internal_rates': $monitor->checkInitInternalRates(); break;
            case 'init_reseller_rates': $monitor->checkInitResellerRates(); break;
            case 'cpm': $monitor->checkCPM(1200); break;
            case 'downloads_delta': $monitor->checkDownloadsDelta(0.5, 1.6); break;
            case 'internal_conversion': $monitor->checkInternalConversion(); break;
            case 'reseller_conversion': $monitor->checkResellerConversion(); break;
            case 'low_advertizer_balance': $monitor->checkLowAdvertizerBalance(20); break;
            case 'cron': $monitor->checkCron(); break;
            case 'antivirus_service_availability': $monitor->checkAntivirusServiceAvailability(); break;
            case 'detects_in_installer': $monitor->checkDetectsInInstaller('regular/setup.exe', $this->getInstallerCriticalDetects()); break;
            case 'detects_in_installer_chrome': $monitor->checkDetectsInInstallerChrome('regular.chrome/setup.exe', $this->getInstallerCriticalDetects()); break;
            case 'detects_in_silent': $monitor->checkDetectsInSilent('regular/Launcher.exe', $this->getInstallerCriticalDetects()); break;
            case 'internal_pc_conversion': $monitor->checkInternalPCConversion(); break;
            case 'reseller_pc_conversion': $monitor->checkResellerPCConversion(); break;
            case 'operating_currency': $monitor->checkOperatingCurrency(); break;
            case 'actual_currency': $monitor->checkActualCurrency(); break;
            case 'reserved_domains': $monitor->checkReservedDomains(); break;
            case 'domain_baned_by_chrome': $monitor->checkDomainBanedByChrome(); break;
            case 'reserved_advertize_domains': $monitor->checkReservedAdvertizeDomains(); break;
            case 'user_domain_in_work': $monitor->checkUserDomainInWork(); break;
            default: return false;
        }
        return true;
    }
}
