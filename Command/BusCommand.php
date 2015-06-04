<?php
/**
 * Created by PhpStorm.
 * User: fernando
 * Date: 3/06/15
 * Time: 11:17
 */

namespace Fer\GeneratorBundle\Command;


use Memio\Model\Constant;
use Memio\Model\Contract;
use Memio\Model\FullyQualifiedName;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Memio\Memio\Config\Build;
use Memio\Model\File;
use Memio\Model\Object;
use Memio\Model\Method;
use Memio\Model\Argument;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class BusCommand extends ContainerAwareCommand {
    protected function configure()
    {
        $this
            ->addArgument('command-name', InputArgument::REQUIRED, 'Command name in CameCase')
            ->setName('fer:command:generate');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandName = $input->getArgument('command-name');
        $this->generateCommandHandler($commandName);
        //$this->generateDefaultCommand();
        $this->generateCommandMessage($commandName);
        $this->installCommandService($commandName);
    }

    protected function generateCommandHandler($commandName)
    {

        $file = File::make('src/AppBundle/Command/Handler/'.$commandName.'Handler.php')
            ->setStructure(
                Object::make('AppBundle\Command\Handler\\'. $commandName . 'Handler')
                    ->addMethod(
                        Method::make('__construct')
                    )
                    ->addMethod(
                        Method::make('handle')
                            ->addArgument(new Argument('mixed', 'message'))
                    )

            )
        ;

        // Generate the code and display in the console
        $prettyPrinter = Build::prettyPrinter();
        $generatedCode = $prettyPrinter->generateCode($file);

        $this->writeFile($this->getContainer()->getParameter('kernel.root_dir') . '/../' . $file->getFilename(), $generatedCode);
    }

    protected function generateCommandMessage($commandName)
    {
        $file = File::make('src/AppBundle/Command/Message/'. $commandName .'.php')
            ->addFullyQualifiedName(FullyQualifiedName::make('Fer\HelpersBundle\CQRS\DefaultCommand'))
            ->setStructure(
                Object::make('AppBundle\Command\Message\\'.$commandName)
                    ->addConstant(
                        Constant::make('COMMAND_NAME', "'". $commandName ."'")
                    )
                    ->extend(Object::make('Fer\HelpersBundle\CQRS\DefaultCommand'))
            )
        ;

        // Generate the code and display in the console
        $prettyPrinter = Build::prettyPrinter();
        $generatedCode = $prettyPrinter->generateCode($file);

        $this->writeFile($this->getContainer()->getParameter('kernel.root_dir') . '/../' . $file->getFilename(), $generatedCode);

    }

    protected function generateDefaultCommand()
    {
        $file = File::make('src/AppBundle/Command/Message/DefaultCommand.php')
            ->addFullyQualifiedName(FullyQualifiedName::make('SimpleBus\Message\Name\NamedMessage'))
            ->setStructure(
                Object::make('AppBundle\Command\Message\DefaultCommand')
                    ->makeAbstract()
                    ->addConstant(
                        Constant::make('COMMAND_NAME', "'the value'")
                    )
                    ->implement(Contract::make('SimpleBus\Message\Name\NamedMessage'))
                    ->addMethod(
                        Method::make('messageName')
                        ->setBody('return static::COMMAND_NAME;')
                        ->makeStatic()
                    )
            )
        ;

        // Generate the code and display in the console
        $prettyPrinter = Build::prettyPrinter();
        $generatedCode = $prettyPrinter->generateCode($file);

        $this->writeFile($this->getContainer()->getParameter('kernel.root_dir') . '/../' . $file->getFilename(), $generatedCode);
    }

    public function installCommandService($commandName)
    {
        $serviceCommandName = 'command_handler_' .strtolower($commandName) ;
        $commands = [
            $serviceCommandName => [
                'class' => 'AppBundle\Command\Handler\\'. $commandName . 'Handler',
                'tags' => [
                    ['name' => 'command_handler', 'handles' => $commandName]
                ]
            ]
        ];

        $configFile = $this->getContainer()->getParameter('kernel.root_dir').'/../src/AppBundle/Resources/config/handlers.yml';
        $dumper = new Dumper();

        if (!file_exists($configFile)) {
            @mkdir(dirname($configFile), 0777, true);
            file_put_contents($configFile, $dumper->dump($commands, 2));
            return true;
        }

        $yaml = new Parser();
        try {
            $yamlConfig = $yaml->parse(file_get_contents($configFile));
        } catch (ParseException $e) {
            printf("Unable to parse the YAML string: %s", $e->getMessage());
            return false;
        }

        if (!array_key_exists($serviceCommandName, $yamlConfig)) {
            $yamlConfig = array_merge($yamlConfig, $commands);
            file_put_contents($configFile, $dumper->dump($yamlConfig, 2));
        }

    }

    protected function writeFile($filePath, $content)
    {
        @mkdir(dirname($filePath), 0777, true);
        if (!file_exists($filePath)) {
            file_put_contents($filePath, $content);
            return true;
        }
    }

}