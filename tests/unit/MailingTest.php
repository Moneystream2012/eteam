<?php
/**
 * Class MailingTest
 *
 * @author Tarasenko Andrey
 */
Yii::import('application.components.mailing.Mailing');
/**
 * Класс для тестирования абстрактного класса рассылки
 *
 * @author Tarasenko Andrey
 */
class MailingTest extends CDbTestCase
{
    /**
     * @var Mailing стаб объекта рассылки
     */
    public $stub;

    /**
     * Инициализирует стаб
     */
    public function setUp()
    {
        parent::setUp();
        $this->stub = $this->getMockForAbstractClass('Mailing');
    }

    /**
     * Тестирует метод, инициализирующий рассылку исходными значениями
     *
     * Случай, когда переданы все параметры
     *
     * @covers Mailing::initialize()
     */
    public function testInitialize()
    {
        $mailing = $this->stub;

        $mailing->initialize('emailto@mailforspam.com', 'Subject', array('var' => 'val'), 'test', 'emailfrom@mailforspam.com');

        $this->assertInstanceOf('TextLogger', $mailing->logger);
        $this->assertEquals('emailto@mailforspam.com', $mailing->emailTo);
        $this->assertEquals('Subject', $mailing->subject);
        $this->assertEquals(array('var' => 'val'), $mailing->body);
        $this->assertEquals('test', $mailing->view);
        $this->assertEquals('emailfrom@mailforspam.com', $mailing->emailFrom);
    }

    /**
     * Тестирует метод, инициализирующий рассылку исходными значениями
     *
     * Случай, когда переданы только обязательные параметры
     *
     * @covers Mailing::initialize()
     */
    public function testInitializeWithoutViewAndEmailFrom()
    {
        $mailing = $this->stub;
        $mailing->initialize('emailto@mailforspam.com', 'Subject', array('var' => 'val'));

        $this->assertNull($mailing->view);
        $this->assertEquals(Yii::app()->project->getNoReplyEmail(), $mailing->emailFrom);
    }

    /**
     * Тестирует метод, отправляющий email через вызов веб-приложения
     *
     * @covers Mailing::sendWithWebApp()
     */
    public function testSendWithWebApp()
    {
        $stub = $this->getMockBuilder('Mailing')->setMethods(array('sendWebAppRequest'))->getMockForAbstractClass();
        $stub->expects($this->once())->method('sendWebAppRequest');

        $stub->sendWithWebApp();
    }

    /**
     * Тестирует метод, выполняющий рассылку альтернативным способом
     *
     * Случай, когда класс альтернативной рассылки неуказан
     *
     * @covers Mailing::sendAlternativeWay()
     */
    public function testSendAlternativeWayEmptyClass()
    {
        $stub = $this->getMockBuilder('Mailing')->setMethods(array('startAlternativeMailing'))->getMockForAbstractClass();
        $stub->logger = new TextLogger(Yii::app()->getRuntimePath(), 'mailing.log', false);

        $stub->alternativeMailingClass = null;
        $stub->expects($this->never())->method('startAlternativeMailing');
        $this->assertFalse($stub->sendAlternativeWay());
    }

    /**
     * Тестирует метод, выполняющий рассылку альтернативным способом
     *
     * Случай, когда указан несуществующий класс альтернативной рассылки
     *
     * @covers Mailing::sendAlternativeWay()
     */
    public function testSendAlternativeWayFakeClass()
    {
        $stub = $this->getMockBuilder('Mailing')->setMethods(array('startAlternativeMailing'))->getMockForAbstractClass();
        $stub->logger = new TextLogger(Yii::app()->getRuntimePath(), 'mailing.log', false);

        $stub->alternativeMailingClass = 'FakeMailing';
        $stub->expects($this->never())->method('startAlternativeMailing');
        $this->assertFalse($stub->sendAlternativeWay());
    }

    /**
     * Тестирует метод, выполняющий рассылку альтернативным способом
     *
     * Случай, когда указан корректный класс альтернативной рассылки
     *
     * @covers Mailing::sendAlternativeWay()
     */
    public function testSendAlternativeWayExistingClass()
    {
        $stub = $this->getMockBuilder('Mailing')->setMethods(array('startAlternativeMailing'))->getMockForAbstractClass();
        $stub->logger = new TextLogger(Yii::app()->getRuntimePath(), 'mailing.log', false);

        $stub->alternativeMailingClass = 'IMMailing';
        $stub->expects($this->once())->method('startAlternativeMailing');
        $stub->sendAlternativeWay();
    }
    
    /**
     * Возвращает связку: имейл пользователя, соответствующий ему идентификатор и результат проверки
     * 
     * @coversNothing
     * @return array
     */
    public function userIdEmailProvider()
    {
        return array(
          array(1, 'admin@example.com', true),
          array(2, 'webamster@example.com', true),
          array(3, 'advertizer@test.com', true),
          array(4, 'advertizer@test.com', false),
        );
    }
    
    /**
     * Тестирует метод, получения идентификатора пользователя по его имейлу
     *
     * @dataProvider userIdEmailProvider
     * @covers Mailing::getUserId()
     * @param integer $userId Идентификатор пользователя
     * @param string $userEmail Email  пользователя
     * @param boolean $expectedResult Ожидаемый результат проверки
     */
    public function testGetUserId($userId, $userEmail, $expectedResult)
    {
        $stub = $this->getMockBuilder('Mailing')->setMethods(array('getUserId'))->getMockForAbstractClass();
        $stub->emailTo = $userEmail;
        
        $class = new ReflectionClass('Mailing');
        $method = $class->getMethod('getUserId');
        $method->setAccessible(true);
        
        $this->assertThat(
          $method->invoke($stub), 
                $expectedResult
                    ? $this->equalTo($userId)
                    : $this->logicalNot($this->equalTo($userId))
        );
    }
}
