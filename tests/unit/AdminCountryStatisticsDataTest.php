<?php
/**
 * Class AdminCountryStatisticsDataTest
 *
 * @author Tarasenko Andrey
 */

/**
 * Class for testing class AdminCountryStatisticsData
 *
 * @author Tarasenko Andrey
 */
class AdminCountryStatisticsDataTest extends DbTestCase
{
    /**
     * @var array Fixtures
     */
    public $fixtures = array(
        'downloads_unique_log' => ':downloads_unique_log',
        'init_unique_log' => ':init_unique_log',
        'done_unique_log' => ':done_unique_log',
    );

    /**
     * Тестируется диапазон дат при неуказанном сайте (Общая статистика)
     */
    public function testCommonStatistics()
    {
        $model = new CountriesFilterForm;
        
        $model->start      = '2015-05-13';
        $model->end        = '2015-05-14';
        $model->site_id    = 0;
        
        $model->process();
        
        $adminCountryStatisticsData = new AdminCountryStatisticsData($model);
        $this->assertInternalType('array', ($rows = $adminCountryStatisticsData->getRows()));
        $this->assertCount(2, $rows);
        
        $row = $rows[0];
        $this->assertEquals('BY', $row['country']);
        $this->assertEquals(2, $row['downloads']);
        $this->assertEquals(50, $row['downloadsPercent']);
        $this->assertEquals(4, $row['init']);
        $this->assertEquals(50, $row['initPercent']);
        $this->assertEquals(2, $row['done']);
        $this->assertEquals(50, $row['donePercent']);
        $this->assertEquals(2, $row['emptyOffers']);
        $this->assertEquals(50, $row['emptyOffersPercent']);
        
        $row = $rows[1];
        $this->assertEquals('UA', $row['country']);
        $this->assertEquals(2, $row['downloads']);
        $this->assertEquals(50, $row['downloadsPercent']);
        $this->assertEquals(4, $row['init']);
        $this->assertEquals(50, $row['initPercent']);
        $this->assertEquals(2, $row['done']);
        $this->assertEquals(50, $row['donePercent']);
        $this->assertEquals(2, $row['emptyOffers']);
        $this->assertEquals(50, $row['emptyOffersPercent']);
        
        $this->assertInternalType('array', ($rows = $adminCountryStatisticsData->getGoogleMapRows()));
        $this->assertCount(2, $rows);

        $this->assertInstanceOf('CArrayDataProvider', $adminCountryStatisticsData->getDataProvider());
    }
    
    /**
     * We tests date period for selected site (Statistic by site)
     */
    public function testSiteStatistics()
    {
        $model = new CountriesFilterForm;
        
        $model->start      = '2015-05-14';
        $model->end        = '2015-05-14';
        $model->site_id    = 5546;
        
        $model->process();
        
        $adminCountryStatisticsData = new AdminCountryStatisticsData($model);
        $this->assertInternalType('array', ($rows = $adminCountryStatisticsData->getRows()));
        $this->assertCount(1, $rows);
        
        $row = $rows[0];
        $this->assertEquals('BY', $row['country']);
        $this->assertEquals(1, $row['downloads']);
        $this->assertEquals(100, $row['downloadsPercent']);
        $this->assertEquals(2, $row['init']);
        $this->assertEquals(100, $row['initPercent']);
        $this->assertEquals(1, $row['done']);
        $this->assertEquals(100, $row['donePercent']);
        $this->assertEquals(1, $row['emptyOffers']);
        $this->assertEquals(100, $row['emptyOffersPercent']);
        
        $this->assertInternalType('array', ($rows = $adminCountryStatisticsData->getGoogleMapRows()));
        $this->assertCount(1, $rows);
        
        $this->assertInstanceOf('CArrayDataProvider', $adminCountryStatisticsData->getDataProvider());
    }
}
