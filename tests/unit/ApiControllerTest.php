<?php
/**
 * @link http://installmonster.ru/
 */
Yii::import('application.controllers.ApiController');
/**
 * API unit tests
 *
 * @author Tarasenko Andrey <andrey.installmonster@gmail.com>
 */
class ApiControllerTest extends DbTestCase
{
    /**
     * @var array fixtures
     */
    public $fixtures = array(
        'browser' => 'Browser',
        'ip_date_ban' => ':ip_date_ban',
        'bot_application_set' => ':bot_application_set'
    );

    /**
     * Data for testing placeholder for main user browser
     *
     * @return array
     */
    public function browsers()
    {
        return array(
            array('K-Meleon', 'k-meleon'),
            array('', 'other'),
            array('-', 'other'),
            array('Konqueror', 'konqueror'),
            array('Opera', 'opera'),
            array('FireFox', 'firefox'),
            array('MSIE', 'msie'),
        );
    }

    /**
     * Testing placeholder for main user browser
     *
     * @dataProvider browsers
     */
    public function testGetBrowserPlaceholderReplacement($browser, $expectedResult)
    {
        $stub = $this->getMockBuilder('ApiController')
            ->disableOriginalConstructor()
            ->setMethods(array('getBrowser'))
            ->getMock();
        $stub->init();

        $stub->method('getBrowser')->willReturn($browser);
        $this->assertEquals($expectedResult, $stub->getBrowserPlaceholderReplacement());
    }

    /**
     * Testing method, that return set of bot software for empty offer response
     *
     * @covers ApiController::getBotApplicationSets()
     */
    public function testGetBotApplicationSets()
    {
        $expected = array(
            array('7-Zip 9.20', 'GnuWin32: Wget-1.11.4-1', 'WebFldrs XP'),
            array('Mozilla Firefox (3.0.1)', 'WebFldrs XP', 'Windows Imaging Component', 'Windows XP Service Pack 2'),
            array('7-Zip 4.65', 'WebFldrs XP'),
            array('7-Zip 9.20', 'WebFldrs XP'),
            array('WebFldrs XP', 'WinPcap 4.1.2'),
        );

        $stub = $this->getMockBuilder('ApiController')
            ->disableOriginalConstructor()
            ->setMethods(null)
        ->getMock();

        Yii::app()->cache->delete('botApplicationSets');
        $this->assertEquals($expected, $stub->getBotApplicationSets());
        $this->assertEquals($expected, Yii::app()->cache->get('botApplicationSets'));
    }

    /**
     * Test method, that compare user software list with bot criteria
     *
     * @covers ApiController::checkBot()
     */
    public function testCheckBot()
    {
        $botApplicationSets = array(
            array('7-Zip 9.20', 'GnuWin32: Wget-1.11.4-1', 'WebFldrs XP'),
            array('Mozilla Firefox (3.0.1)', 'WebFldrs XP', 'Windows Imaging Component', 'Windows XP Service Pack 2'),
            array('7-Zip 4.65', 'WebFldrs XP'),
            array('7-Zip 9.20', 'WebFldrs XP'),
            array('WebFldrs XP', 'WinPcap 4.1.2'),
        );

        $stub = $this->getMockBuilder('ApiController')
            ->disableOriginalConstructor()
            ->setMethods(array('getBotApplicationSets'))
        ->getMock();

        $stub->method('getBotApplicationSets')->willReturn($botApplicationSets);

        $stub->request['system']['applications'] = array();
        $this->assertTrue($stub->checkBot());

        $stub->request['system']['applications'] = 123;
        $this->assertTrue($stub->checkBot());

        $stub->request['system']['applications'] = null;
        $this->assertTrue($stub->checkBot());

        $stub->request['system']['applications'] = 'fake';
        $this->assertTrue($stub->checkBot());

        $stub->request['system']['applications'] = array('Application 1', 'Application 2');
        $this->assertFalse($stub->checkBot());

        $stub->request['system']['applications'] = array('7-Zip 4.65', 'WebFldrs XP');
        $this->assertTrue($stub->checkBot());

        $stub->request['system']['applications'] = array('Windows XP Service Pack 2', 'Mozilla Firefox (3.0.1)', 'Windows Imaging Component', 'WebFldrs XP');
        $this->assertTrue($stub->checkBot());

        $stub->request['system']['applications'] = array('Mozilla Firefox (3.0.1)', 'WebFldrs XP', 'Windows Imaging Component');
        $this->assertFalse($stub->checkBot());
    }
}
