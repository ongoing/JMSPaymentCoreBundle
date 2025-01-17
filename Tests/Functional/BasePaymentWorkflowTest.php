<?php

namespace JMS\Payment\CoreBundle\Tests\Functional;

use Doctrine\ORM\EntityManager;
use JMS\Payment\CoreBundle\Tests\Functional\TestBundle\Entity\Order;
use JMS\Payment\CoreBundle\Util\Number;
use Symfony\Component\Routing\RouterInterface;

abstract class BasePaymentWorkflowTest extends BaseTestCase
{
    protected function getRawExtendedData($paymentInstruction)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $stmt = $em->getConnection()->prepare(
            'SELECT extended_data FROM payment_instructions WHERE id = '.$paymentInstruction->getId()
        );

        $result = $stmt->executeQuery()->fetchAllAssociative();

        return unserialize($result[0]['extended_data']);
    }

    protected function doTestPaymentDetails()
    {
        $client = $this->createClient();
        $this->importDatabaseSchema();

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        /** @var RouterInterface $router */
        $router = $this->getContainer()->get('router');

        $order = new Order(123.45);
        $em->persist($order);
        $em->flush();

        $crawler = $client->request('GET', $router->generate('payment_details', ['orderId' => $order->getId()]));
        $form = $crawler->selectButton('submit_btn')->form();
        $form['jms_choose_payment_method[method]']->select('test_plugin');
        $client->submit($form);

        $response = $client->getResponse();
        $this->assertResponseStatusCodeSame(201, substr($response, 0, 2000));

        $em->clear();
        $order = $em->getRepository(Order::class)->find($order->getId());
        $this->assertTrue(Number::compare(123.45, $order->getPaymentInstruction()->getAmount(), '=='));
        $this->assertEquals('bar', $order->getPaymentInstruction()->getExtendedData()->get('foo'));

        return $order;
    }
}
