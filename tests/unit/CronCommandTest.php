<?php
/**
 * Class CronCommandTest
 *
 * @author Tarasenko Andrey
 */
Yii::import('application.commands.CronCommand');
Yii::import('application.components.Logger.ConsoleLogger');
/**
 * Class for testing CronCommand
 *
 * @author Tarasenko Andrey
 */
class CronCommandTest extends DbTestCase
{
    /**
     * @var CronCommand testing class instance
     */
    private $_cron;

    /**
     * @var array Fixtures
     */
    public $fixtures = array(
        'statistics' => ':statistics',
        'banner_statistics' => ':banner_statistics',
    );

    /**
     * Create testing class instance
     */
    public function setUp()
    {
        parent::setUp();
        $this->_cron = new CronCommand('cron', new CConsoleCommandRunner());
        $this->_cron->logger = new ConsoleLogger(false);
        Settings::set('critical_cpm', 400);
    }

    /**
     * Check does 15-minutes cron completed successfully
     *
     * @covers CronCommand::action15min()
     */
    public function testAction15minEnds()
    {
        $this->_cron->action15min();
    }

    /**
     * Check does 3-minutes cron completed successfully
     *
     * @covers CronCommand::action3min()
     */
    public function testAction3minEnds()
    {
        $this->_cron->action3min();
    }

    /**
     * Check does hourly cron completed successfully
     *
     * @covers CronCommand::actionHourly()
     */
    public function testActionHourlyEnds()
    {
        $this->_cron->actionHourly();
    }

    /**
     * Check does nightly_operation_day cron completed successfully
     *
     * @covers CronCommand::actionNightly()
     */
    public function testActionNightlyEnds()
    {
        $this->_cron->actionNightly();
    }

    /**
     * Check does History updater completed successfully
     *
     * @covers CronCommand::actionHistoryUpdate()
     */
    public function testActionHistoryUpdateEnds()
    {
        $this->_cron->actionHistoryUpdate();
    }

    /**
     * Check does Action statistic updater completed successfully
     *
     * @covers CronCommand::actionStatistics()
     */
    public function testActionStatisticsEnds()
    {
        $this->_cron->actionStatistics();
    }

    /**
     * Check does clear & agregate service working successfully
     * Проверяет успешность очистки и агрегации
     *
     * @covers CronCommand::actionCleanAndAggregate()
     */
    public function testActionCleanAndAggregateEnds()
    {
        $this->_cron->actionCleanAndAggregate();
    }

    /**
     * Проверяет успешность обновления статистики кампаний
     *
     * @covers CronCommand::actionStatCampaignCountry()
     */
    public function testActionStatCampaignCountryEnds()
    {
        $this->_cron->actionStatCampaignCountry();
    }

    /**
     * Тестирует запускаются ли в 15-ти минутном кроне необходимые методы
     *
     * @covers CronCommand::action15min()
     */
    public function testAction15min()
    {
        $stub = $this->getMockBuilder('CronCommand')
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    '_create_accountingday',
                    '_process_payments',
                    'rotateApiRequestLog',
                    '_send_advertizer_email_account_low',
                    'calculateSiteTrfficPurity',
                    '_domainState',
                    '_testDomainState',
                    '_launderedSubDomain',
                )
            )
        ->getMock();

        $stub->logger = new ConsoleLogger(false);

        $stub->expects($this->once())->method('_create_accountingday');
        $stub->expects($this->once())->method('_process_payments');
        $stub->expects($this->once())->method('rotateApiRequestLog');
        $stub->expects($this->once())->method('_send_advertizer_email_account_low');
        $stub->expects($this->once())->method('calculateSiteTrfficPurity');
        $stub->expects($this->once())->method('_domainState');
        $stub->expects($this->once())->method('_testDomainState');
        $stub->expects($this->once())->method('_launderedSubDomain');

        $stub->action15min();
    }

    /**
     * Тестирует запускаются ли в 3-х минутном кроне необходимые методы
     *
     * @covers CronCommand::action3min()
     */
    public function testAction3min()
    {
        $this->loadFixtures();

        $stub = $this->getMockBuilder('CronCommand')
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    '_ban_ip_rate',
                    '_process_company_payment_status',
                    '_process_internal_stat',
                    '_process_banner_stat',
                    '_check_company_critical_CPM',
                    '_check_company_IU',
                    '_process_company_limits',
                    '_update_current_statistics_dayly',
                    'updateStatisticsSiteInits',
                    '_process_companies_order',
                    'updateBalanceData',
                    'changeDomain',
                )
            )
            ->getMock();

        $stub->logger = new ConsoleLogger(false);

        $stub->expects($this->once())->method('_ban_ip_rate');
        $stub->expects($this->once())->method('_process_company_payment_status');
        $stub->expects($this->once())->method('_process_internal_stat');
        $stub->expects($this->once())->method('_process_banner_stat');
        $stub->expects($this->once())->method('_check_company_critical_CPM');
        $stub->expects($this->once())->method('_check_company_IU');
        $stub->expects($this->once())->method('_process_company_limits');
        $stub->expects($this->once())->method('_update_current_statistics_dayly');
        $stub->expects($this->once())->method('updateStatisticsSiteInits');
        $stub->expects($this->once())->method('_process_companies_order');
        $stub->expects($this->once())->method('updateBalanceData');
        $stub->expects($this->once())->method('changeDomain');

        $stub->action3min();
    }

    /**
     * Тестирует запускаются ли в 3-х минутном кроне необходимые методы
     *
     * @covers CronCommand::action3min()
     */
    public function testActionNightly()
    {
        $stub = $this->getMockBuilder('CronCommand')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'currencyUpdate',
                'removeOldSiteCategoryData',
                'removeOldIpDateBan',
            ))
        ->getMock();

        $stub->logger = new ConsoleLogger(false);

        $stub->expects($this->once())->method('currencyUpdate');
        $stub->expects($this->once())->method('removeOldSiteCategoryData');
        $stub->expects($this->once())->method('removeOldIpDateBan');

        $stub->actionNightly();
    }

    /**
     * Тестирует запускаются ли в часовом кроне необходимые методы
     *
     * @covers CronCommand::actionHourly()
     */
    public function testActionHourly()
    {
        $stub = $this->getMockBuilder('CronCommand')
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    '_amonetize_bundles_update',
                    '_launderedDomainState',
                    '_advertizeDomainState',
                    'collectSuspiciousProcesses',
                )
            )
            ->getMock();

        $stub->logger = new ConsoleLogger(false);

        $stub->expects($this->once())->method('_amonetize_bundles_update');
        $stub->expects($this->once())->method('_launderedDomainState');
        $stub->expects($this->once())->method('_advertizeDomainState');
        $stub->expects($this->once())->method('collectSuspiciousProcesses');

        $stub->actionHourly();
    }

    /**
     * Проверяет успешно ли завершается метод changeDomain()
     *
     * @covers CronCommand::changeDomain()
     */
    public function testChangeDomain()
    {
        $method = new ReflectionMethod('CronCommand', 'changeDomain');
        $method->setAccessible(true);
        $method->invokeArgs($this->_cron, array());
    }

    /**
     * Проверяет успешно ли завершается метод updateBalanceData()
     *
     * @covers CronCommand::updateBalanceData()
     */
    public function testUpdateBalanceData()
    {
        $method = new ReflectionMethod('CronCommand', 'updateBalanceData');
        $method->setAccessible(true);
        $method->invokeArgs($this->_cron, array());
    }

    /**
     * Проверяет успешно ли завершается метод _process_companies_order()
     *
     * @covers CronCommand::_process_companies_order()
     */
    public function testProcessCompaniesOrder()
    {
        $method = new ReflectionMethod('CronCommand', '_process_companies_order');
        $method->setAccessible(true);
        $method->invokeArgs($this->_cron, array());
    }

    /**
     * Проверяет успешно ли завершается метод updateStatisticsSiteInits()
     *
     * @covers CronCommand::updateStatisticsSiteInits()
     */
    public function testUpdateStatisticsSiteInits()
    {
        $method = new ReflectionMethod('CronCommand', 'updateStatisticsSiteInits');
        $method->setAccessible(true);
        $method->invokeArgs($this->_cron, array());
    }

    /**
     * Проверяет успешно ли завершается метод _update_current_statistics_dayly()
     *
     * @covers CronCommand::_update_current_statistics_dayly()
     */
    public function testUpdateCurrentStatisticsDayly()
    {
        $method = new ReflectionMethod('CronCommand', '_update_current_statistics_dayly');
        $method->setAccessible(true);
        $method->invokeArgs($this->_cron, array());
    }

    /**
     * Проверяет успешно ли завершается метод _process_company_limits()
     *
     * @covers CronCommand::_process_company_limits()
     */
    public function testProcessCompanyLimits()
    {
        $method = new ReflectionMethod('CronCommand', '_process_company_limits');
        $method->setAccessible(true);
        $method->invokeArgs($this->_cron, array());
    }

    /**
     * Проверяет успешно ли завершается метод _check_company_IU()
     *
     * @covers CronCommand::_check_company_IU()
     */
    public function testCheckCompanyIU()
    {
        $method = new ReflectionMethod('CronCommand', '_check_company_IU');
        $method->setAccessible(true);
        $method->invokeArgs($this->_cron, array());
    }

    /**
     * Проверяет успешно ли завершается метод _check_company_critical_CPM()
     *
     * @covers CronCommand::_check_company_critical_CPM()
     */
    public function testCheckCompanyCriticalCPM()
    {
        $method = new ReflectionMethod('CronCommand', '_check_company_critical_CPM');
        $method->setAccessible(true);
        $method->invokeArgs($this->_cron, array());
    }

    /**
     * Проверяет успешно ли завершается метод _process_banner_stat()
     *
     * @covers CronCommand::_process_banner_stat()
     */
    public function testProcessBannerStat()
    {
        $method = new ReflectionMethod('CronCommand', '_process_banner_stat');
        $method->setAccessible(true);
        $method->invokeArgs($this->_cron, array());
    }

    /**
     * Проверяет успешно ли завершается метод _process_internal_stat()
     *
     * @covers CronCommand::_process_internal_stat()
     */
    public function testProcessInternalStat()
    {
        $method = new ReflectionMethod('CronCommand', '_process_internal_stat');
        $method->setAccessible(true);
        $method->invokeArgs($this->_cron, array());
    }

    /**
     * Проверяет успешно ли завершается метод _process_company_payment_status()
     *
     * @covers CronCommand::_process_company_payment_status()
     */
    public function testProcessCompanyPaymentStatus()
    {
        $method = new ReflectionMethod('CronCommand', '_process_company_payment_status');
        $method->setAccessible(true);
        $method->invokeArgs($this->_cron, array());
    }

    /**
     * Проверяет успешно ли завершается метод _ban_ip_rate()
     *
     * @covers CronCommand::_ban_ip_rate()
     */
    public function testBanIpRate()
    {
        $method = new ReflectionMethod('CronCommand', '_ban_ip_rate');
        $method->setAccessible(true);
        $method->invokeArgs($this->_cron, array());
    }

    /**
     * Проверяет успешно ли завершается метод _create_accountingday()
     *
     * @covers CronCommand::_create_accountingday()
     */
    public function testCreateAccountingDay()
    {
        $method = new ReflectionMethod('CronCommand', '_create_accountingday');
        $method->setAccessible(true);
        $method->invokeArgs($this->_cron, array());
    }

    /**
     * Проверяет успешно ли завершается метод _process_payments()
     *
     * @covers CronCommand::_process_payments()
     */
    public function testProcessPayments()
    {
        $method = new ReflectionMethod('CronCommand', '_process_payments');
        $method->setAccessible(true);
        $method->invokeArgs($this->_cron, array());
    }

    /**
     * Проверяет успешно ли завершается метод rotateApiRequestLog()
     *
     * @covers CronCommand::rotateApiRequestLog()
     */
    public function testRotateApiRequestLog()
    {
        $method = new ReflectionMethod('CronCommand', 'rotateApiRequestLog');
        $method->setAccessible(true);
        $method->invokeArgs($this->_cron, array());
    }

    /**
     * Проверяет успешно ли завершается метод _send_advertizer_email_account_low()
     *
     * @covers CronCommand::_send_advertizer_email_account_low()
     */
    public function testSendAdvertizerEmailAccountLow()
    {
        $method = new ReflectionMethod('CronCommand', '_send_advertizer_email_account_low');
        $method->setAccessible(true);
        $method->invokeArgs($this->_cron, array());
    }

    /**
     * Проверяет успешно ли завершается метод calculateSiteTrfficPurity()
     *
     * @covers CronCommand::calculateSiteTrfficPurity()
     */
    public function testCalculateSiteTrfficPurity()
    {
        $method = new ReflectionMethod('CronCommand', 'calculateSiteTrfficPurity');
        $method->setAccessible(true);
        $method->invokeArgs($this->_cron, array());
    }

    /**
     * Проверяет успешно ли завершается метод _domainState()
     *
     * @covers CronCommand::_domainState()
     */
    public function testDomainState()
    {
        $method = new ReflectionMethod('CronCommand', '_domainState');
        $method->setAccessible(true);
        $method->invokeArgs($this->_cron, array());
    }

    /**
     * Проверяет успешно ли завершается метод _testDomainState()
     *
     * @covers CronCommand::_testDomainState()
     */
    public function testTestDomainState()
    {
        $method = new ReflectionMethod('CronCommand', '_testDomainState');
        $method->setAccessible(true);
        $method->invokeArgs($this->_cron, array());
    }

    /**
     * Проверяет успешно ли завершается метод _launderedSubDomain()
     *
     * @covers CronCommand::_launderedSubDomain()
     */
    public function testLaunderedSubDomain()
    {
        $method = new ReflectionMethod('CronCommand', '_launderedSubDomain');
        $method->setAccessible(true);
        $method->invokeArgs($this->_cron, array());
    }

    public function checkIuCampaignDataPrivider()
    {
        return array(
            array(
                'status' => Company::STATUS_ACTIVE, 'isPaid' => true,
                'excludedCompanies' => array(), 
                'expects' => array(
                    'contains' => array(18381, 183811),
                    'notContains' => array(
                        18384, // не проходит по категории (2)
                        18383, // не проходит по значению поля url (^http*)
                        )
                )),
            array(
                'status' => Company::STATUS_ACTIVE, 'isPaid' => true,
                'excludedCompanies' => array(18381, 183811), 
                'expects' => array(
                    'notContains' => array(18381, 183811)
                )),
            array(
                'status' => Company::STATUS_DISABLED_BY_IU, 'isPaid' => false,
                'excludedCompanies' => array(), 
                'expects' => array(
                    'contains' => array(18382, 183821)
                )),
            array(
                'status' => Company::STATUS_DISABLED_BY_IU, 'isPaid' => false,
                'excludedCompanies' => array(18382, 183821), 
                'expects' => array(
                    'notContains' => array(18382, 183821)
                )),
        );
    }

    /**
     * Проверяет корректность возврата кампаний подлежащих проверке на доступность URL
     *
     * @dataProvider checkIuCampaignDataPrivider
     * @covers CronCommand::getCheckIuCampaignList()
     * @return void
     */
    public function testGetCheckIuCampaignList($status, $isPaid, array $excludedCompanies, array $expects)
    {
        $this->loadFixtures(array('company' => 'Company'));

        $method = new ReflectionMethod('CronCommand', 'getCheckIuCampaignList');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->_cron, array($status, $isPaid, $excludedCompanies));
        $this->assertInternalType('array', $result);
        $this->assertContainsOnly('array', $result);
        $ids = ArrayHelper::getColumn($result, 'id');
        if (isset($expects['contains'])) {
            foreach ($expects['contains'] as $key => $value) {
                $this->assertContains($value, $ids);
            }
        }
        if (isset($expects['notContains'])) {
            foreach ($expects['notContains'] as $key => $value) {
                $this->assertNotContains($value, $ids);
            }
        }
    }
}
