<?php

namespace JMS\Payment\CoreBundle\DependencyInjection;

use JMS\Payment\CoreBundle\Entity\ExtendedDataType;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\Kernel;

/*
 * Copyright 2010 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class JMSPaymentCoreExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('doctrine', ['dbal' => ['types' => [ExtendedDataType::NAME => ExtendedDataType::class]]]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $xmlLoader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $xmlLoader->load('payment.xml');

        if (version_compare(Kernel::VERSION, '3.0', '>=')) {
            $xmlLoader->load('commands.xml');
        }

        $config = $this->processConfiguration(
            $this->getConfiguration($configs, $container),
            $configs
        );

        $container->setParameter('payment.encryption.enabled', $config['encryption']['enabled']);

        if ($config['encryption']['enabled']) {
            $container->setParameter('payment.encryption', $config['encryption']['provider']);
            $container->setParameter('payment.encryption.secret', $config['encryption']['secret']);

            foreach (['mcrypt', 'defuse_php_encryption'] as $provider) {
                $container->setParameter("payment.encryption.$provider.secret", $config['encryption']['secret']);
            }
        } else {
            $container->removeAlias('payment.encryption_service');
            $container->removeDefinition('payment.encryption');
            $container->removeDefinition('payment.encryption.mcrypt');
            $container->removeDefinition('payment.encryption.defuse_php_encryption');
        }
    }

    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration($this->getAlias());
    }
}
