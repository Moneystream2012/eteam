<?php
 
/**
 * Adds Google Analytics code
 *
 * @property string $gaTrackingId GA tracking id
 * @property array $filterIPs IPs on which GA is disabled
 */
class GoogleAnalytics extends CWidget
{
	/**
	 * @var string Google Analytics tracking id (UA-xxxxxx-xx)
	 */
    public $gaTrackingId = null;

	/**
	 * @var array IPs or subnets to be excluded (GA will be disabled)
	 *
	 * For example, 91.197.129.69 for IP, 91.202 for subnet
	 */
    public $filterIPs = array(
        '127.0.0.1',
    );

    /**
     * Inserts GA code
     */
    public function run()
    {
        if (Yii::app()->user->checkAccess('sendGoogleAnalytics')) {
            if (!$this->gaTrackingId) {
                $this->gaTrackingId = Yii::app()->params['gaTrackingId'];
            }

            if (!$this->gaTrackingId) {
                return;
            }

            $ip = @$_SERVER['REMOTE_ADDR'];
            foreach ($this->filterIPs as $value) {
                if (substr($ip, 0, strlen($value)) == $value) {
                    return;
                }
            }

            $this->render('googleAnalytics', array(
                'trackingId' => $this->gaTrackingId,
            ));
        }
    }
}
