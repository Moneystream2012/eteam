<?php
/**
 * Class CronCommandComponentTest
 *
 * @author Tarasenko Andrey
 */

/**
 * Class for testing CronCommandComponent
 *
 * @author Tarasenko Andrey
 * @covers CronCommandComponent
 */
class CronCommandComponentTest extends DbTestCase
{
    
    /**
     * @coversNothing
     * 
     * @return \CronCommandComponent
     */
    public function additionProvider()
    {
        $cronCommandComponent = new CronCommandComponent();
        
        return array(
            array($cronCommandComponent, $domain = 'somedomain', 'http://' . $domain . '/domainRotation/testresponse'),
            array($cronCommandComponent, $domain = 'somedomain', 'http://' . $domain . '/domainRotation/response/'),
            array($cronCommandComponent, $domain = 'somedomain', 'http://' . $domain . '/domainRotation/ipresponse/?arg=var'),
            array($cronCommandComponent, $domain = 'somedomain', 'http://' . $domain . '/domainRotation/advertizeResponse'),
        );
    }
    
    
    /**
     * @covers CronCommandComponent::getGuardedDomain
     * @dataProvider additionProvider
     */
    public function testGetGuardedDomain(CronCommandComponent $cronCommandComponent, $domain, $callbackUrl)
    {
        $this->assertInstanceOf('CronCommandComponent', $cronCommandComponent);
        
        $this->assertRegExp($pattern = '@http:\/\/([^\/]+).*[&?]guard=([^$]+)$@i', $guardedUrl = $cronCommandComponent->getGuardedDomain($domain, $callbackUrl));
        $this->assertEquals(1, preg_match($pattern, $guardedUrl, $matches));
        
        $criteria=new CDbCriteria(); 
        $criteria->condition = '`domain`=:_domain AND `key`=:_key';
        $criteria->params = array(':_domain' => $matches[1], ':_key' => $matches[2]);
        
        $this->assertEquals(1, Yii::app()->db->commandBuilder->createCountCommand('{{domain_guard_key}}', $criteria)->queryScalar());
        Yii::app()->db->commandBuilder->createDeleteCommand('{{domain_guard_key}}', $criteria)->execute();
        
        $this->assertEquals(0, Yii::app()->db->commandBuilder->createCountCommand('{{domain_guard_key}}', $criteria)->queryScalar());
    }
}
