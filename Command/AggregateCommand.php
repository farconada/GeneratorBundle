<?php
/**
 * Created by PhpStorm.
 * User: fernando
 * Date: 14/6/15
 * Time: 9:41
 */

namespace Fer\GeneratorBundle\Command;


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

class AggregateCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this
            ->addArgument('aggregate-name', InputArgument::REQUIRED, 'Aggregate root name in CameCase')
            ->setName('fer:aggregate:generate');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $aggregateName = $input->getArgument('aggregate-name');
        $this->generateAggregateId($output, $aggregateName);
        $this->generateAggregateEntity($output, $aggregateName);
        $this->generateRepositoryInterface($output, $aggregateName);
        $this->generateRepository($output, $aggregateName);
        $this->generateController($output, $aggregateName);
    }

    protected function generateAggregateId(OutputInterface $output, $aggregateName)
    {
        $file = File::make('src/AppBundle/Entity/'. $aggregateName .'Id.php')
            ->addFullyQualifiedName(FullyQualifiedName::make('Fer\HelpersBundle\CQRS\AggregateIdInterface'))
            ->setStructure(
                Object::make('AppBundle\Entity\\'.$aggregateName . 'Id')
                    ->implement(Contract::make('Fer\HelpersBundle\CQRS\AggregateIdInterface'))
            )
        ;

        // Generate the code and display in the console
        $prettyPrinter = Build::prettyPrinter();
        $generatedCode = $prettyPrinter->generateCode($file);

        $this->writeFile($output, $this->getContainer()->getParameter('kernel.root_dir') . '/../' . $file->getFilename(), $generatedCode);
    }

    public function generateAggregateEntity(OutputInterface $output, $aggregateName)
    {
        $file = File::make('src/AppBundle/Entity/'. $aggregateName .'.php')
            ->setStructure(
                Object::make('AppBundle\Entity\\'.$aggregateName)
            )
        ;

        // Generate the code and display in the console
        $prettyPrinter = Build::prettyPrinter();
        $generatedCode = $prettyPrinter->generateCode($file);

        $this->writeFile($output,$this->getContainer()->getParameter('kernel.root_dir') . '/../' . $file->getFilename(), $generatedCode);
    }

    protected function generateRepositoryInterface(OutputInterface $output, $aggregateName)
    {
        $file = File::make('src/AppBundle/Entity/'. $aggregateName .'RepositoryInterface.php')
            ->addFullyQualifiedName(FullyQualifiedName::make('Fer\HelpersBundle\CQRS\RepositoryInterface'))
            ->setStructure(
                Contract::make('AppBundle\Entity\\'.$aggregateName . 'RepositoryInterface')
                ->extend(Contract::make('Fer\HelpersBundle\CQRS\RepositoryInterface'))
            )
        ;

        // Generate the code and display in the console
        $prettyPrinter = Build::prettyPrinter();
        $generatedCode = $prettyPrinter->generateCode($file);

        $this->writeFile($output, $this->getContainer()->getParameter('kernel.root_dir') . '/../' . $file->getFilename(), $generatedCode);
    }

    protected function generateRepository(OutputInterface $output, $aggregateName)
    {
        $file = File::make('src/AppBundle/Entity/'. $aggregateName .'Repository.php')
            ->addFullyQualifiedName(FullyQualifiedName::make('AppBundle\Entity\\'.$aggregateName . 'RepositoryInterface'))
            ->setStructure(
                Object::make('AppBundle\Entity\\'.$aggregateName . 'Repository')
                    ->implement(Contract::make('AppBundle\Entity\\'.$aggregateName . 'RepositoryInterface'))
            )
        ;

        // Generate the code and display in the console
        $prettyPrinter = Build::prettyPrinter();
        $generatedCode = $prettyPrinter->generateCode($file);

        $this->writeFile($output, $this->getContainer()->getParameter('kernel.root_dir') . '/../' . $file->getFilename(), $generatedCode);
    }

    protected function generateController(OutputInterface $output, $aggregateName)
    {
        $file = File::make('src/AppBundle/Controller/'. $aggregateName .'Controller.php')
            ->setStructure(
                Object::make('AppBundle\Controller\\'.$aggregateName . 'Controller')
            )
        ;

        // Generate the code and display in the console
        $prettyPrinter = Build::prettyPrinter();
        $generatedCode = $prettyPrinter->generateCode($file);

        $this->writeFile($output, $this->getContainer()->getParameter('kernel.root_dir') . '/../' . $file->getFilename(), $generatedCode);
    }

    protected function writeFile(OutputInterface $output, $filePath, $content)
    {
        @mkdir(dirname($filePath), 0777, true);
        if (!file_exists($filePath)) {
            file_put_contents($filePath, $content);
            $output->writeln('<fg=green>Creado fichero: ' . $filePath . '</fg=green>');
            return true;
        }
        $output->writeln('<fg=yellow>Ya existía el fichero: ' . $filePath . '</fg=yellow>');

    }

}