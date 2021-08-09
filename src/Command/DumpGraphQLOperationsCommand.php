<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Command;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\LeafType;
use Overblog\GraphQLBundle\Request\Executor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpGraphQLOperationsCommand extends Command
{
    protected static $defaultName        = 'graphql:dump-operations';
    protected static $defaultDescription = 'Dump query and mutation into a .gql file';
    private Executor $executor;

    public function __construct(Executor $executor)
    {
        parent::__construct();

        $this->executor = $executor;
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('file', InputArgument::REQUIRED, 'File to dump GraphQL operations to')
            ->addOption('schema', 's', InputOption::VALUE_REQUIRED, 'Schema name')
            ->addOption('alias', 't', InputOption::VALUE_REQUIRED, 'The result operation alias', 'payload')
            ->addOption('import', 'i', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Imports to add at the beggining of the file', ['./fragments.gql'])
            ->setHelp(
                <<<'EOF'
<info>%command.name%</info> dumps all queries and mutations

EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $schema = $this->executor->getSchema($input->getOption('schema'));
        $file   = $input->getArgument('file');
        $output = '';
        if (file_exists($file)) {
            $output = file_get_contents($file);
        }

        $lines = [];
        foreach ($input->getOption('import') as $import) {
            $lines[] = $this->getImport($import);
        }

        $query    = $schema->getQueryType();
        $mutation = $schema->getMutationType();
        foreach (['query' => $query, 'mutation' => $mutation] as $operationName => $operation) {
            foreach ($operation->getFields() as $field) {
                $lines[] = $this->getOperation($operationName, $field, $input->getOption('alias'));
            }
        }

        file_put_contents($file, implode("\n", $lines));

        return Command::SUCCESS;
    }

    protected function getImport(string $import)
    {
        return sprintf('#import "%s"', $import);
    }

    protected function getOperation(string $operationName, FieldDefinition $field, string $alias)
    {
        $outputType = $field->getType();

        $outputFields = '';
        if (!$outputType instanceof LeafType) {
            $fragment     = sprintf('%sFields', lcfirst((string) $outputType->name));
            $outputFields = sprintf('{
        ...%s
    }', $fragment);
        }

        $variables = [];
        foreach ($field->args as $arg) {
            $variables[] = ['name' => $arg->name, 'type' => (string) $arg->getType()];
        }

        $variablesList = implode(', ', array_map(fn ($var) => sprintf('$%s: %s', $var['name'], $var['type']), $variables));
        $argumentsList = implode(', ', array_map(fn ($var) => sprintf('%s: $%s', $var['name'], $var['name']), $variables));

        return sprintf('
%s %s%s {
    %s: %s%s %s
}',
$operationName, $field->getName(), '' !== $variablesList ? sprintf('(%s)', $variablesList) : '', $alias, $field->getName(), '' !== $argumentsList ? sprintf('(%s)', $argumentsList) : '', $outputFields);
    }
}
