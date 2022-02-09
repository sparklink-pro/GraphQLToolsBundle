Installation
===========

This bundle use the [Overblog/GraphQLBundle](https://github.com/overblog/GraphQLBundle), first you need to install it.

1. Install the bundle:
    ```bash
    $ composer require sparklink/graphql-tools-bundle
    ```
2. In `config/packages/graphql.yaml`, add the builders:
```yaml
overblog_graphql:
  # ...
    definitions:
    #.....
        builders:
        fields:
            - alias: CrudQuery
              class: "Sparklink\\GraphQLToolsBundle\\GraphQL\\Builder\\CrudQueryBuilder"
            - alias: CrudMutation
              class: "Sparklink\\GraphQLToolsBundle\\GraphQL\\Builder\\CrudMutationBuilder"
            - alias: CrudEntityId
              class: "Sparklink\\GraphQLToolsBundle\\GraphQL\\Builder\\CrudEntityIdBuilder"      
```
3. In your project's root query and mutation attach: 

    In `Query`, the `CrudQuery` and `CrudEntityId` builders.
    ```php
        namespace App\GraphQL\Root;

        use App\GraphQL\Builder\CrudConfig;
        use Overblog\GraphQLBundle\Annotation as GQL;

        #[GQL\Type]
        #[GQL\FieldsBuilder(name: 'CrudQuery', config: CrudConfig::CONFIG)]
        #[GQL\FieldsBuilder(name: 'CrudEntityId', config: CrudConfig::CONFIG)]
        class Query
        {
            //...
        }
    ```
    In `Mutation`, the `CrudMutation` builder
    ```php
        namespace App\GraphQL\Root;

        use App\GraphQL\Builder\CrudConfig;
        use Overblog\GraphQLBundle\Annotation as GQL;

        #[GQL\Type]
        #[GQL\FieldsBuilder(name: 'CrudMutation', config: CrudConfig::CONFIG)]
        class Mutation
        {
            //...
        }   
    ```

4. Configure the builders (see [here](./crud-builder.md#usage) for more information)