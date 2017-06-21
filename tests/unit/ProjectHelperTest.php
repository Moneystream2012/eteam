<?php
/**
 * @link http://installmonster.ru/
 * @copyright (c) 2015 InstallMonster
 */

/**
 * Testing helper for work with brands
 *
 * @author Tarasenko Andrey <andrey.installmonster@gmail.com>
 */
class ProjectHelperTest extends DbTestCase
{
    /**
     * @var ProjectHelper Brand Helper
     */
    protected $project;

    /**
     * @var array Fixtures
     */
    public $fixtures = array(
        'user_settings' => 'UserSettings',
    );

    /**
     * Create test instance of brand-helper
     */
    public function setUp()
    {
        parent::setUp();
        $this->project = $this->getMock('ProjectHelper', array('updateConfig', 'getHttpHost'));
        $this->project->method('getHttpHost')->willReturn('insterra.net');
    }

    /**
     * Check current brand returned value
     */
    public function testGetCurrentBrand()
    {
        $this->assertEquals('insterra', $this->project->getCurrentBrand());
    }

    /**
     * Check current brand returned value for selected domain
     */
    public function testGetBrandByDomain()
    {
        $this->assertEquals('insterra', $this->project->getBrandByDomain('insterra.ru'));
        $this->assertEquals('im', $this->project->getBrandByDomain('www.installmonster.ru'));
    }

    /**
     * Check current brand returned value for selected user
     */
    public function testGetBrandByUserId()
    {
        $this->assertEquals('im', $this->project->getBrandByUserId(2));
        $this->assertEquals('insterra', $this->project->getBrandByUserId(3));
    }
    
    /**
     * Return set with: User_ID, language, expected result of checking
     * 
     * @coversNothing
     * @return array
     */
    public function userIdLanguageProvider()
    {
        return array(
          array(1, 'en', true),
          array(2, 'ru', true),
          array(3, 'en', true),
          array(3, 'ru', false),
        );
    }

    /**
     * Check returned языка интерфейса по указанному пользователю
     * 
     * 
     * @dataProvider userIdLanguageProvider
     * @covers ProjectHelper::getLanguageByUserId()
     * @param integer $userId Идентификатор пользователя
     * @param string $userLanguage Язык интерфейса пользователя
     * @param boolean $expectedResult Ожидаемый результат проверки
     */
    public function testGetLanguageByUserId($userId, $userLanguage, $expectedResult)
    {
        $this->assertThat(
          $this->project->getLanguageByUserId($userId), 
                $expectedResult
                    ? $this->equalTo($userLanguage)
                    : $this->logicalNot($this->equalTo($userLanguage))
        );
    }

    /**
     * Check returned названия бренда
     */
    public function testGetTitle()
    {
        $this->assertEquals('Insterra', $this->project->getTitle());
        $this->assertEquals('InstallMonster', $this->project->getTitle('im'));
        $this->assertEquals('Insterra', $this->project->getTitle('insterra'));
    }

    /**
     * Check returned технического названия бренда
     */
    public function testGetName()
    {
        $this->assertEquals('insterra', $this->project->getName());
        $this->assertEquals('installmonster', $this->project->getName('im'));
        $this->assertEquals('insterra', $this->project->getName('insterra'));
    }

    /**
     * Check returned языков бренда
     */
    public function testGetLanguages()
    {
        $this->assertEquals(
            array(
                'default' => 'en',
                'ru',
            ),
            $this->project->getLanguages()
        );
        $this->assertEquals(
            array(
                'default' => 'ru',
                'en',
            ),
            $this->project->getLanguages('im')
        );
        $this->assertEquals(
            array(
                'default' => 'en',
                'ru',
            ),
            $this->project->getLanguages('insterra')
        );
    }

    /**
     * Check returned языка бренда по умолчанию
     */
    public function testGetDefaultLanguage()
    {
        $this->assertEquals('en', $this->project->getDefaultLanguage());
        $this->assertEquals('ru', $this->project->getDefaultLanguage('im'));
        $this->assertEquals('en', $this->project->getDefaultLanguage('insterra'));
    }

    /**
     * Check returned доменов бренда
     */
    public function testGetDomains()
    {
        $this->assertEquals(
            array(
                'default' => 'insterra.com',
                'insterra.net',
                'insterra.ru',
            ),
            $this->project->getDomains()
        );
        $this->assertEquals(
            array(
                'default' => 'installmonster.ru',
                'instalmonster.com',
            ),
            $this->project->getDomains('im')
        );
        $this->assertEquals(
            array(
                'default' => 'insterra.com',
                'insterra.net',
                'insterra.ru',
            ),
            $this->project->getDomains('insterra')
        );
    }

    /**
     * Check returned domain brand by default
     */
    public function testGetDefaultDomain()
    {
        $this->assertEquals('insterra.com', $this->project->getDefaultDomain());
        $this->assertEquals('installmonster.ru', $this->project->getDefaultDomain('im'));
        $this->assertEquals('insterra.com', $this->project->getDefaultDomain('insterra'));
    }

    /**
     * Check returned темы бренда
     */
    public function testGetTheme()
    {
        $this->assertEquals('insterra', $this->project->getTheme());
        $this->assertEquals('im', $this->project->getTheme('im'));
        $this->assertEquals('insterra', $this->project->getTheme('insterra'));
    }

    /**
     * Check returned валюты бренда
     */
    public function testGetCurrency()
    {
        $this->assertEquals('USD', $this->project->getCurrency());
        $this->assertEquals('RUB', $this->project->getCurrency('im'));
        $this->assertEquals('USD', $this->project->getCurrency('insterra'));
    }

    /**
     * Check returned электронной почты админа бренда
     */
    public function testGetAdminEmail()
    {
        $this->assertEquals('admin@insterra.com', $this->project->getAdminEmail());
        $this->assertEquals('admin@installmonster.ru', $this->project->getAdminEmail('im'));
        $this->assertEquals('admin@insterra.com', $this->project->getAdminEmail('insterra'));
    }

    /**
     * Check returned электронной почты бренда для рассылки
     */
    public function testGetNoReplyEmail()
    {
        $this->assertEquals('noreply@insterra.com', $this->project->getNoReplyEmail());
        $this->assertEquals('noreply@installmonster.ru', $this->project->getNoReplyEmail('im'));
        $this->assertEquals('noreply@insterra.com', $this->project->getNoReplyEmail('insterra'));
    }

    /**
     * Тестирует метод, возвращающий список брендов
     *
     * @covers ProjectHelper::getBrandList()
     */
    public function testGetBrandList()
    {
        $this->assertInternalType('array', $this->project->getBrandList());
        $this->assertEquals(
            array(
                'im' => 'InstallMonster',
                'insterra' => 'Insterra'
            ),
            $this->project->getBrandList()
        );
    }

    /**
     * Тестирует метод, возвращающий бренд по умолчанию
     *
     * @covers ProjectHelper::getDefaultBrand()
     */
    public function getDefaultBrand()
    {
        $this->assertEquals('im', $this->project->getDefaultBrand());
    }

    /**
     * Тестирует, метод возвращающий валюту пользователя
     *
     * @covers ProjectHelper::getCurrencyByUserId()
     */
    public function testGetCurrencyByUserId()
    {
        $this->assertEquals('RUB', $this->project->getCurrencyByUserId(2));
        $this->assertEquals('USD', $this->project->getCurrencyByUserId(3));
        $this->assertEquals('RUB', $this->project->getCurrencyByUserId(100500)); //fake user
    }
}
