<?php
/**
 * Created by PhpStorm.
 * User: fernando
 * Date: 2/6/15
 * Time: 17:12
 */

namespace Fer\GeneratorBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Fer\GeneratorBundle\Util\KernelManipulator;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Dumper;

class PackagesCommand extends ContainerAwareCommand {
    protected function configure()
    {
        $this->setName('fer:installdeps');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bundles = [
            [
                'namespace' => 'JMS\\AopBundle',
                'bundle'    => 'JMSAopBundle'
            ],
            [
                'namespace' => 'JMS\\DiExtraBundle',
                'bundle'    => 'JMSDiExtraBundle',
                'params'    => '$this'
            ],
            [
                'namespace' => 'Doctrine\\Bundle\\FixturesBundle',
                'bundle'    => 'DoctrineFixturesBundle',
            ],
            [
                'namespace' => 'SimpleBus\\SymfonyBridge',
                'bundle'    => 'SimpleBusCommandBusBundle',
                'config'    => 'bus_config.yml'
            ],
            [
                'namespace' => 'SimpleBus\\SymfonyBridge',
                'bundle'    => 'SimpleBusEventBusBundle',
            ],
            [
                'namespace' => 'SimpleBus\\SymfonyBridge',
                'bundle'    => 'DoctrineOrmBridgeBundle',
            ],
            [
                'namespace' => 'Tbbc\\RestUtilBundle',
                'bundle'    => 'TbbcRestUtilBundle',
                'config'    => 'exceptions.yml'
            ],
            [
                'namespace' => 'Lexik\\Bundle\\JWTAuthenticationBundle',
                'bundle'    => 'LexikJWTAuthenticationBundle',
                'config'    => 'jwt_security.yml'
            ]
        ];

        foreach ($bundles as $bundle) {
            $params = isset($bundle['params']) ? $bundle['params']: null;
            $this->updateKernel($output, $this->getContainer()->get('kernel'), $bundle['namespace'], $bundle['bundle'], $params);
            if (isset($bundle['config'])) {
                $this->updateConfig($output, $this->getContainer()->get('kernel'), $bundle['config']);
            }

        }
    }

    protected function updateKernel(OutputInterface $output, $kernel, $namespace, $bundle, $params = null)
    {
        $output->writeln(sprintf("Enabling the bundle <comment>%s</comment> inside the Kernel", $bundle));
        $manip = new KernelManipulator($kernel);
        try {
            $manip->addBundle($namespace.'\\'.$bundle, $params);

        } catch (\RuntimeException $e) {
            return array(
                sprintf('Bundle <comment>%s</comment> is already defined in <comment>AppKernel::registerBundles()</comment>.', $namespace.'\\'.$bundle),
                '',
            );
        }

    }

    protected function updateConfig(OutputInterface $output, $kernel, $configFile)
    {
        $output->writeln(sprintf("Enabling the config <comment>%s</comment> inside the Kernel", $configFile));
        $config = $this->getContainer()->getParameter('kernel.root_dir').'/config/config.yml';

        file_put_contents($this->getContainer()->getParameter('kernel.root_dir').'/config/' . $configFile, file_get_contents(__DIR__ . '/../Resources/config/' . $configFile));
        $yaml = new Parser();
        try {
            $yamlConfig = $yaml->parse(file_get_contents($config));
        } catch (ParseException $e) {
            printf("Unable to parse the YAML string: %s", $e->getMessage());
        }

        $found = false;
        foreach ($yamlConfig['imports'] as $resource ) {
            if ($resource['resource'] === $configFile) {
                $found = true;
            }
        }
        if (!$found) {
            $yamlConfig['imports'][] = ['resource' => $configFile];
        }

        $dumper = new Dumper();
        file_put_contents($config, $dumper->dump($yamlConfig, 2));

    }

    protected function configChanges(OutputInterface $output, $kernel)
    {
        $output->writeln(sprintf("Changing Symfony config"));
        $config = $this->getContainer()->getParameter('kernel.root_dir').'/config/config.yml';
        $yaml = new Parser();
        try {
            $yamlConfig = $yaml->parse(file_get_contents($config));
        } catch (ParseException $e) {
            printf("Unable to parse the YAML string: %s", $e->getMessage());
        }

        if(!isset($yamlConfig['framework']['serializer']['enabled'])){
            $yamlConfig['framework']['serializer']['enabled'] = true;
            $output->writeln(sprintf("Enabling Symfony serializer"));
        }


        $dumper = new Dumper();
        file_put_contents($config, $dumper->dump($yamlConfig, 2));
    }


}