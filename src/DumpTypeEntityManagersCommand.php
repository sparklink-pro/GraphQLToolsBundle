<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Command;

use Sparklink\GraphQLToolsBundle\Manager\EntityTypesManager;
use Sparklink\GraphQLToolsBundle\Service\TypeEntityResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DumpTypeEntityManagersCommand extends Command
{
    protected static $defaultName = 'graphql:dump-managers';
    protected static $defaultDescription = 'Dump type entity managers';
    protected TypeEntityResolver $resolver;
    private EntityTypesManager $typesManager;

    public function __construct(TypeEntityResolver $resolver, EntityTypesManager $typesManager)
    {
        parent::__construct();
        $this->resolver = $resolver;
        $this->typesManager = $typesManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->setHelp(
                <<<'EOF'
<info>%command.name%</info> dumps all queries and mutations

EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $rows = [];
        foreach ($this->resolver->getMapping() as $type => $mapping) {
            $class = $mapping['class'];
            $manager = $this->typesManager->getManager($type);
            $rows[] = [$type, $class, \get_class($manager)];
        }

        $table
            ->setHeaders(['Type', 'Class', 'Manager'])
            ->setRows($rows)
        ;
        $table->render();

        return Command::SUCCESS;
    }
}
